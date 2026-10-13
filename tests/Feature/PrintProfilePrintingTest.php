<?php

namespace Tests\Feature;

use App\Actions\PrintProfiles\BuildPrintProfilePlan;
use App\Actions\PrintProfiles\QueuePrintProfile;
use App\Jobs\RunPrintProfileJob;
use App\Jobs\SendLabelPrinterSpoolJob;
use App\Models\Assay;
use App\Models\AssayType;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use App\Models\Project;
use App\Models\Result;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleProcedure;
use App\Support\Printing\LabelPrinterTransport;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrintProfilePrintingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sample_labels_render_content_copies_and_override_without_printing(): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        $override = Printer::factory()->create();
        $profile = PrintProfile::factory()->create(['profile' => "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 2\n20 EXIT"]);
        Queue::fake();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

        $plan = app(BuildPrintProfilePlan::class)->handle($profile, $sample, $override->id, new DateTimeImmutable('2026-10-13T10:00:00+00:00'), 1, 'Lab user');

        $this->assertSame(2, $plan['total_labels']);
        $this->assertSame($override->id, $plan['labels'][0]['print_to']);
        $this->assertSame('M260001|{assay_plate_barcode}|14-10-2026|Lab user', $plan['labels'][0]['zpl']);
        $this->assertSame([10, 20], array_column($plan['trace'], 'line'));
        Queue::assertNothingPushed();
    }

    #[DataProvider('analysisModes')]
    public function test_analysis_filters_preserve_legacy_selection(string $mode, array $expected): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        $profile = new PrintProfile(['profile' => "10 ANALYSIS $mode LABEL $design->id PRINTER $printer->id COPIES 1"]);

        $plan = app(BuildPrintProfilePlan::class)->handle($profile, $sample);

        $this->assertSame(array_map(fn (int $index): int => $analyses[$index]->id, $expected), array_column($plan['labels'], 'sample_analysis_id'));
    }

    #[DataProvider('sampleConditions')]
    public function test_sample_conditions_jump_to_explicit_line_numbers(string $condition, int $expectedCopies): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        $profile = new PrintProfile(['profile' => "10 SAMPLE $condition GOTO 45 ELSE 90\n45 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 1\n50 EXIT\n90 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 2\n100 EXIT"]);

        $plan = app(BuildPrintProfilePlan::class)->handle($profile, $sample);

        $this->assertSame($expectedCopies, $plan['total_labels']);
    }

    public function test_false_condition_without_else_falls_through_and_goto_skips_printing(): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        $profile = new PrintProfile(['profile' => "10 SAMPLE HASANY [999] GOTO 70\n20 LOG continue\n30 GOTO 70\n40 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 1\n70 EXIT"]);

        $plan = app(BuildPrintProfilePlan::class)->handle($profile, $sample);

        $this->assertSame(0, $plan['total_labels']);
        $this->assertSame([10, 20, 30, 70], array_column($plan['trace'], 'line'));
    }

    public function test_single_uses_result_follow_number_one_and_preserves_boolean_not_quirk(): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        $this->createResult($sample, $analyses[0], 0.01, 8);
        $profile = new PrintProfile(['profile' => "10 ANALYSIS ONLY [101] LABEL $design->id PRINTER $printer->id COPIES 1 SINGLE\n20 ANALYSIS NOT [102,103] LABEL $design->id PRINTER $printer->id COPIES 1 SINGLE"]);

        $plan = app(BuildPrintProfilePlan::class)->handle($profile, $sample);

        $this->assertSame(3, $plan['total_labels']);
        $this->assertStringContainsString('M260001.1.1', $plan['labels'][0]['zpl']);
        $this->assertStringContainsString('M260001.1.8', $plan['labels'][1]['zpl']);
        $this->assertStringContainsString('M260001.1.1', $plan['labels'][2]['zpl']);
    }

    public function test_queued_execution_snapshots_script_and_sends_through_existing_transport_jobs(): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        $script = "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 2";
        $profile = PrintProfile::factory()->create(['profile' => $script]);
        Queue::fake([RunPrintProfileJob::class, SendLabelPrinterSpoolJob::class]);
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldReceive('send')->once()->withArgs(fn (Printer $target, string $zpl): bool => $target->is($printer) && substr_count($zpl, 'M260001|') === 2));

        app(QueuePrintProfile::class)->handle($profile, $sample, currentUser: 'Operator');
        $profile->update(['profile' => '10 EXIT']);
        $this->app->call([Queue::pushed(RunPrintProfileJob::class)->sole(), 'handle']);

        Queue::assertPushedOn('printing', RunPrintProfileJob::class, fn (RunPrintProfileJob $job): bool => $job->profileScript === $script && $job->sampleId === $sample->id && $job->currentUser === 'Operator');
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->sole(), 'handle']);
    }

    public function test_queue_action_waits_for_commit(): void
    {
        [$sample] = $this->fixtures();
        $profile = PrintProfile::factory()->create();

        DB::transaction(function () use ($profile, $sample): void {
            app(QueuePrintProfile::class)->handle($profile, $sample);
            $this->assertDatabaseCount('jobs', 0);
        });

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'printing']);
    }

    public function test_disabled_printing_does_not_dispatch_transport_jobs(): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '1', 'default' => '0', 'description' => 'Disable']);
        Queue::fake();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

        $this->app->call([new RunPrintProfileJob(1, "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 1", $sample->id), 'handle']);

        Queue::assertNothingPushed();
    }

    #[DataProvider('unsafeScripts')]
    public function test_invalid_execution_never_dispatches_partial_spools(string $suffix): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        Queue::fake();
        $script = "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 1\n$suffix";

        try {
            $this->app->call([new RunPrintProfileJob(1, $script, $sample->id), 'handle']);
            $this->fail('The unsafe script should fail.');
        } catch (InvalidArgumentException) {
            Queue::assertNothingPushed();
        }
    }

    public function test_spool_size_limit_applies_to_dry_run_before_dispatch(): void
    {
        [$sample, $analyses, $design, $printer] = $this->fixtures();
        config(['printing.max_spool_bytes' => 1]);
        $this->expectException(InvalidArgumentException::class);

        app(BuildPrintProfilePlan::class)->handle(new PrintProfile(['profile' => "10 SAMPLE PRINT $design->id PRINTER $printer->id COPIES 1"]), $sample);
    }

    public static function analysisModes(): array
    {
        return [
            'any' => ['ANY', [0, 1, 2]],
            'only' => ['ONLY [101]', [0]],
            'not' => ['NOT [101]', [1, 2]],
            'confirmed' => ['ONLYWITHCONFIRMATION [101,102]', [1]],
            'group' => ['GROUP [7]', [0, 2]],
            'legacy notgroup uses assay IDs' => ['NOTGROUP [101]', [1, 2]],
            'current revision is not original' => ['ONLY [1]', []],
        ];
    }

    public static function sampleConditions(): array
    {
        return [
            'has any' => ['HASANY [101]', 1],
            'no matching assay' => ['HASANY [999]', 2],
            'has all' => ['HASALL [101,102]', 1],
            'missing one' => ['HASALL [101,999]', 2],
            'confirmed' => ['HASANYWITHCONFIRMATION [102]', 1],
            'not confirmed' => ['HASANYWITHCONFIRMATION [101]', 2],
            'more than one' => ['HASONLYONE', 2],
        ];
    }

    public static function unsafeScripts(): array
    {
        return [
            'loop' => ['20 GOTO 20'],
            'missing design' => ['20 SAMPLE PRINT 999999 PRINTER 1 COPIES 1'],
            'missing printer' => ['20 SAMPLE PRINT 1 PRINTER 999999 COPIES 1'],
            'label limit' => ["20 SAMPLE PRINT 1 PRINTER 1 COPIES 9999\n30 GOTO 20"],
        ];
    }

    /** @return array{Sample, list<SampleAnalysis>, LabelDesign, Printer} */
    private function fixtures(): array
    {
        $client = Client::create(['name' => 'Waterbedrijf', 'active' => 1]);
        $project = Project::create(['client' => $client->id, 'subclient' => 0, 'project_name' => 'Water', 'lock_pass' => 'private']);
        $procedure = SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0]);
        $sample = Sample::create([
            'barcode' => 'M260001', 'follow_no' => 1000, 'description' => 'Water', 'sampling_method' => $procedure->id,
            'date_registered' => '1788998400', 'registered_by' => 1, 'client' => $client->id, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'stored_in' => 'A', 'diluted_at' => '1', 'analyses_data' => '[]',
        ])->refresh();
        $analyses = [];

        foreach ([[101, 7, 0], [102, 8, 1], [103, 7, 1]] as $index => [$original, $type, $confirmed]) {
            AssayType::firstOrCreate(['id' => $type], ['name' => 'Type '.$type, 'description' => '', 'added_by' => 1, 'added_date' => '0']);
            $assay = Assay::create([
                'original_id' => $original, 'name' => 'Assay '.$original, 'type_base' => $type, 'media_id' => '{}',
                'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
                'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
                'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '',
            ]);
            $analysis = SampleAnalysis::create([
                'profile_group' => 1, 'sample' => $sample->id, 'follow_number' => $index + 1, 'profile' => 0, 'assay' => 0,
                'assay_base' => $assay->id, 'original_assay_base' => $original, 'roaming_id' => 0,
                'project' => $project->id, 'predicted_end' => 1, 'conf_requested' => $confirmed,
            ]);
            $this->createResult($sample, $analysis, 1, 1);
            $analyses[] = $analysis;
        }

        return [$sample, $analyses, LabelDesign::factory()->create(['zpl' => '{sample_barcode}|{assay_plate_barcode}|{date_today}|{current_user}']), Printer::factory()->create()];
    }

    private function createResult(Sample $sample, SampleAnalysis $analysis, float $dilution, int $follow): void
    {
        Result::create([
            'sample' => $sample->id, 'sa_id' => $analysis->id, 'follow_no' => $follow, 'profile' => 0,
            'assay' => 0, 'assay_base' => $analysis->assay_base, 'roaming_id' => 0, 'df' => (string) $dilution, 'rep' => 0, 'data' => [],
        ]);
    }
}
