<?php

namespace Tests\Feature;

use App\Actions\Confirmations\SetConfirmationDecision;
use App\Actions\Confirmations\UpdateConfirmationMetadata;
use App\Actions\Confirmations\UpdateConfirmationTrackValue;
use App\Actions\Results\CalculateAnalysisResult;
use App\Calculations\Maz7218GrensCalculation;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Media;
use App\Models\Result;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Maz7218GrensWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    public function test_calculates_and_persists_the_registered_border_calculator_for_a_profile(): void
    {
        [$analysis, $assay] = $this->analysis('+', ['confirmation' => 0]);
        $profile = AssayProfile::create([
            'research_profile' => 1, 'assay' => $assay->id, 'dillutions' => [1],
            'replicates' => 0, 'reference' => [], 'hidden' => 0, 'project_order' => 1, 'conf_trip' => 0,
        ]);
        $analysis->update(['profile' => 1, 'assay' => $profile->id, 'roaming_id' => null]);

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame(['kve' => 'Aangetoond'], $calculation['output']);
        $this->assertSame(Maz7218GrensCalculation::class, $calculation['_calculation']['calculator']);
        $this->assertTrue($analysis->fresh()->is_ready);
        $this->assertSame($calculation, $analysis->fresh()->storedResult);
    }

    #[DataProvider('ctResults')]
    public function test_completeness_preserves_the_border_ct_exception(string $value, bool $ready): void
    {
        [$analysis] = $this->analysis($value, ['confirmation' => 0]);
        $analysis->results()->firstOrFail()->update(['data' => ['kve' => $value, 'Ctwaarde' => '']]);

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame(['kve' => $ready ? 'Niet aangetoond' : 'Niet afgerond'], $calculation['output']);
        $this->assertSame($ready, $calculation['isReady']);
        $this->assertSame($ready, $analysis->fresh()->is_ready);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public static function ctResults(): array
    {
        return ['minus' => ['-', true], 'zero' => ['0', true], 'positive' => ['+', false], 'N is not exempt' => ['N', false]];
    }

    public function test_empty_ct_is_not_exempt_for_other_calculators(): void
    {
        [$analysis] = $this->analysis('0', ['confirmation' => 0, 'script' => 'file:maz7218']);
        $analysis->results()->firstOrFail()->update(['data' => ['kve' => '0', 'Ctwaarde' => '']]);

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertFalse($calculation['isReady']);
        $this->assertSame(['result' => 'Niet afgerond'], $calculation['output']);
    }

    public function test_pending_confirmation_keeps_legacy_text_and_positive_messages(): void
    {
        [$analysis] = $this->analysis('+', ['confirmation_type' => 0, 'script' => '$toonMeldingIndienPositief=Controle nodig'."\nfile:maz7218grens"]);

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('enabled_pending', $calculation['confirmation']['status']);
        $this->assertSame(['kve' => 'Niet afgerond'], $calculation['output']);
        $this->assertSame(['kve' => 'Not completed'], $calculation['outputEn']);
        $this->assertSame(['kve' => ''], $calculation['disposition']);
        $this->assertSame(['Controle nodig'], $calculation['messageBag']);
        $this->assertFalse($analysis->fresh()->is_ready);
        $this->assertSame($calculation, $analysis->fresh()->storedResult);
        $this->assertDatabaseCount('confirmations', 1);
    }

    public function test_automatic_decline_applies_the_culture_marker_in_the_same_calculation(): void
    {
        [$analysis] = $this->analysis('+', ['confirmation_init' => 2]);

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame(['kve' => 'Aangetoond <sup>**</sup>'], $calculation['output']);
        $this->assertSame(['kve' => 'Detected <sup>**</sup>'], $calculation['outputEn']);
        $this->assertSame('disabled', $calculation['confirmation']['status']);
        $this->assertTrue($analysis->fresh()->is_ready);
        $this->assertSame(2, $analysis->fresh()->conf_requested);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public function test_ask_mode_still_requests_a_confirmation_decision(): void
    {
        [$analysis] = $this->analysis('+', ['confirmation_init' => 0]);

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('decision_pending', $calculation['confirmation']['status']);
        $this->assertTrue($calculation['confirmation']['decision_required']);
        $this->assertFalse($analysis->fresh()->is_ready);
        $this->assertDatabaseCount('confirmations', 0);
    }

    public function test_negative_border_results_do_not_create_confirmation_work(): void
    {
        [$analysis] = $this->analysis('-');

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis);

        $this->assertSame('not_applicable', $calculation['confirmation']['status']);
        $this->assertSame(['kve' => 'Niet aangetoond'], $calculation['output']);
        $this->assertTrue($analysis->fresh()->is_ready);
        $this->assertDatabaseCount('confirmations', 0);
    }

    #[DataProvider('confirmedBorderResults')]
    public function test_confirmed_border_results_flow_through_the_existing_actions(int $type, string $confirmationValue, string $expected): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Queue::fake();
        [$analysis, $assay] = $this->analysis('+', ['confirmation_type' => $type]);
        $scope = $type === 0 ? 'global' : '1';
        $mediaId = json_decode($assay->confirmation_script, true)[0]['mediaId'];
        app(SetConfirmationDecision::class)->handle($analysis, 'enable');
        app(UpdateConfirmationTrackValue::class)->handle($analysis->fresh(), $scope, 0, 0, 0, $confirmationValue);
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), $scope, 0, $mediaId.'_inzet', '16-10-2026');
        app(UpdateConfirmationMetadata::class)->handle($analysis->fresh(), $scope, 0, $mediaId.'_aflees', '17-10-2026');

        $calculation = app(CalculateAnalysisResult::class)->handle($analysis->fresh());

        $this->assertSame('enabled_complete', $calculation['confirmation']['status']);
        $this->assertSame(['kve' => $expected], $calculation['output']);
        $this->assertTrue($analysis->fresh()->is_ready);
        $this->assertSame(json_decode(json_encode($calculation, JSON_THROW_ON_ERROR), true), $analysis->fresh()->storedResult);
    }

    public static function confirmedBorderResults(): array
    {
        return [
            'per plate confirmed' => [1, '+', 'Aangetoond'],
            'per plate rejected' => [1, '-', 'Niet aangetoond'],
            'global confirmed' => [0, '+', 'Aangetoond'],
            'global rejected' => [0, '-', 'Niet aangetoond'],
        ];
    }

    /** @return array{SampleAnalysis, Assay} */
    private function analysis(string $value, array $assayOverrides = []): array
    {
        $media = Media::create(['name' => 'Border confirmation medium', 'hasDate' => 0, 'confirmation_controls' => '{}', 'active' => 1]);
        $assay = Assay::create(array_replace([
            'original_id' => 0, 'name' => 'Border assay', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 0, 'confirmation' => 1, 'confirmation_type' => 1,
            'confirmation_script' => json_encode([['mediaId' => $media->id, 'chainId' => 1, 'disposition' => '+']], JSON_THROW_ON_ERROR),
            'confirmation_support' => null, 'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 0,
            'script' => 'file:maz7218grens', 'custom_fields' => '{}', 'duration' => '1', 'start_from' => 'r',
            'confirmation_init' => 1, 'confirmation_depth' => 1, 'article_code' => '',
        ], $assayOverrides));
        $analysis = SampleAnalysis::create([
            'profile_group' => 1, 'sample' => 10, 'follow_number' => 1, 'profile' => 0, 'assay' => 0,
            'assay_base' => $assay->id, 'roaming_id' => null, 'predicted_end' => 0,
            'original_assay_base' => null, 'conf_requested' => 0, 'is_ready' => 0, 'project' => null,
            'project_order' => 1, 'storedResult' => null,
        ]);
        $roaming = RoamingAnalysis::create([
            'said' => $analysis->id, 'assay' => $assay->id, 'dillutions' => [1], 'replicates' => 0,
            'reference' => [], 'reference_scope' => '', 'reference_source' => null,
        ]);
        $analysis->update(['roaming_id' => $roaming->id]);
        Result::create([
            'sample' => 10, 'sa_id' => $analysis->id, 'follow_no' => 1, 'profile' => 0,
            'assay' => 0, 'assay_base' => $assay->id, 'roaming_id' => $roaming->id,
            'df' => '1', 'rep' => 0, 'data' => ['kve' => $value],
        ]);

        return [$analysis, $assay];
    }
}
