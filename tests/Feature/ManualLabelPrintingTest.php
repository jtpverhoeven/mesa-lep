<?php

namespace Tests\Feature;

use App\Actions\Printing\SelectManualPrintSamples;
use App\Jobs\SendLabelPrinterSpoolJob;
use App\Models\Assay;
use App\Models\AssayType;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\Result;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManualLabelPrintingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_barcode_range_is_inclusive_id_based_and_accepts_reversed_plate_barcodes(): void
    {
        $first = $this->sample(['barcode' => 'S260009']);
        $middle = $this->sample(['barcode' => 'S260001']);
        $this->sample(['barcode' => 'R260001', 'sample_type' => 'R']);
        $last = $this->sample(['barcode' => 'S260002']);
        $this->sample(['barcode' => 'S260003']);

        $selected = app(SelectManualPrintSamples::class)->handle([
            'bar_range_start' => $last->barcode.'.1.2', 'bar_range_stop' => $first->barcode,
            'print_normal_samples' => true, 'print_rodac_samples' => true,
        ])->pluck('id')->all();

        $this->assertSame([$first->id, $middle->id, $last->id], $selected);
    }

    public function test_shorthand_range_uses_latest_sequence_and_rodac_subset(): void
    {
        $this->sample(['barcode' => '26000001', 'follow_no' => 1, 'sample_type' => 'R', 'leg_type' => '-']);
        $first = $this->sample(['barcode' => '26001001', 'follow_no' => 1, 'sample_type' => 'R', 'leg_type' => '-']);
        $last = $this->sample(['barcode' => '26001002', 'follow_no' => 2, 'sample_type' => 'R', 'leg_type' => '-']);

        $selected = app(SelectManualPrintSamples::class)->handle([
            'bar_range_start' => 'R1', 'bar_range_stop' => 'R2', 'print_rodac_samples' => true,
        ])->pluck('id')->all();

        $this->assertSame([$first->id, $last->id], $selected);
    }

    #[DataProvider('invalidRanges')]
    public function test_invalid_range_never_selects_unrelated_samples(string $stop, string $type, string $matrix): void
    {
        $this->sample(['barcode' => '26000001', 'leg_type' => '-']);
        $this->sample(['barcode' => '26000002', 'follow_no' => 2, 'sample_type' => $type, 'leg_type' => $matrix]);
        $this->expectException(ValidationException::class);

        app(SelectManualPrintSamples::class)->handle(['bar_range_start' => '26000001', 'bar_range_stop' => $stop, 'print_normal_samples' => true]);
    }

    public static function invalidRanges(): array
    {
        return [
            'missing endpoint' => ['99999999', 'S', '-'],
            'mixed shorthand' => ['2', 'S', '-'],
            'mixed type' => ['26000002', 'L', '-'],
            'mixed matrix' => ['26000002', 'S', 'A'],
        ];
    }

    #[DataProvider('dateRanges')]
    public function test_date_ranges_preserve_legacy_fields_and_final_day_cutoff(string $type): void
    {
        $start = CarbonImmutable::parse('2026-10-14')->getTimestamp();
        $selected = $this->sample(['date_registered' => (string) $start, 'sample_innoculated' => (string) $start, 'custom_fields' => '{"collected":"14-10-2026"}']);
        $selected->project()->update(['project_date' => $start, 'custom_fields' => '{"collected":"14-10-2026"}']);
        $outside = $this->sample(['barcode' => '26000002', 'date_registered' => (string) ($start + 23 * 3600 + 1), 'sample_innoculated' => (string) ($start + 23 * 3600 + 1), 'custom_fields' => '{"collected":"15-10-2026"}']);
        $outside->project()->update(['project_date' => $start, 'custom_fields' => '{"collected":"15-10-2026"}']);
        SampleField::create(['name' => 'collected', 'alias' => 'Monstername', 'type' => 'date', 'std_value' => '', 'position' => 1]);
        ProjectField::create(['name' => 'collected', 'alias' => 'Monstername', 'type' => 'date', 'std_value' => '', 'position' => 1]);

        $ids = app(SelectManualPrintSamples::class)->handle([
            'mode' => 'date_range', 'range_start' => '2026-10-14', 'range_stop' => '2026-10-14', 'range_type_select' => $type,
        ])->pluck('id')->all();

        $this->assertSame([$selected->id], $ids);
    }

    public static function dateRanges(): array
    {
        return [['0'], ['i'], ['s_collected'], ['p_collected']];
    }

    public function test_page_provides_profiles_defaults_and_only_date_metadata(): void
    {
        $profile = PrintProfile::factory()->create();
        $label = LabelDesign::factory()->create();
        Cvar::create(['cvar' => 'standard_extra_label', 'value' => (string) $label->id, 'default' => '0', 'description' => 'Extra label']);
        SampleField::create(['name' => 'collected', 'alias' => 'Monstername', 'type' => 'date', 'std_value' => '', 'position' => 1]);
        SampleField::create(['name' => 'notes', 'alias' => 'Notitie', 'type' => 'text', 'std_value' => '', 'position' => 2]);
        $this->actingAs($this->operator());

        $this->get(route('printing.index'))->assertViewIs('printing.index')
            ->assertViewHas('options', fn (array $options): bool => $options['profiles']->sole()->is($profile)
                && $options['extraLabel'] === $label->id && array_column($options['dateTypes'], 'id') === ['0', 'i', 's_collected']);
    }

    #[DataProvider('printerOverrides')]
    public function test_scan_combines_multiple_profiles_sample_copies_and_extra_labels_on_default_or_override_printers(bool $override): void
    {
        [$sample, $sampleDesign, $analysisDesign, $printer] = $this->printingFixtures();
        $otherPrinter = Printer::factory()->create();
        $extra = LabelDesign::factory()->create(['zpl' => 'EXTRA:{sample_barcode}', 'default_printer' => $otherPrinter->id]);
        $first = PrintProfile::factory()->create(['profile' => "10 SAMPLE PRINT $sampleDesign->id PRINTER $printer->id COPIES 2"]);
        $second = PrintProfile::factory()->create(['profile' => "10 SAMPLE PRINT $extra->id PRINTER $otherPrinter->id COPIES 1"]);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, [
            'print_sample_amount' => 2, 'also_print' => true, 'also_print_amount' => 3,
            'also_print_selection' => $extra->id, 'profile_ids' => [$first->id, $second->id],
            'printer' => $override ? $printer->id : 0,
        ]))->assertAccepted()->assertJsonPath('total_labels', 8)->assertJsonPath('sample_count', 1);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, $override ? 1 : 2);
        $spools = Queue::pushed(SendLabelPrinterSpoolJob::class)->keyBy('printerId');
        $this->assertSame(4, substr_count($spools[$printer->id]->zpl, 'S260001|'));
        $this->assertSame(4, substr_count($spools[$override ? $printer->id : $otherPrinter->id]->zpl, 'EXTRA:'));
    }

    public static function printerOverrides(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('duplicateBarcodes')]
    public function test_duplicate_scans_print_the_sample_analysis_or_specific_plate(string $suffix, int $count, string $needle): void
    {
        [$sample] = $this->printingFixtures();
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, [
            'barcode' => $sample->barcode.$suffix, 'duplicate_label' => true, 'print_sample' => false,
        ]))->assertAccepted()->assertJsonPath('total_labels', $count);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $this->assertStringContainsString($needle, Queue::pushed(SendLabelPrinterSpoolJob::class)->sole()->zpl);
    }

    public static function duplicateBarcodes(): array
    {
        return [
            'sample' => ['', 1, 'S260001|'],
            'analysis' => ['.1', 2, 'S260001.1.1'],
            'plate' => ['.1.2', 1, 'S260001.1.2'],
        ];
    }

    public function test_advanced_type_filter_restricts_sample_labels_but_not_extras_or_profiles(): void
    {
        [$sample, $design, $analysisDesign, $printer] = $this->printingFixtures();
        $type = AssayType::create(['name' => 'Ander type', 'description' => '', 'added_by' => 1, 'added_date' => '0']);
        $extra = LabelDesign::factory()->create(['default_printer' => $printer->id, 'zpl' => 'EXTRA']);
        $profile = PrintProfile::factory()->create(['profile' => "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 3"]);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, [
            'assay_type_select' => $type->id, 'also_print' => true, 'also_print_amount' => 1,
            'also_print_selection' => $extra->id, 'profile_ids' => [$profile->id],
        ]))->assertAccepted()->assertJsonPath('total_labels', 4);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $this->assertSame(3, substr_count(Queue::pushed(SendLabelPrinterSpoolJob::class)->sole()->zpl, 'S260001|'));
    }

    public function test_date_override_renders_sample_profile_and_plate_dates_without_updating_inoculation(): void
    {
        [$sample, $design, $analysisDesign, $printer] = $this->printingFixtures();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00:00'));
        $profile = PrintProfile::factory()->create(['profile' => "10 ANALYSIS ANY LABEL $analysisDesign->id PRINTER $printer->id COPIES 1"]);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, ['date_offset' => '2026-10-16', 'profile_ids' => [$profile->id]]))
            ->assertAccepted()->assertJsonPath('total_labels', 3);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $zpl = Queue::pushed(SendLabelPrinterSpoolJob::class)->sole()->zpl;
        $this->assertStringContainsString('S260001|16-10-2026|Operator', $zpl);
        $this->assertStringContainsString('S260001.1.1|16-10-2026|ZO', $zpl);
        $this->assertSame('', $sample->fresh()->sample_innoculated);
    }

    #[DataProvider('rangeModes')]
    public function test_ranges_queue_all_selected_samples_and_include_last_barcode(string $mode): void
    {
        [$sample, $design, $analysisDesign, $printer] = $this->printingFixtures();
        $date = CarbonImmutable::parse('2026-10-14')->getTimestamp();
        $sample->update(['date_registered' => (string) $date]);
        $this->sample(['barcode' => 'S260002', 'date_registered' => (string) $date]);
        $this->sample(['barcode' => 'S260003', 'date_registered' => (string) ($date + 86400)]);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, [
            'mode' => $mode, 'bar_range_start' => 'S260001', 'bar_range_stop' => 'S260002', 'print_normal_samples' => true,
            'range_type_select' => '0', 'range_start' => '2026-10-14', 'range_stop' => '2026-10-14',
        ]))->assertAccepted()->assertJsonPath('sample_count', 2)->assertJsonPath('total_labels', 2);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $this->assertStringContainsString('S260002|', Queue::pushed(SendLabelPrinterSpoolJob::class)->sole()->zpl);
    }

    public static function rangeModes(): array
    {
        return [['date_range'], ['sample_range']];
    }

    public function test_large_plan_requires_confirmation_counts_copies_and_uses_single_use_snapshot(): void
    {
        [$sample, $design, $analysisDesign, $printer] = $this->printingFixtures();
        Cvar::create(['cvar' => 'MAX_LABEL_PRINT', 'value' => '2', 'default' => '250', 'description' => 'Limit']);
        $this->actingAs($this->operator());
        Queue::fake();

        $response = $this->postJson(route('printing.store'), $this->payload($sample, ['print_sample_amount' => 3]))
            ->assertConflict()->assertJsonPath('confirmation_required', true)->assertJsonPath('total_labels', 3);

        Queue::assertNothingPushed();
        $design->update(['zpl' => 'CHANGED']);
        $token = $response->json('confirmation_token');
        $this->postJson(route('printing.store'), ['confirmation_token' => $token])->assertAccepted()->assertJsonPath('total_labels', 3);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $this->assertSame(3, substr_count(Queue::pushed(SendLabelPrinterSpoolJob::class)->sole()->zpl, 'S260001|'));
        $this->postJson(route('printing.store'), ['confirmation_token' => $token])->assertUnprocessable()->assertJsonValidationErrors('confirmation_token');
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
    }

    public function test_confirmation_cannot_be_used_by_another_operator_or_after_expiry(): void
    {
        [$sample] = $this->printingFixtures();
        Cvar::create(['cvar' => 'MAX_LABEL_PRINT', 'value' => '0', 'default' => '250', 'description' => 'Limit']);
        $operator = $this->operator();
        $this->actingAs($operator);
        Queue::fake();
        $token = $this->postJson(route('printing.store'), $this->payload($sample))->assertConflict()->json('confirmation_token');
        $this->actingAs($this->operator());

        $this->postJson(route('printing.store'), ['confirmation_token' => $token])->assertUnprocessable()->assertJsonValidationErrors('confirmation_token');
        $this->actingAs($operator);
        $this->travel(11)->minutes();
        $this->postJson(route('printing.store'), ['confirmation_token' => $token])->assertUnprocessable()->assertJsonValidationErrors('confirmation_token');

        Queue::assertNothingPushed();
    }

    public function test_at_limit_prints_without_confirmation(): void
    {
        [$sample] = $this->printingFixtures();
        Cvar::create(['cvar' => 'MAX_LABEL_PRINT', 'value' => '2', 'default' => '250', 'description' => 'Limit']);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, ['print_sample_amount' => 2]))->assertAccepted();

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
    }

    public function test_large_aggregate_is_confirmable_without_imposing_a_new_profile_limit_on_manual_printing(): void
    {
        [$sample, $design, $analysisDesign, $printer] = $this->printingFixtures();
        $design->update(['zpl' => 'LABEL']);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, [
            'print_sample_amount' => 9999, 'also_print' => true, 'also_print_selection' => $design->id, 'also_print_amount' => 2,
        ]))->assertConflict()->assertJsonPath('confirmation_required', true)->assertJsonPath('total_labels', 10001);

        Queue::assertNothingPushed();
    }

    public function test_broken_profile_prevents_partial_dispatch_of_other_selected_labels(): void
    {
        [$sample] = $this->printingFixtures();
        $profile = PrintProfile::factory()->create(['profile' => '10 GOTO 99']);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, ['profile_ids' => [$profile->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('options');

        Queue::assertNothingPushed();
    }

    public function test_disabled_printing_reports_no_output_and_does_not_queue(): void
    {
        [$sample] = $this->printingFixtures();
        Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '1', 'default' => '0', 'description' => 'Disable']);
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample))->assertAccepted()->assertJsonPath('disabled', true);

        Queue::assertNothingPushed();
    }

    #[DataProvider('invalidPrintOptions')]
    public function test_invalid_options_return_422_without_queueing(array $changes, string $error): void
    {
        [$sample] = $this->printingFixtures();
        $this->actingAs($this->operator());
        Queue::fake();

        $this->postJson(route('printing.store'), $this->payload($sample, $changes))->assertUnprocessable()->assertJsonValidationErrors($error);

        Queue::assertNothingPushed();
    }

    public static function invalidPrintOptions(): array
    {
        return [
            'unknown barcode' => [['barcode' => 'unknown'], 'barcode'],
            'unknown analysis' => [['barcode' => 'S260001.99', 'duplicate_label' => true, 'print_sample' => false], 'options'],
            'unknown plate' => [['barcode' => 'S260001.1.99', 'duplicate_label' => true, 'print_sample' => false], 'options'],
            'copies zero' => [['print_sample_amount' => 0], 'print_sample_amount'],
            'printer missing' => [['printer' => 99999], 'printer'],
            'design missing' => [['sample_label_select' => 99999], 'sample_label_select'],
            'profile missing' => [['profile_ids' => [99999]], 'profile_ids.0'],
            'no options' => [['print_sample' => false], 'options'],
            'duplicate with sample labels' => [['duplicate_label' => true], 'duplicate_label'],
            'duplicate in range' => [['mode' => 'sample_range', 'bar_range_start' => 'S260001', 'bar_range_stop' => 'S260001', 'print_normal_samples' => true, 'duplicate_label' => true], 'duplicate_label'],
            'invalid date' => [['date_offset' => '2026-02-30'], 'date_offset'],
            'reversed dates' => [['mode' => 'date_range', 'range_type_select' => 'i', 'range_start' => '2026-10-15', 'range_stop' => '2026-10-14'], 'range_stop'],
            'unlisted date field' => [['mode' => 'date_range', 'range_type_select' => 's_secret', 'range_start' => '2026-10-14', 'range_stop' => '2026-10-14'], 'range_type_select'],
            'range no types' => [['mode' => 'sample_range', 'bar_range_start' => 'S260001', 'bar_range_stop' => 'S260001'], 'print_normal_samples'],
        ];
    }

    #[DataProvider('printEndpoints')]
    public function test_guests_are_redirected_to_login(string $method): void
    {
        Queue::fake();

        $this->call($method, '/laboratory/printing')->assertRedirect(route('login'));

        Queue::assertNothingPushed();
    }

    #[DataProvider('printEndpoints')]
    public function test_users_without_scan_to_print_permission_receive_403(string $method): void
    {
        $this->actingAs(User::factory()->create(['enabled' => true]));
        Queue::fake();

        $this->call($method, '/laboratory/printing')->assertForbidden();

        Queue::assertNothingPushed();
    }

    public static function printEndpoints(): array
    {
        return [['GET'], ['POST']];
    }

    private function operator(): User
    {
        Permission::findOrCreate('scan-to-print.use', 'web');
        $role = Role::findOrCreate('Print operator', 'web');
        $role->givePermissionTo('scan-to-print.use');
        $user = User::factory()->create(['name' => 'Operator', 'enabled' => true]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{Sample, LabelDesign, LabelDesign, Printer} */
    private function printingFixtures(): array
    {
        $sample = $this->sample();
        $printer = Printer::factory()->create();
        $sampleDesign = LabelDesign::factory()->create(['default_sample' => true, 'default_printer' => $printer->id, 'zpl' => '{sample_barcode}|{date_today}|{current_user}']);
        $analysisDesign = LabelDesign::factory()->create(['default_analysis' => true, 'default_printer' => $printer->id, 'zpl' => '{assay_plate_barcode}|{date_today}|{assay_predicted_end_day}']);
        $type = AssayType::create(['name' => 'Water', 'description' => '', 'added_by' => 1, 'added_date' => '0']);
        $assay = Assay::create([
            'original_id' => 101, 'name' => 'Analyse', 'type_base' => $type->id, 'media_id' => '{}', 'dillution' => 1, 'replicates' => 1,
            'confirmation' => 0, 'confirmation_script' => '{}', 'type' => 1, 'meta_assays' => '', 'max_count' => 300,
            'min_count' => 10, 'script' => '', 'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '',
        ]);
        $analysis = SampleAnalysis::create([
            'profile_group' => 1, 'sample' => $sample->id, 'follow_number' => 1, 'profile' => 0, 'assay' => 0,
            'assay_base' => $assay->id, 'original_assay_base' => 101, 'roaming_id' => 0, 'project' => $sample->project, 'predicted_end' => 1,
        ]);

        foreach ([1, 2] as $follow) {
            Result::create(['sample' => $sample->id, 'sa_id' => $analysis->id, 'follow_no' => $follow, 'profile' => 0,
                'assay' => 0, 'assay_base' => $assay->id, 'roaming_id' => 0, 'df' => '1', 'rep' => $follow - 1, 'data' => []]);
        }

        return [$sample, $sampleDesign, $analysisDesign, $printer];
    }

    /** @return array<string, mixed> */
    private function payload(Sample $sample, array $changes = []): array
    {
        return array_replace(['mode' => 'scan', 'barcode' => $sample->barcode, 'date_offset' => now()->format('Y-m-d'),
            'print_sample' => true, 'print_sample_amount' => 1, 'profile_ids' => [], 'printer' => 0], $changes);
    }

    /** @param array<string, mixed> $attributes */
    private function sample(array $attributes = []): Sample
    {
        $client = Client::create(['name' => 'Laboratorium', 'active' => 1]);
        $project = Project::create(['client' => $client->id, 'subclient' => 0, 'project_name' => 'Printen', 'lock_pass' => 'private']);
        $procedure = SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0]);

        return Sample::create(array_replace([
            'barcode' => 'S260001', 'follow_no' => 1, 'description' => 'Water', 'sampling_method' => $procedure->id,
            'date_registered' => '1791936000', 'registered_by' => 1, 'client' => $client->id, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => 1791936000,
            'sample_innoculated' => '', 'stored_in' => '', 'diluted_at' => '1', 'analyses_data' => '[]',
            'sample_type' => 'S', 'leg_type' => '',
        ], $attributes))->refresh();
    }
}
