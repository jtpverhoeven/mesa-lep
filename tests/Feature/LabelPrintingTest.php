<?php

namespace Tests\Feature;

use App\Actions\Cvars\SetCvar;
use App\Actions\Printing\BuildLabelPrintPlan;
use App\Actions\Printing\DispatchLabelEvents;
use App\Actions\Printing\SendLabelPrintPlan;
use App\Actions\SampleAnalyses\AddResearchProfileToSample;
use App\Actions\SampleAnalyses\AddResearchToEmptySamples;
use App\Actions\SampleAnalyses\AddRoamingAnalysisToSample;
use App\Actions\SampleBuffers\CommitSampleBuffers;
use App\Actions\SampleBuffers\CreateSampleBuffer;
use App\Actions\SampleBuffers\UpdateSampleBuffers;
use App\Actions\Samples\CreateBulkSamples;
use App\Actions\Samples\CreateRegisteredSample;
use App\Actions\Samples\CreateSampleWithAnalyses;
use App\Actions\Samples\UpdateLookupResearch;
use App\Jobs\SendLabelPrinterSpoolJob;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\AssayType;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\Project;
use App\Models\ResearchProfile;
use App\Models\Result;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleBuffer;
use App\Models\SampleProcedure;
use App\Models\User;
use App\Support\Printing\LabelPrinterTransport;
use App\Support\Printing\TcpLabelPrinterTransport;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LabelPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_sample_queues_its_configured_registration_labels(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $existing = $this->sample('M260001');
        $design = LabelDesign::factory()->create(['zpl' => '^XA^FD{sample_barcode}^FS^XZ']);
        $event = LabelEvent::factory()->for($design, 'labelDesign')->create(['copies' => 2]);

        $sample = app(CreateRegisteredSample::class)->handle([
            'client' => $existing->client,
            'registered_by' => $existing->registered_by,
        ], Project::findOrFail($existing->project));

        $this->assertModelExists($sample);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->printerId === $event->print_to
            && $job->eventIds === [$event->id]
            && $job->zpl === str_repeat('^XA^FD'.$sample->barcode.'^FS^XZ', 2));
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
    }

    public function test_event_dispatch_batches_registration_labels_and_renders_the_current_operator(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $user = User::factory()->create(['name' => 'Registration operator']);
        $this->actingAs($user);
        $first = $this->sample('M260001');
        $second = $this->sample('M260002', $first->project);
        $design = LabelDesign::factory()->create(['zpl' => '{sample_barcode}|{current_user};']);
        $event = LabelEvent::factory()->for($design, 'labelDesign')->create();

        app(DispatchLabelEvents::class)->handle([$first, $second]);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->printerId === $event->print_to
            && $job->zpl === 'M260001|Registration operator;M260002|Registration operator;'
            && $job->activityContext['user_id'] === $user->id);
    }

    public function test_automatic_printing_logs_matching_event_ids_with_the_sample_that_triggered_them(): void
    {
        $this->withPrintingLogs(function (): void {
            Queue::fake([SendLabelPrinterSpoolJob::class]);
            $user = User::factory()->create(['name' => 'Registration operator']);
            $this->actingAs($user);
            $sample = $this->sample('M260001');
            $design = LabelDesign::factory()->create(['zpl' => '^XA^FDPRIVATE LABEL^FS^XZ']);
            $printer = Printer::factory()->create();
            $always = LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')->create(['copies' => 2]);
            $matching = LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')
                ->conditional(['type' => 'sClient', 'inspect_value' => (string) $sample->client, 'negative' => '0'])->create();
            $unmatched = LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')
                ->conditional(['type' => 'mType', 'inspect_value' => 'R', 'negative' => '0'])->create();

            app(DispatchLabelEvents::class)->handle([$sample]);

            $activity = $this->printingLog();
            $this->assertStringContainsString('Hit label event rules: '.$always->id.', '.$matching->id.'.', $activity);
            $this->assertStringContainsString('"event_ids":['.$always->id.','.$matching->id.']', $activity);
            $this->assertStringContainsString('"sample_id":'.$sample->id, $activity);
            $this->assertStringContainsString('"sample_barcode":"M260001"', $activity);
            $this->assertStringContainsString('"event_group":1', $activity);
            $this->assertStringContainsString('"user_id":'.$user->id, $activity);
            $this->assertStringContainsString('"label_ids":['.$design->id.']', $activity);
            $this->assertStringContainsString('"printer_ids":['.$printer->id.']', $activity);
            $this->assertStringNotContainsString('"event_ids":['.$unmatched->id.']', $activity);
            $this->assertStringNotContainsString('PRIVATE LABEL', $activity);
            Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        });
    }

    public function test_rule_hit_logs_identify_analysis_and_tht_sources_even_when_printing_is_disabled(): void
    {
        $this->withPrintingLogs(function (): void {
            Queue::fake();
            Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '1', 'default' => '0', 'description' => 'Disable']);
            $sample = $this->sample('M260001');
            $analysis = app(AddRoamingAnalysisToSample::class)->handle($sample, $this->assay(), ['dillutions' => [1, 0.01], 'replicates' => 1]);
            $buffer = app(CreateSampleBuffer::class)->handle(['client' => $sample->client, 'register_as' => 'tht', 'description' => 'Water']);
            $design = LabelDesign::factory()->create(['zpl' => '^XA^FDPRIVATE LABEL^FS^XZ']);
            $analysisEvent = LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 2]);
            $thtEvent = LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 3]);

            app(DispatchLabelEvents::class)->handle([$analysis, $buffer]);

            $activity = $this->printingLog();
            $this->assertSame(1, substr_count($activity, 'Hit label event rules: '.$analysisEvent->id.'.'));
            $this->assertSame(1, substr_count($activity, 'Hit label event rules: '.$thtEvent->id.'.'));
            $this->assertStringContainsString('"event_group":2', $activity);
            $this->assertStringContainsString('"sample_analysis_id":'.$analysis->id, $activity);
            $this->assertStringContainsString('"event_group":3', $activity);
            $this->assertStringContainsString('"sample_buffer_id":'.$buffer->id, $activity);
            $this->assertStringContainsString('"tht_code":"'.$buffer->tht_code.'"', $activity);
            $this->assertStringNotContainsString('PRIVATE LABEL', $activity);
            Queue::assertNothingPushed();
        });
    }

    public function test_unmatched_automation_events_do_not_write_rule_hit_entries(): void
    {
        $this->withPrintingLogs(function (): void {
            Queue::fake();
            $sample = $this->sample('M260001');
            LabelEvent::factory()->conditional(['type' => 'mType', 'inspect_value' => 'R', 'negative' => '0'])->create();

            $spools = app(DispatchLabelEvents::class)->handle([$sample]);

            $this->assertSame([], $spools);
            $this->assertSame('', $this->printingLog());
            Queue::assertNothingPushed();
        });
    }

    public function test_adding_a_standalone_analysis_prints_matching_rules_after_dilutions_and_replicates_exist(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $sample = $this->sample('M260001');
        $assay = $this->assay();
        $design = LabelDesign::factory()->create(['zpl' => '{assay_plate_barcode}|{assay_dillution_numeric};']);
        $event = LabelEvent::factory()->for($design, 'labelDesign')
            ->conditional(['type' => 'assayIs', 'inspect_value' => (string) $assay->original_id, 'negative' => '0'], 2)->create(['copies' => 2]);
        LabelEvent::factory()->for($design, 'labelDesign')
            ->conditional(['type' => 'assayIs', 'inspect_value' => '999999', 'negative' => '0'], 2)->create();

        $analysis = app(AddRoamingAnalysisToSample::class)->handle($sample, $assay, ['dillutions' => [1, 0.01], 'replicates' => 1]);

        $this->assertCount(4, $analysis->results);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->eventIds === [$event->id]
            && $job->zpl === 'M260001.1.3|0.01;M260001.1.3|0.01;M260001.1.4|0.01;M260001.1.4|0.01;M260001.1.1|1;M260001.1.1|1;M260001.1.2|1;M260001.1.2|1;');
    }

    public function test_adding_a_research_profile_batches_all_new_analysis_labels(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $sample = $this->sample('M260001');
        $profile = ResearchProfile::create(['original_id' => 1, 'name' => 'Water profile', 'global' => 1, 'client' => 0, 'active' => 1]);
        foreach ([1, 2] as $order) {
            $assay = $this->assay();
            AssayProfile::create([
                'research_profile' => $profile->id, 'assay' => $assay->id, 'dillutions' => [1],
                'replicates' => 0, 'reference' => [], 'hidden' => 0, 'project_order' => $order,
            ]);
        }
        $design = LabelDesign::factory()->create(['zpl' => '{assay_plate_barcode};']);
        $event = LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 2]);

        $analyses = app(AddResearchProfileToSample::class)->handle($sample, $profile);

        $this->assertCount(2, $analyses);
        $this->assertDatabaseCount('results', 2);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->eventIds === [$event->id]
            && $job->zpl === 'M260001.1.1;M260001.2.1;');
    }

    public function test_registration_with_analyses_spools_sample_and_plate_labels_once(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $existing = $this->sample('M260001');
        $assay = $this->assay();
        $printer = Printer::factory()->create();
        $sampleDesign = LabelDesign::factory()->create(['zpl' => 'S:{sample_barcode};']);
        $analysisDesign = LabelDesign::factory()->create(['zpl' => 'A:{assay_plate_barcode};']);
        $sampleEvent = LabelEvent::factory()->for($printer, 'printer')->for($sampleDesign, 'labelDesign')->create();
        $analysisEvent = LabelEvent::factory()->for($printer, 'printer')->for($analysisDesign, 'labelDesign')->create(['event_group' => 2]);

        $sample = app(CreateSampleWithAnalyses::class)->handle([
            'client' => $existing->client, 'project' => $existing->project, 'registered_by' => 1,
            'analyses' => [['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['dillutions' => [1], 'replicates' => 0]]],
        ]);

        $this->assertCount(1, $sample->analyses);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->eventIds === [$sampleEvent->id, $analysisEvent->id]
            && $job->zpl === 'S:'.$sample->barcode.';A:'.$sample->barcode.'.1.1;');
    }

    public function test_bulk_registration_prints_once_after_the_creation_callback_finishes(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $existing = $this->sample('M260001');
        $design = LabelDesign::factory()->create(['zpl' => '{sample_description};']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();

        $samples = app(CreateBulkSamples::class)->handle([
            'client' => $existing->client, 'registered_by' => 1,
            'samples' => [['description' => 'First'], ['description' => 'Second']],
        ], fn (Sample $sample) => $sample->update(['description' => $sample->description.' finalized']));

        $this->assertCount(2, $samples);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->zpl === 'First finalized;Second finalized;');
    }

    public function test_creating_a_tht_buffer_prints_its_assigned_code_without_registering_a_sample(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $design = LabelDesign::factory()->create(['zpl' => '{sample_tht_code}|{sample_sample_name};']);
        $event = LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 3]);
        LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 1]);

        $buffer = app(CreateSampleBuffer::class)->handle([
            'client' => 1, 'register_as' => 'tht', 'description' => 'THT water',
            'sampling_date' => '2026-10-14', 'tht_date' => '2026-11-04',
        ]);

        $this->assertNotEmpty($buffer->tht_code);
        $this->assertDatabaseCount('samples', 0);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->eventIds === [$event->id]
            && $job->zpl === $buffer->tht_code.'|THT water;');
    }

    public function test_normal_buffers_do_not_print_registration_or_tht_labels(): void
    {
        Queue::fake();
        LabelEvent::factory()->create();
        LabelEvent::factory()->create(['event_group' => 3]);

        $buffer = app(CreateSampleBuffer::class)->handle(['client' => 1, 'register_as' => 'buffer', 'description' => 'Staged water']);

        $this->assertModelExists($buffer);
        Queue::assertNothingPushed();
    }

    public function test_tht_approval_batches_only_newly_authorized_buffers_and_does_not_print_twice(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $first = app(CreateSampleBuffer::class)->handle(['client' => 1, 'register_as' => 'buffer', 'description' => 'First']);
        $second = app(CreateSampleBuffer::class)->handle(['client' => 1, 'register_as' => 'buffer', 'description' => 'Second']);
        $first->update(['tht' => 1]);
        $second->update(['tht' => 1]);
        $design = LabelDesign::factory()->create(['zpl' => '{sample_sample_name}|{sample_tht_code};']);
        LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 3]);
        $ids = [$first->id, $second->id];
        $receipt = ['receive_date' => '2026-10-14', 'receive_time' => '10:30'];

        $count = app(UpdateSampleBuffers::class)->authorizeTht($ids, $receipt);
        $repeated = app(UpdateSampleBuffers::class)->authorizeTht($ids, $receipt);

        $this->assertSame(2, $count);
        $this->assertSame(0, $repeated);
        $this->assertNotEmpty($first->fresh()->tht_code);
        $this->assertNotEmpty($second->fresh()->tht_code);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->zpl === 'First|'.$first->fresh()->tht_code.';Second|'.$second->fresh()->tht_code.';');
    }

    public function test_buffer_commits_print_transferred_fields_once_after_hydration(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $existing = $this->sample('M260001');
        $first = app(CreateSampleBuffer::class)->handle(['client' => $existing->client, 'register_as' => 'buffer', 'description' => 'First']);
        $second = app(CreateSampleBuffer::class)->handle(['client' => $existing->client, 'register_as' => 'buffer', 'description' => 'Second']);
        $first->update(['tht_code' => 'THT-1', 'source' => 3, 'meta' => ['Details' => 'Hydrated water']]);
        $second->update(['tht_code' => 'THT-2', 'source' => 1]);
        $design = LabelDesign::factory()->create(['zpl' => '{sample_description}|{sample_tht_code}|{sample_source};']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();

        $samples = app(CommitSampleBuffers::class)->handle([$first->id, $second->id], 1, ['receive_date' => '14-10-2026', 'receive_time' => '10:30']);

        $this->assertCount(2, $samples);
        $this->assertDatabaseCount('samplebuffers', 0);
        $this->assertDatabaseHas('metadata', ['sample' => $samples->first()->id, 'name' => 'Details', 'value' => 'Hydrated water']);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->zpl === 'First|THT-1|3;Second|THT-2|1;');
    }

    public function test_invalid_registration_labels_roll_back_new_samples_without_queuing_prints(): void
    {
        Queue::fake();
        $existing = $this->sample('M260001');
        LabelEvent::factory()->create(['print_label' => 999999]);

        try {
            app(CreateSampleWithAnalyses::class)->handle(['client' => $existing->client, 'registered_by' => 1]);
            $this->fail('Invalid label configuration should prevent registration.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('missing label design or printer', $exception->getMessage());
        }

        $this->assertDatabaseCount('samples', 1);
        $this->assertDatabaseCount('projects', 1);
        Queue::assertNothingPushed();
    }

    public function test_lookup_research_addition_batches_only_new_analyses_without_reprinting_existing_ones(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $sample = $this->sample('M260001');
        $assay = $this->assay();
        app(AddRoamingAnalysisToSample::class)->handle($sample, $assay, ['dillutions' => [1], 'replicates' => 0]);
        $design = LabelDesign::factory()->create(['zpl' => '{assay_plate_barcode};']);
        LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 2]);
        $entry = ['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['dillutions' => [1], 'replicates' => 0]];

        app(UpdateLookupResearch::class)->handle($sample, ['operation' => 'add', 'analyses' => [$entry, $entry]]);

        $this->assertCount(3, $sample->fresh()->analyses);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->zpl === 'M260001.2.1;M260001.3.1;');
    }

    public function test_adding_research_to_empty_samples_batches_labels_across_all_selected_samples(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $first = $this->sample('M260001');
        $second = $this->sample('M260002', $first->project);
        $assay = $this->assay();
        $design = LabelDesign::factory()->create(['zpl' => '{assay_plate_barcode};']);
        LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 2]);

        $count = app(AddResearchToEmptySamples::class)->handle([$first->id, $second->id], [
            ['type' => 'assay', 'assay_id' => $assay->id, 'settings' => ['dillutions' => [1], 'replicates' => 0]],
        ]);

        $this->assertSame(2, $count);
        $this->assertDatabaseCount('results', 2);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->zpl === 'M260001.1.1;M260002.1.1;');
    }

    public function test_printing_logs_rotate_after_three_files_preserve_raw_zpl_and_do_not_change_application_logging(): void
    {
        $this->withPrintingLogs(function (): void {
            foreach (['printing', 'printing_debug'] as $channel) {
                foreach ([1, 2, 3, 4] as $daysAgo) {
                    $date = (new DateTimeImmutable)->modify('-'.$daysAgo.' days')->format('Y-m-d');
                    Storage::disk('local')->put($channel.'/printing-'.$date.'.log', 'Old activity');
                }
            }

            $zpl = "^XA\n^FDTEST^FS\n^XZ";
            Log::channel('printing')->info('Label spool disabled.', ['printer_id' => 1, 'user_id' => 42]);
            Log::channel('printing_debug')->debug($zpl, ['printer_id' => 1]);
            Log::info('Unrelated application activity.');

            $this->assertCount(3, Storage::disk('local')->files('printing'));
            $this->assertCount(3, Storage::disk('local')->files('printing_debug'));
            $this->assertStringContainsString('Label spool disabled.', $this->printingLog());
            $this->assertStringContainsString('"user_id":42', $this->printingLog());
            $this->assertStringNotContainsString('^XA', $this->printingLog());
            $this->assertStringContainsString($zpl, $this->printingLog('printing_debug'));
            $this->assertStringNotContainsString('\\n', $this->printingLog('printing_debug'));
            $applicationLog = Storage::disk('local')->get('application.log');
            $this->assertStringContainsString('Unrelated application activity.', $applicationLog);
            $this->assertStringNotContainsString('Label spool', $applicationLog);
            $this->assertStringNotContainsString('^XA', $applicationLog);
        });
    }

    public function test_disabled_printing_logs_repeated_aligned_zpl_and_activity_without_contacting_printers(): void
    {
        $this->withPrintingLogs(function (): void {
            Queue::fake();
            $user = User::factory()->create(['name' => 'Print operator']);
            $this->actingAs($user);
            Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '1', 'default' => '0', 'description' => 'Disable']);
            Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => '^LH4,4', 'default' => '', 'description' => 'Alignment']);
            $zpl = "^XA\n^FDRAW OUTPUT^FS\n^XZ";
            $design = LabelDesign::factory()->create(['name' => 'Monsteretiket', 'zpl' => $zpl]);
            $printer = Printer::factory()->create(['name' => 'Balieprinter']);
            LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')->create(['copies' => 2]);
            $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

            app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));

            $activity = $this->printingLog();
            $this->assertStringContainsString('Label spool disabled.', $activity);
            $this->assertStringContainsString('"user_id":'.$user->id, $activity);
            $this->assertStringContainsString('Print operator', $activity);
            $this->assertStringContainsString('Monsteretiket', $activity);
            $this->assertStringContainsString('Balieprinter', $activity);
            $this->assertStringContainsString('"copies":2', $activity);
            $this->assertStringNotContainsString('RAW OUTPUT', $activity);
            $this->assertStringContainsString('^LH4,4'.$zpl.$zpl, $this->printingLog('printing_debug'));
            Storage::disk('local')->assertMissing('application.log');
            Queue::assertNothingPushed();
        });
    }

    public function test_enabled_printing_logs_the_original_user_through_the_queue_without_logging_raw_zpl(): void
    {
        $this->withPrintingLogs(function (): void {
            Queue::fake([SendLabelPrinterSpoolJob::class]);
            $user = User::factory()->create(['name' => 'Original operator']);
            $this->actingAs($user);
            $design = LabelDesign::factory()->create(['name' => 'Sample label', 'zpl' => '^XA^FDPRIVATE ZPL^FS^XZ']);
            $event = LabelEvent::factory()->for($design, 'labelDesign')->create();
            $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldReceive('send')->once());

            app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));
            $this->actingAs(User::factory()->create(['name' => 'Different worker user']));
            $job = Queue::pushed(SendLabelPrinterSpoolJob::class)->sole();
            $this->app->call([$job, 'handle']);

            $activity = $this->printingLog();
            $this->assertSame($user->id, $job->activityContext['user_id']);
            $this->assertStringContainsString('Original operator', $activity);
            $this->assertStringNotContainsString('Different worker user', $activity);
            $this->assertStringContainsString('Sample label', $activity);
            $this->assertStringContainsString('"printer_id":'.$event->print_to, $activity);
            $this->assertStringContainsString('Label spool queued.', $activity);
            $this->assertStringContainsString('Label spool sending.', $activity);
            $this->assertStringContainsString('Label spool sent.', $activity);
            $this->assertStringNotContainsString('PRIVATE ZPL', $activity);
            $this->assertSame([], Storage::disk('local')->files('printing_debug'));
            Storage::disk('local')->assertMissing('application.log');
            Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        });
    }

    public function test_disabling_printing_after_queueing_logs_raw_zpl_with_the_current_alignment(): void
    {
        $this->withPrintingLogs(function (): void {
            Queue::fake([SendLabelPrinterSpoolJob::class]);
            $disabled = Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '0', 'default' => '0', 'description' => 'Disable']);
            $alignment = Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => '^LH1,1', 'default' => '', 'description' => 'Alignment']);
            $design = LabelDesign::factory()->create(['zpl' => "^XA\n^FDQUEUED^FS\n^XZ"]);
            LabelEvent::factory()->for($design, 'labelDesign')->create(['copies' => 2]);
            app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));
            app(SetCvar::class)->handle($disabled, '1');
            app(SetCvar::class)->handle($alignment, '^LH9,9');
            $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

            $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->sole(), 'handle']);

            $this->assertStringContainsString('^LH9,9'.$design->zpl.$design->zpl, $this->printingLog('printing_debug'));
            $this->assertStringContainsString('Label spool disabled.', $this->printingLog());
            $this->assertStringNotContainsString('Label spool sent.', $this->printingLog());
            Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        });
    }

    public function test_failed_transport_is_recorded_in_the_printing_activity_channel_without_raw_zpl(): void
    {
        $this->withPrintingLogs(function (): void {
            $design = LabelDesign::factory()->create(['name' => 'Sample label', 'zpl' => '^XA^FDPRIVATE FAILURE^FS^XZ']);
            LabelEvent::factory()->for($design, 'labelDesign')->create();
            $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldReceive('send')->once()->andThrow(new RuntimeException('Offline')));
            app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));

            $this->artisan('queue:work', ['connection' => 'database', '--queue' => 'printing', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

            $this->assertStringContainsString('Label spool failed.', $this->printingLog());
            $this->assertStringContainsString('Sample label', $this->printingLog());
            $this->assertStringContainsString('Offline', $this->printingLog());
            $this->assertStringNotContainsString('PRIVATE FAILURE', $this->printingLog());
            $this->assertSame([], Storage::disk('local')->files('printing_debug'));
            $this->assertDatabaseCount('failed_jobs', 1);
        });
    }

    public function test_planning_selects_only_matching_rules_in_the_requested_group_without_mutating_them(): void
    {
        $design = LabelDesign::factory()->create(['zpl' => '^XA^FD{sample_barcode}^FS^XZ']);
        $printer = Printer::factory()->create();
        $always = LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')->create(['copies' => 2]);
        LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')
            ->conditional(['type' => 'mType', 'inspect_value' => 'R', 'negative' => '0'])->create();
        $negative = LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')
            ->conditional(['type' => 'mType', 'inspect_value' => 'R', 'negative' => '1'])->create();
        LabelEvent::factory()->for($design, 'labelDesign')->for($printer, 'printer')->create(['event_group' => 2]);
        $original = LabelEvent::query()->orderBy('id')->get()->toArray();

        $plan = app(BuildLabelPrintPlan::class)->handle(1, ['sample' => ['sample_type' => 'S', 'barcode' => 'M260001']]);

        $this->assertSame([
            ['event_id' => $always->id, 'print_label' => $design->id, 'print_to' => $printer->id, 'copies' => 2, 'zpl' => '^XA^FDM260001^FS^XZ'],
            ['event_id' => $negative->id, 'print_label' => $design->id, 'print_to' => $printer->id, 'copies' => 1, 'zpl' => '^XA^FDM260001^FS^XZ'],
        ], $plan);
        $this->assertSame($original, LabelEvent::query()->orderBy('id')->get()->toArray());
    }

    public function test_printer_override_routes_all_matching_jobs_and_zero_keeps_each_rule_printer(): void
    {
        $default = Printer::factory()->create();
        $override = Printer::factory()->create();
        $design = LabelDesign::factory()->for($default, 'printer')->create();
        $first = LabelEvent::factory()->for($design, 'labelDesign')->create();
        $second = LabelEvent::factory()->for($design, 'labelDesign')->create();
        $planner = app(BuildLabelPrintPlan::class);

        $overridden = $planner->handle(1, [], $override->id);
        $normal = $planner->handle(1, [], 0);

        $this->assertSame([$override->id, $override->id], array_column($overridden, 'print_to'));
        $this->assertSame([$first->print_to, $second->print_to], array_column($normal, 'print_to'));
    }

    public function test_analysis_rules_match_both_selected_original_assays_and_assay_types(): void
    {
        $event = LabelEvent::factory()->conditional(['type' => 'assayIs', 'inspect_value' => '12,34', 'negative' => '0'], 2)->create();
        $type = LabelEvent::factory()->conditional(['type' => 'assayTypeIs', 'inspect_value' => '7', 'negative' => '0'], 2)->create();

        $plan = app(BuildLabelPrintPlan::class)->handle(2, [
            'analysis' => ['assay_base' => 99, 'original_assay_base' => 34], 'assay' => ['type_base' => 7],
        ]);

        $this->assertSame([$event->id, $type->id], array_column($plan, 'event_id'));
    }

    public function test_tht_planning_ignores_imported_conditional_rules_and_includes_unconditional_rules(): void
    {
        $always = LabelEvent::factory()->create(['event_group' => 3]);
        LabelEvent::factory()->conditional(['type' => 'mType', 'inspect_value' => 'S', 'negative' => '0'], 3)->create();

        $plan = app(BuildLabelPrintPlan::class)->handle(3, ['sample' => ['sample_type' => 'S']]);

        $this->assertSame([$always->id], array_column($plan, 'event_id'));
    }

    public function test_non_matching_or_corrupt_rules_do_not_fail_on_missing_designs_or_printers(): void
    {
        LabelEvent::factory()->conditional(['type' => 'mType', 'inspect_value' => 'R', 'negative' => '0'])
            ->create(['print_label' => 999999, 'print_to' => 999999]);
        LabelEvent::factory()->create(['event' => 'L', 'event_data' => '{broken', 'print_label' => 999999, 'print_to' => 999999]);

        $plan = app(BuildLabelPrintPlan::class)->handle(1, ['sample' => ['sample_type' => 'S']]);

        $this->assertSame([], $plan);
    }

    #[DataProvider('brokenRuleConfiguration')]
    public function test_matching_rules_with_broken_configuration_fail_before_any_jobs_are_sent(array $attributes): void
    {
        LabelEvent::factory()->create($attributes);
        $this->expectException(LogicException::class);

        app(BuildLabelPrintPlan::class)->handle(1, []);
    }

    public function test_unknown_groups_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(BuildLabelPrintPlan::class)->handle(4, []);
    }

    public function test_sample_registration_entry_point_loads_related_names_and_legacy_follow_numbers(): void
    {
        $first = $this->sample('M260001');
        $sample = $this->sample('M260002', $first->project, ['sample_extra' => '{"follow":"A-8"}', 'custom_fields' => '{"matrix":"Water"}']);
        $design = LabelDesign::factory()->create(['zpl' => '{sample_barcode}|{sample_db_follow_no}|{sample_user_follow_no}|{client_name}|{project_project_name}|{method_name}|{sample_custom_matrix}']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();

        $plan = app(BuildLabelPrintPlan::class)->forSampleRegistration($sample);

        $this->assertSame('M260002|2|A-8|Waterbedrijf|Project water|Scheppen|Water', $plan[0]['zpl']);
        $this->assertDatabaseCount('samples', 2);
    }

    public function test_analysis_addition_prints_each_result_follow_number_in_dilution_order_with_legacy_plate_fields(): void
    {
        $sample = $this->sample('M260003');
        $type = AssayType::create(['name' => 'Kiemgetal', 'description' => '', 'added_by' => 1, 'added_date' => '0']);
        $assay = Assay::create([
            'original_id' => 41, 'name' => 'Plate count', 'type_base' => $type->id, 'media_id' => '{}',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
            'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '',
        ]);
        $analysis = SampleAnalysis::create([
            'profile_group' => 1, 'sample' => $sample->id, 'follow_number' => 4, 'profile' => 0, 'assay' => 0,
            'assay_base' => $assay->id, 'original_assay_base' => 41, 'roaming_id' => 0,
            'project' => $sample->project, 'predicted_end' => 1,
        ]);
        foreach ([[1, 0, 3], [0.01, 0, 8], [0.01, 1, 9]] as [$dilution, $replicate, $follow]) {
            Result::create([
                'sample' => $sample->id, 'sa_id' => $analysis->id, 'follow_no' => $follow, 'profile' => 0,
                'assay' => 0, 'assay_base' => $assay->id, 'roaming_id' => 0, 'df' => (string) $dilution, 'rep' => $replicate, 'data' => [],
            ]);
        }
        $design = LabelDesign::factory()->create(['zpl' => '{assay_plate_barcode}|{assay_dillution_label}|{assay_dillution_numeric}|{assay_predicted_end_day}|{date_today}']);
        LabelEvent::factory()->for($design, 'labelDesign')
            ->conditional(['type' => 'assayIs', 'inspect_value' => '41,42', 'negative' => '0'], 2)->create(['copies' => 2]);

        $plan = app(BuildLabelPrintPlan::class)->forAnalysisAddition($analysis, printedAt: new DateTimeImmutable('2026-10-12T09:08:00+00:00'), dateOffset: 1);

        $this->assertSame([
            'M260003.4.8|-2|0.01|DO|13-10-2026',
            'M260003.4.9|-2|0.01|DO|13-10-2026',
            'M260003.4.3||1|DO|13-10-2026',
        ], array_column($plan, 'zpl'));
        $this->assertSame([2, 2, 2], array_column($plan, 'copies'));
        $this->assertDatabaseCount('results', 3);
        $this->assertSame(1, $analysis->fresh()->predicted_end);
    }

    public function test_tht_approval_entry_point_renders_buffer_fields_without_registering_samples(): void
    {
        $sample = $this->sample('M260004');
        $buffer = SampleBuffer::create([
            'client' => $sample->client, 'project' => 'Portal order', 'sampling_date' => '2026-10-12',
            'sampling_method' => $sample->sampling_method, 'sample_name' => 'Water', 'sample_details' => '',
            'tht' => 1, 'tht_date' => '2026-11-04', 'tht_code' => 'THT-7', 'meta' => [], 'analyses_selected' => [],
        ]);
        $design = LabelDesign::factory()->create(['zpl' => '{sample_tht_code}|{sample_sample_name}|{sample_tht_date}|{client_name}|{sample_db_follow_no}']);
        LabelEvent::factory()->for($design, 'labelDesign')->create(['event_group' => 3]);

        $plan = app(BuildLabelPrintPlan::class)->forThtApproval($buffer);

        $this->assertSame('THT-7|Water|04-11-2026|Waterbedrijf|', $plan[0]['zpl']);
        $this->assertDatabaseCount('samples', 1);
        $this->assertDatabaseCount('samplebuffers', 1);
    }

    public function test_sender_spools_once_per_printer_and_repeats_labels_with_one_legacy_alignment_prefix(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => '^LH4,4', 'default' => '', 'description' => 'Alignment']);
        $printer = Printer::factory()->create();
        $design = LabelDesign::factory()->create(['zpl' => '^XA^FDBOX^FS^XZ']);
        $first = LabelEvent::factory()->for($printer, 'printer')->for($design, 'labelDesign')->create(['copies' => 2]);
        $second = LabelEvent::factory()->for($printer, 'printer')->for($design, 'labelDesign')->create();
        $this->mock(LabelPrinterTransport::class, function (MockInterface $mock) use ($printer): void {
            $mock->shouldReceive('send')->once()->withArgs(fn (Printer $target, string $zpl): bool => $target->is($printer) && $zpl === '^LH4,4^XA^FDBOX^FS^XZ^XA^FDBOX^FS^XZ^XA^FDBOX^FS^XZ');
        });
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);

        $results = app(SendLabelPrintPlan::class)->handle($plan);

        $this->assertSame([[
            'printer_id' => $printer->id, 'status' => 'queued', 'bytes' => 51,
            'event_ids' => [$first->id, $second->id], 'error' => null,
        ]], $results);
        Queue::assertPushedOn('printing', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->printerId === $printer->id
            && $job->zpl === '^XA^FDBOX^FS^XZ^XA^FDBOX^FS^XZ^XA^FDBOX^FS^XZ'
            && $job->eventIds === [$first->id, $second->id]);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);

        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->first(), 'handle']);
    }

    #[DataProvider('disabledPrintingSettings')]
    public function test_disabled_printing_never_calls_the_transport(?string $value, string $default): void
    {
        Queue::fake();
        Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => $value, 'default' => $default, 'description' => 'Disable printing']);
        $event = LabelEvent::factory()->create();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);

        $results = app(SendLabelPrintPlan::class)->handle($plan);

        $this->assertSame([['printer_id' => $event->print_to, 'status' => 'disabled', 'bytes' => 0, 'event_ids' => [$event->id], 'error' => null]], $results);
        Queue::assertNothingPushed();
    }

    public function test_user_cvar_edits_change_printing_and_alignment_without_recreating_the_sender_or_reloading_config(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        config(['printing.enabled' => false, 'printing.alignment' => 'ignored']);
        $disabled = Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '0', 'default' => '1', 'description' => 'Disable printing']);
        $alignment = Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => '^LH4,4', 'default' => '^LH1,1', 'description' => 'Alignment']);
        $design = LabelDesign::factory()->create(['zpl' => '^XA^XZ']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();
        $this->mock(LabelPrinterTransport::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->withArgs(fn (Printer $printer, string $zpl): bool => $zpl === '^LH4,4^XA^XZ');
            $mock->shouldReceive('send')->once()->withArgs(fn (Printer $printer, string $zpl): bool => $zpl === '^LH9,9^XA^XZ');
        });
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);
        $sender = app(SendLabelPrintPlan::class);
        $setCvar = app(SetCvar::class);

        $first = $sender->handle($plan);
        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->first(), 'handle']);
        $setCvar->handle($disabled, '1');
        $stopped = $sender->handle($plan);
        $setCvar->handle($disabled, '0');
        $setCvar->handle($alignment, '^LH9,9');
        $resumed = $sender->handle($plan);
        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->last(), 'handle']);

        $this->assertSame('queued', $first[0]['status']);
        $this->assertSame('disabled', $stopped[0]['status']);
        $this->assertSame('queued', $resumed[0]['status']);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 2);
    }

    #[DataProvider('alignmentSettings')]
    public function test_alignment_uses_the_current_cvar_value_or_its_default_and_preserves_explicit_empty_values(?string $value, string $default, string $expectedPrefix): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => $value, 'default' => $default, 'description' => 'Alignment']);
        $design = LabelDesign::factory()->create(['zpl' => '^XA^XZ']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();
        $this->mock(LabelPrinterTransport::class, function (MockInterface $mock) use ($expectedPrefix): void {
            $mock->shouldReceive('send')->once()->withArgs(fn (Printer $printer, string $zpl): bool => $zpl === $expectedPrefix.'^XA^XZ');
        });
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);

        $results = app(SendLabelPrintPlan::class)->handle($plan);

        $this->assertSame('queued', $results[0]['status']);
        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->first(), 'handle']);
    }

    public function test_one_offline_printer_is_recorded_as_failed_without_retrying_or_blocking_other_printers(): void
    {
        $first = LabelEvent::factory()->create();
        $second = LabelEvent::factory()->create();
        $this->mock(LabelPrinterTransport::class, function (MockInterface $mock) use ($first, $second): void {
            $mock->shouldReceive('send')->once()->withArgs(fn (Printer $printer, string $zpl): bool => $printer->id === $first->print_to)->andThrow(new RuntimeException('Offline'));
            $mock->shouldReceive('send')->once()->withArgs(fn (Printer $printer, string $zpl): bool => $printer->id === $second->print_to);
        });
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);

        $results = app(SendLabelPrintPlan::class)->handle($plan);

        $this->assertSame(['queued', 'queued'], array_column($results, 'status'));
        $this->assertDatabaseCount('jobs', 2);

        $this->artisan('queue:work', [
            'connection' => 'database', '--queue' => 'printing', '--stop-when-empty' => true,
            '--sleep' => 0, '--tries' => 5,
        ])->assertSuccessful();

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->assertDatabaseHas('failed_jobs', ['connection' => 'database', 'queue' => 'printing']);
        $this->assertStringContainsString('Offline', DB::table('failed_jobs')->sole()->exception);
        $lock = Cache::lock('label-printer:'.$first->print_to, 5);

        try {
            $this->assertTrue($lock->get());
        } finally {
            $lock->release();
        }
    }

    public function test_print_jobs_use_the_configured_queue_and_connection_without_inline_transport(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        config(['printing.queue' => 'zebra-labels', 'printing.connection' => 'redis']);
        LabelEvent::factory()->create();
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

        $results = app(SendLabelPrintPlan::class)->handle($plan);

        $this->assertSame('queued', $results[0]['status']);
        Queue::assertPushedOn('zebra-labels', SendLabelPrinterSpoolJob::class, fn (SendLabelPrinterSpoolJob $job): bool => $job->connection === 'redis');
    }

    public function test_print_jobs_are_not_visible_to_workers_until_the_transaction_commits(): void
    {
        LabelEvent::factory()->create();
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);

        DB::transaction(function () use ($plan): void {
            app(SendLabelPrintPlan::class)->handle($plan);
            $this->assertDatabaseCount('jobs', 0);
        });

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'printing']);
    }

    public function test_print_jobs_are_discarded_when_the_transaction_rolls_back(): void
    {
        LabelEvent::factory()->create();
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);
        DB::beginTransaction();

        try {
            app(SendLabelPrintPlan::class)->handle($plan);
            $this->assertDatabaseCount('jobs', 0);
        } finally {
            DB::rollBack();
        }

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_disabling_printing_after_dispatch_prevents_the_queued_spool_from_being_sent(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $disabled = Cvar::create(['cvar' => 'MESA_DISABLE_PRINTING', 'value' => '0', 'default' => '0', 'description' => 'Disable printing']);
        LabelEvent::factory()->create();
        app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));
        app(SetCvar::class)->handle($disabled, '1');
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));

        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->sole(), 'handle']);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
    }

    public function test_alignment_edited_after_dispatch_is_used_when_the_spool_is_sent(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $alignment = Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => '^LH1,1', 'default' => '', 'description' => 'Alignment']);
        $design = LabelDesign::factory()->create(['zpl' => '^XA^XZ']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();
        app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));
        app(SetCvar::class)->handle($alignment, '^LH9,9');
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldReceive('send')->once()
            ->withArgs(fn (Printer $printer, string $zpl): bool => $zpl === '^LH9,9^XA^XZ'));

        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->sole(), 'handle']);

        Queue::assertPushed(SendLabelPrinterSpoolJob::class, 1);
    }

    public function test_alignment_changes_cannot_bypass_the_spool_size_limit_in_the_worker(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        config(['printing.max_spool_bytes' => 10]);
        $alignment = Cvar::create(['cvar' => 'MESA_ZEBRA_ALIGN', 'value' => '', 'default' => '', 'description' => 'Alignment']);
        $design = LabelDesign::factory()->create(['zpl' => '^XA^XZ']);
        LabelEvent::factory()->for($design, 'labelDesign')->create();
        app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));
        app(SetCvar::class)->handle($alignment, '^LH9,9');
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));
        $this->expectException(InvalidArgumentException::class);

        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->sole(), 'handle']);
    }

    public function test_a_printer_deleted_after_dispatch_fails_the_job_instead_of_silently_dropping_it(): void
    {
        Queue::fake([SendLabelPrinterSpoolJob::class]);
        $event = LabelEvent::factory()->create();
        app(SendLabelPrintPlan::class)->handle(app(BuildLabelPrintPlan::class)->handle(1, []));
        Printer::query()->whereKey($event->print_to)->delete();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));
        $this->expectException(ModelNotFoundException::class);

        $this->app->call([Queue::pushed(SendLabelPrinterSpoolJob::class)->sole(), 'handle']);
    }

    public function test_a_busy_printer_cannot_receive_an_overlapping_spool_while_other_printers_can_print(): void
    {
        config(['printing.lock_wait_seconds' => 0]);
        $busy = Printer::factory()->create();
        $available = Printer::factory()->create();
        $lock = Cache::lock('label-printer:'.$busy->id, 30);
        $this->assertTrue($lock->get());
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldReceive('send')->once()
            ->withArgs(fn (Printer $printer, string $zpl): bool => $printer->is($available) && $zpl === '^XA^XZ'));

        try {
            $this->app->call([new SendLabelPrinterSpoolJob($available->id, '^XA^XZ', []), 'handle']);
            $this->expectException(LockTimeoutException::class);
            $this->app->call([new SendLabelPrinterSpoolJob($busy->id, '^XA^XZ', []), 'handle']);
        } finally {
            $lock->release();
        }
    }

    public function test_oversized_spools_are_rejected_before_any_printer_is_contacted(): void
    {
        Queue::fake();
        config(['printing.max_spool_bytes' => 10]);
        LabelEvent::factory()->create();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);
        $this->expectException(InvalidArgumentException::class);

        try {
            app(SendLabelPrintPlan::class)->handle($plan);
        } finally {
            Queue::assertNothingPushed();
        }
    }

    public function test_deleted_printers_are_detected_before_any_spool_is_sent(): void
    {
        Queue::fake();
        LabelEvent::factory()->create();
        $second = LabelEvent::factory()->create();
        $plan = app(BuildLabelPrintPlan::class)->handle(1, []);
        Printer::query()->whereKey($second->print_to)->delete();
        $this->mock(LabelPrinterTransport::class, fn (MockInterface $mock) => $mock->shouldNotReceive('send'));
        $this->expectException(LogicException::class);

        try {
            app(SendLabelPrintPlan::class)->handle($plan);
        } finally {
            Queue::assertNothingPushed();
        }
    }

    #[DataProvider('printerTypes')]
    public function test_bound_tcp_transport_delivers_the_complete_zpl_to_both_printer_types(int $type): void
    {
        config(['printing.timeout_seconds' => 1]);
        $errorCode = 0;
        $errorMessage = '';
        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertIsResource($server);
        $address = stream_socket_get_name($server, false);
        $printer = Printer::factory()->make(['adres' => '127.0.0.1', 'port' => parse_url('tcp://'.$address, PHP_URL_PORT), 'type' => $type]);
        $payload = '^XA^FD'.str_repeat('A', 131072).'^FS^XZ';

        try {
            app(LabelPrinterTransport::class)->send($printer, $payload);
            $connection = stream_socket_accept($server, 1);
            $this->assertIsResource($connection);
            stream_set_timeout($connection, 1);
            $received = stream_get_contents($connection);

            $this->assertSame($payload, $received);
        } finally {
            if (isset($connection) && is_resource($connection)) {
                fclose($connection);
            }
            fclose($server);
        }
    }

    public function test_tcp_transport_times_out_when_a_printer_stops_accepting_the_spool(): void
    {
        $errorCode = 0;
        $errorMessage = '';
        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertIsResource($server);
        $address = stream_socket_get_name($server, false);
        $printer = Printer::factory()->make(['adres' => '127.0.0.1', 'port' => parse_url('tcp://'.$address, PHP_URL_PORT)]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('timed out accepting the label spool');

        try {
            (new TcpLabelPrinterTransport(0.02))->send($printer, str_repeat('A', 8 * 1024 * 1024));
        } finally {
            fclose($server);
        }
    }

    #[DataProvider('invalidPrinterEndpoints')]
    public function test_tcp_transport_rejects_invalid_endpoints_before_opening_a_socket(array $attributes): void
    {
        $printer = Printer::factory()->make($attributes);
        $this->expectException(InvalidArgumentException::class);

        (new TcpLabelPrinterTransport)->send($printer, '^XA^XZ');
    }

    public static function printerTypes(): array
    {
        return ['shared printer' => [0], 'print server' => [1]];
    }

    public static function disabledPrintingSettings(): array
    {
        return ['current cvar value' => ['1', '0'], 'cvar default when unset' => [null, '1']];
    }

    public static function alignmentSettings(): array
    {
        return [
            'current cvar value' => ['^LH4,4', '^LH1,1', '^LH4,4'],
            'cvar default when unset' => [null, '^LH1,1', '^LH1,1'],
            'empty value clears alignment' => ['', '^LH1,1', ''],
        ];
    }

    public static function invalidPrinterEndpoints(): array
    {
        return [
            'zero port' => [['port' => 0]], 'out-of-range port' => [['port' => 65536]],
            'unknown type' => [['type' => 2]], 'URL instead of hostname' => [['adres' => 'http://localhost/path']],
        ];
    }

    public static function brokenRuleConfiguration(): array
    {
        return [
            'missing design' => [['print_label' => 999999]],
            'missing printer' => [['print_to' => 999999]],
            'zero copies' => [['copies' => 0]],
            'too many copies' => [['copies' => 10000]],
        ];
    }

    private function withPrintingLogs(callable $callback): void
    {
        Storage::fake('local');
        config([
            'logging.default' => 'single',
            'logging.channels.single.path' => Storage::disk('local')->path('application.log'),
            'logging.channels.printing.path' => Storage::disk('local')->path('printing/printing.log'),
            'logging.channels.printing_debug.path' => Storage::disk('local')->path('printing_debug/printing.log'),
        ]);

        foreach (['single', 'printing', 'printing_debug'] as $channel) {
            Log::forgetChannel($channel);
        }

        try {
            $callback();
        } finally {
            foreach (['single', 'printing', 'printing_debug'] as $channel) {
                Log::forgetChannel($channel);
            }
        }
    }

    private function printingLog(string $channel = 'printing'): string
    {
        return collect(Storage::disk('local')->files($channel))
            ->map(fn (string $path): string => Storage::disk('local')->get($path))->implode("\n");
    }

    private function assay(): Assay
    {
        return Assay::create([
            'original_id' => 41, 'name' => 'Plate count', 'type_base' => 0, 'media_id' => '{}',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
            'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '', 'active' => 1,
        ]);
    }

    private function sample(string $barcode, ?int $projectId = null, array $attributes = []): Sample
    {
        if ($projectId === null) {
            $client = Client::create(['name' => 'Waterbedrijf', 'active' => 1]);
            $projectId = Project::create([
                'client' => $client->id, 'subclient' => 0, 'project_name' => 'Project water', 'lock_pass' => 'private',
            ])->id;
        }

        $project = Project::query()->findOrFail($projectId);
        $procedure = SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0]);

        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1000, 'description' => 'Water sample', 'sampling_method' => $procedure->id,
            'date_registered' => '1788998400', 'registered_by' => 1, 'client' => $project->client, 'subclient' => 0,
            'project' => $projectId, 'custom_fields' => '{}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'stored_in' => 'A', 'diluted_at' => '1', 'analyses_data' => '[]', ...$attributes,
        ])->refresh();
    }
}
