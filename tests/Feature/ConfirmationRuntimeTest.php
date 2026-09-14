<?php

namespace Tests\Feature;

use App\Actions\Confirmations\AddConfirmationContender;
use App\Actions\Confirmations\GetConfirmation;
use App\Actions\Confirmations\RecalculateConfirmation;
use App\Actions\Confirmations\RemoveConfirmationContender;
use App\Actions\Confirmations\SetConfirmationDecision;
use App\Actions\Confirmations\UpdateConfirmationMetadata;
use App\Actions\Confirmations\UpdateConfirmationTrackValue;
use App\Actions\Results\UpdateResultValue;
use App\Models\Assay;
use App\Models\Media;
use App\Models\Result;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConfirmationRuntimeTest extends TestCase
{
    private mixed $originalDefaultConnection;

    private mixed $originalSqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config([
            'database.default' => $this->originalDefaultConnection,
            'database.connections.sqlite.database' => $this->originalSqliteDatabase,
        ]);

        parent::tearDown();
    }

    public function test_enabled_confirmation_moves_from_pending_to_complete_with_a_ratio(): void
    {
        Queue::fake();
        [$analysis, $media] = $this->analysis();

        app(SetConfirmationDecision::class)->handle($analysis, 'enable');
        $pending = app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertSame('enabled_pending', $pending['status']);
        $this->assertFalse($pending['scopes'][0]['ready']);
        $this->assertSame(1, count($pending['contenders']));

        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '0.1', 0, 0, 0, '+');
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), '0.1', 0, $media->id.'_inzet', '12-09-2026');
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), '0.1', 0, $media->id.'_aflees', '13-09-2026');

        $complete = app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertSame('enabled_complete', $complete['status']);
        $this->assertTrue($complete['scopes'][0]['ready']);
        $this->assertSame(1, $complete['summary']['tested']);
        $this->assertSame(1, $complete['summary']['confirmed']);
        $this->assertSame(1, $complete['summary']['ratio']);
    }

    public function test_undecided_confirmation_does_not_prompt_without_a_qualifying_calculation(): void
    {
        [$analysis] = $this->analysis();

        $state = app(GetConfirmation::class)->handle($analysis);

        $this->assertSame('not_applicable', $state['status']);
        $this->assertFalse($state['decision_required']);

        $analysis->storedResult = [
            'confirmation' => [
                'status' => 'decision_pending',
                'decision_required' => true,
            ],
        ];
        $analysis->save();

        $state = app(GetConfirmation::class)->handle($analysis->fresh());

        $this->assertSame('decision_pending', $state['status']);
        $this->assertTrue($state['decision_required']);
    }

    public function test_changing_a_plate_count_to_zero_removes_that_confirmation_scope(): void
    {
        Queue::fake();
        [$analysis, $media] = $this->analysis();

        app(SetConfirmationDecision::class)->handle($analysis, 'enable');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '0.1', 0, 0, 0, '+');
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), '0.1', 0, $media->id.'_inzet', '12-09-2026');
        $confirmation = $analysis->fresh()->confirmationRecord;
        $confirmation->note = 'Bewaren';
        $confirmation->save();
        $result = $analysis->fresh()->results()->firstOrFail();

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result, 'kve', '0');
        app(RecalculateConfirmation::class)->handle($analysis->fresh(), $confirmation->fresh());

        $state = app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertSame($confirmation->id, $analysis->fresh()->confirmationRecord->id);
        $this->assertSame('Bewaren', $analysis->fresh()->confirmationRecord->note);
        $this->assertSame('enabled_complete', $state['status']);
        $this->assertFalse($state['scopes'][0]['applicable']);
        $this->assertTrue($state['scopes'][0]['ready']);
        $this->assertSame([], $state['contenders']);
        $this->assertArrayNotHasKey('0.1', $analysis->fresh()->confirmationRecord->racetrack);
        $this->assertSame([
            'applicable' => false,
            'isReady' => true,
            'ratio' => false,
        ], $analysis->fresh()->confirmationRecord->metadata['0.1']['0']);
    }

    public function test_changing_a_plate_count_from_zero_initializes_that_confirmation_scope(): void
    {
        Queue::fake();
        [$analysis] = $this->analysis();
        $result = $analysis->results()->firstOrFail();
        $result->update(['data' => ['kve' => '0']]);

        app(SetConfirmationDecision::class)->handle($analysis->fresh(), 'enable');

        $this->assertSame([], app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0)['contenders']);

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result->fresh(), 'kve', '2');
        app(RecalculateConfirmation::class)->handle($analysis->fresh(), $analysis->fresh()->confirmationRecord);

        $state = app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertSame('enabled_pending', $state['status']);
        $this->assertTrue($state['scopes'][0]['applicable']);
        $this->assertCount(2, $state['contenders']);
    }

    public function test_changing_a_plate_count_to_zero_preserves_other_dilution_tracks(): void
    {
        Queue::fake();
        [$analysis] = $this->analysis();
        Result::create([
            'sample' => 10,
            'sa_id' => $analysis->id,
            'follow_no' => 1,
            'profile' => 0,
            'assay' => 0,
            'assay_base' => $analysis->assay_base,
            'roaming_id' => 0,
            'df' => '1',
            'rep' => 0,
            'data' => ['kve' => '1'],
        ]);

        app(SetConfirmationDecision::class)->handle($analysis->fresh(), 'enable');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '0.1', 0, 0, 0, '+');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '1', 0, 0, 0, '-');
        $result = $analysis->results()->where('df', '0.1')->firstOrFail();

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result, 'kve', '0');
        app(RecalculateConfirmation::class)->handle($analysis->fresh(), $analysis->fresh()->confirmationRecord);

        $racetrack = $analysis->fresh()->confirmationRecord->racetrack;

        $this->assertArrayNotHasKey('0.1', $racetrack);
        $this->assertSame('-', $racetrack['1']['0'][0][0]);
    }

    public function test_saving_the_same_count_keeps_the_existing_decision(): void
    {
        Queue::fake();
        [$analysis] = $this->analysis();

        app(SetConfirmationDecision::class)->handle($analysis, 'enable');
        $result = $analysis->fresh()->results()->firstOrFail();

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result, 'kve', '1');

        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertNotNull($analysis->fresh()->confirmationRecord);
    }

    public function test_changing_a_plate_count_does_not_expand_started_confirmation_tracks(): void
    {
        Queue::fake();
        [$analysis, $media] = $this->analysis();
        $analysis->assayRecord()->update(['confirmation_init' => 1]);

        app(SetConfirmationDecision::class)->handle($analysis, 'enable');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '0.1', 0, 0, 0, '+');
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), '0.1', 0, $media->id.'_inzet', '12-09-2026');
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), '0.1', 0, $media->id.'_aflees', '13-09-2026');
        $result = $analysis->fresh()->results()->firstOrFail();

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result, 'kve', '2');

        $rebuilt = app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertSame(1, $analysis->fresh()->conf_requested);
        $this->assertTrue($rebuilt['scopes'][0]['applicable']);
        $this->assertSame('enabled_complete', $rebuilt['status']);
        $this->assertCount(1, $rebuilt['contenders']);
        $this->assertSame('+', $rebuilt['contenders'][0]['answers']['0']);
    }

    public function test_decreasing_a_plate_count_does_not_contract_started_confirmation_tracks(): void
    {
        Queue::fake();
        [$analysis] = $this->analysis();
        $result = $analysis->results()->firstOrFail();

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result, 'kve', '2');
        app(SetConfirmationDecision::class)->handle($analysis->fresh(), 'enable');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '0.1', 0, 0, 0, '+');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), '0.1', 0, 1, 0, '-');

        app(UpdateResultValue::class)->handle($analysis->fresh(), $result->fresh(), 'kve', '1');

        $state = app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertCount(2, $state['contenders']);
        $this->assertSame('+', $state['contenders'][0]['answers']['0']);
        $this->assertSame('-', $state['contenders'][1]['answers']['0']);
    }

    public function test_started_confirmation_tracks_change_only_through_manual_actions(): void
    {
        Queue::fake();
        [$analysis] = $this->analysis();

        app(SetConfirmationDecision::class)->handle($analysis, 'enable');
        app(AddConfirmationContender::class)->handle($analysis->fresh(), '0.1', 0);

        $this->assertCount(2, app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0)['contenders']);

        app(RemoveConfirmationContender::class)->handle($analysis->fresh(), '0.1', 0, 1);

        $this->assertCount(1, app(GetConfirmation::class)->handle($analysis->fresh(), '0.1', 0)['contenders']);
    }

    /** @return array{SampleAnalysis, Media} */
    private function analysis(): array
    {
        $media = Media::create([
            'name' => 'Confirmation medium',
            'hasDate' => 0,
            'confirmation_controls' => '{}',
            'active' => 1,
        ]);
        $assay = Assay::create([
            'original_id' => 0,
            'name' => 'Confirmation assay',
            'type_base' => 1,
            'media_id' => '[]',
            'dillution' => 1,
            'replicates' => 0,
            'confirmation' => 1,
            'confirmation_type' => 1,
            'confirmation_script' => json_encode([
                ['mediaId' => $media->id, 'chainId' => 1, 'disposition' => '+'],
            ], JSON_THROW_ON_ERROR),
            'confirmation_support' => null,
            'type' => 1,
            'meta_assays' => '',
            'max_count' => 300,
            'min_count' => 0,
            'script' => '',
            'custom_fields' => '{}',
            'duration' => '1',
            'start_from' => 'r',
            'confirmation_init' => 0,
            'confirmation_depth' => 2,
            'article_code' => '',
        ]);
        $analysis = SampleAnalysis::create([
            'profile_group' => 1,
            'sample' => 10,
            'follow_number' => 1,
            'profile' => 0,
            'assay' => 0,
            'assay_base' => $assay->id,
            'roaming_id' => null,
            'predicted_end' => 0,
            'original_assay_base' => null,
            'conf_requested' => 0,
            'is_ready' => 0,
            'project' => null,
            'project_order' => 1,
            'storedResult' => null,
        ]);
        Result::create([
            'sample' => 10,
            'sa_id' => $analysis->id,
            'follow_no' => 1,
            'profile' => 0,
            'assay' => 0,
            'assay_base' => $assay->id,
            'roaming_id' => 0,
            'df' => '0.1',
            'rep' => 0,
            'data' => ['kve' => '1'],
        ]);

        return [$analysis, $media];
    }
}
