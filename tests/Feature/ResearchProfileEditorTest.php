<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\Client;
use App\Models\ReferenceSource;
use App\Models\ResearchProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResearchProfileEditorTest extends TestCase
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

    #[DataProvider('profileSearches')]
    public function test_directory_search_matches_client_or_profile_names_and_excludes_inactive_records(string $search, array $expectedNames): void
    {
        $this->operator();
        $client = Client::create(['name' => 'Acme Laboratories']);
        $otherClient = Client::create(['name' => 'Other Laboratories']);
        $inactiveClient = Client::create(['name' => 'Acme Inactive', 'active' => 0]);
        $this->profile(['name' => 'Routine panel', 'global' => 0, 'client' => $client->id]);
        $this->profile(['name' => 'Acme panel', 'global' => 0, 'client' => $otherClient->id]);
        $this->profile(['name' => 'Acme global']);
        $this->profile(['name' => 'Control panel', 'global' => 0, 'client' => $otherClient->id]);
        $this->profile(['name' => 'Acme retired', 'global' => 0, 'client' => $client->id, 'active' => 0]);
        $this->profile(['name' => 'Acme hidden', 'global' => 0, 'client' => $inactiveClient->id]);

        $response = $this->getJson(route('research-profiles.list', ['query' => $search]))->assertOk();

        $this->assertSame($expectedNames, array_column($response->json('data'), 'name'));
    }

    public static function profileSearches(): array
    {
        return [
            'partial client and profile names ignoring case and surrounding spaces' => [
                ' cME ', ['Acme global', 'Acme panel', 'Routine panel'],
            ],
            'partial profile name ignoring case' => ['OUTIN', ['Routine panel']],
            'no matches' => ['Unknown', []],
            'empty search' => ['', ['Acme global', 'Acme panel', 'Control panel', 'Routine panel']],
            'whitespace search' => ['   ', ['Acme global', 'Acme panel', 'Control panel', 'Routine panel']],
        ];
    }

    public function test_editor_exposes_each_workflow_output_and_the_original_inactive_assay_settings(): void
    {
        $this->operator();
        config(['result-calculations.reference_outputs.Multi' => ['ph' => 'pH', 'temperature' => 'Temperatuur']]);
        $oldAssay = $this->assay('Inactive multi-output assay', '$setting=True'."\r\n".'File:Multi.class.php');
        $oldAssay->update(['active' => 0]);
        $currentAssay = $this->assay('MPN', 'file:mpn');
        $profile = $this->profile();
        $profile->assayProfiles()->create($this->settings($oldAssay, ['ref_ph' => '7', 'ref_temperature' => '20']));

        $this->getJson(route('research-profiles.data', $profile))
            ->assertOk()
            ->assertJsonPath('data.profile.assays.0.configuration.reference_outputs', [
                ['key' => 'ref_ph', 'name' => 'ph', 'label' => 'pH'],
                ['key' => 'ref_temperature', 'name' => 'temperature', 'label' => 'Temperatuur'],
            ])
            ->assertJsonPath('data.profile.assays.0.reference', ['ref_ph' => '7', 'ref_temperature' => '20'])
            ->assertJsonPath('data.assays.0.id', $currentAssay->id)
            ->assertJsonPath('data.assays.0.configuration.reference_outputs', [
                ['key' => 'ref_kve', 'name' => 'kve', 'label' => 'kve/100ml'],
            ])
            ->assertJsonMissingPath('data.assays.0.script');
    }

    #[DataProvider('unconfiguredWorkflows')]
    public function test_unconfigured_workflows_do_not_invent_kve_reference_fields_or_break_the_editor(string $script): void
    {
        $this->operator();
        $this->assay('Unknown', $script);

        $this->getJson(route('research-profiles.data'))
            ->assertOk()
            ->assertJsonPath('data.assays.0.configuration.reference_outputs', null);
    }

    public static function unconfiguredWorkflows(): array
    {
        return [
            'unknown identifier' => ['file:notported'],
            'inline script' => ['$output = ["ph" => "pH"];'],
            'malformed file declaration' => ['file:../invalid'],
        ];
    }

    public function test_default_calculator_exposes_its_result_output(): void
    {
        $this->operator();
        $this->assay('Default', '');

        $this->getJson(route('research-profiles.data'))
            ->assertOk()
            ->assertJsonPath('data.assays.0.configuration.reference_outputs', [
                ['key' => 'ref_result', 'name' => 'result', 'label' => 'Resultaat'],
            ]);
    }

    #[DataProvider('referencesRequiringSource')]
    public function test_creating_with_a_reference_without_a_source_returns_422_and_writes_nothing(array $reference): void
    {
        $this->operator();
        $assay = $this->assay();

        $this->postJson(route('research-profiles.store'), $this->payload([$this->settings($assay, $reference)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'assays.0.reference_source' => 'Een referentiebron is verplicht wanneer een referentiewaarde is ingevuld.',
            ]);

        $this->assertDatabaseCount('researchprofiles', 0);
        $this->assertDatabaseCount('assayprofiles', 0);
    }

    public static function referencesRequiringSource(): array
    {
        return [
            'positive number' => [['ref_kve' => '100']],
            'zero string' => [['ref_kve' => '0']],
            'zero number' => [['ref_kve' => 0]],
            'qualitative output' => [['ref_presence' => 'Afwezig']],
            'second output only' => [['ref_ph' => '', 'ref_temperature' => '20']],
        ];
    }

    public function test_creating_without_reference_values_does_not_require_a_source(): void
    {
        $this->operator();
        $assay = $this->assay();

        $response = $this->postJson(route('research-profiles.store'), $this->payload([
            $this->settings($assay, ['ref_kve' => '  ', 'ref_other' => null]),
        ]))->assertCreated();

        $profile = ResearchProfile::findOrFail($response->json('data.id'));
        $this->assertSame($profile->id, $profile->original_id);
        $this->assertNull($profile->assayProfiles->sole()->reference_source);
    }

    public function test_creating_preserves_all_output_keys_order_and_analysis_settings(): void
    {
        $this->operator();
        $first = $this->assay('First');
        $second = $this->assay('Second');
        $source = $this->source();
        $settings = [
            ...$this->settings($second, ['ref_ph' => '7.2', 'ref_temperature' => '20'], $source),
            'dillutions' => ['-1' => '0.1', '-2' => '0.01'], 'replicates' => 2, 'conf_trip' => 50,
        ];

        $response = $this->postJson(route('research-profiles.store'), $this->payload([
            $settings, $this->settings($first),
        ]))->assertCreated();

        $rows = ResearchProfile::findOrFail($response->json('data.id'))->assayProfiles;
        $this->assertSame([$second->id, $first->id], $rows->pluck('assay')->all());
        $this->assertSame([1, 2], $rows->pluck('project_order')->all());
        $this->assertSame(['ref_ph' => '7.2', 'ref_temperature' => '20'], $rows[0]->reference);
        $this->assertSame(['-1' => '0.1', '-2' => '0.01'], $rows[0]->dillutions);
        $this->assertSame(2, $rows[0]->replicates);
        $this->assertSame(50, $rows[0]->conf_trip);
        $this->assertSame($source->id, $rows[0]->reference_source);
    }

    public function test_failed_revision_keeps_the_current_profile_active_and_writes_no_revision(): void
    {
        $this->operator();
        $assay = $this->assay();
        $profile = $this->profile();

        $this->putJson(route('research-profiles.update', $profile), $this->payload([$this->settings($assay, ['ref_kve' => '0'])]))
            ->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');

        $this->assertTrue($profile->fresh()->active);
        $this->assertDatabaseCount('researchprofiles', 1);
        $this->assertDatabaseCount('assayprofiles', 0);
    }

    public function test_valid_revision_preserves_history_and_the_older_revision_cannot_be_changed(): void
    {
        $this->operator();
        $assay = $this->assay();
        $profile = $this->profile();
        $payload = $this->payload([$this->settings($assay, ['ref_kve' => '0'], $this->source())]);

        $response = $this->putJson(route('research-profiles.update', $profile), $payload)->assertOk();

        $revised = ResearchProfile::findOrFail($response->json('data.id'));
        $this->assertFalse($profile->fresh()->active);
        $this->assertTrue($revised->active);
        $this->assertSame($profile->original_id, $revised->original_id);
        $this->assertSame(['ref_kve' => '0'], $revised->assayProfiles->sole()->reference);

        $this->putJson(route('research-profiles.update', $profile), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('profile');
        $this->assertDatabaseCount('researchprofiles', 2);
    }

    public function test_client_sources_are_offered_and_accepted_only_for_their_own_client(): void
    {
        $this->operator();
        $client = Client::create(['name' => 'Client A']);
        $other = Client::create(['name' => 'Client B']);
        $global = $this->source();
        $private = $this->source($client->id);
        $this->source($other->id);
        $assay = $this->assay();
        $settings = $this->settings($assay, ['ref_kve' => '10'], $private);

        $this->getJson(route('research-profiles.reference-sources', ['client' => $client->id]))
            ->assertOk()->assertJsonPath('data', [
                ['id' => $global->id, 'name' => 'Source', 'client' => null],
                ['id' => $private->id, 'name' => 'Source', 'client' => $client->id],
            ]);
        $this->postJson(route('research-profiles.store'), $this->payload([$settings]))
            ->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');
        $this->postJson(route('research-profiles.store'), [
            ...$this->payload([$settings]), 'global' => false, 'client' => $other->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');
        $response = $this->postJson(route('research-profiles.store'), [
            ...$this->payload([$settings]), 'global' => false, 'client' => $client->id,
        ])->assertCreated();

        $this->assertDatabaseCount('researchprofiles', 1);
        $this->assertSame($private->id, ResearchProfile::findOrFail($response->json('data.id'))->assayProfiles->sole()->reference_source);
    }

    public function test_copy_preserves_decoded_legacy_reference_and_dilution_settings(): void
    {
        $this->operator();
        $source = $this->profile();
        $destination = $this->profile();
        $assay = $this->assay();
        $referenceSource = $this->source();
        $source->assayProfiles()->create([
            ...$this->settings($assay, ['ref_ph' => '7', 'ref_temperature' => '20'], $referenceSource),
            'dillutions' => ['-1' => '0.1'],
        ]);

        $this->postJson(route('research-profiles.copy', $destination), ['source_id' => $source->id])->assertOk();

        $copied = $destination->assayProfiles->sole();
        $this->assertSame(['ref_ph' => '7', 'ref_temperature' => '20'], $copied->reference);
        $this->assertSame(['-1' => '0.1'], $copied->dillutions);
        $this->assertSame($referenceSource->id, $copied->reference_source);
    }

    public function test_copy_rejects_a_reference_without_a_source(): void
    {
        $this->operator();
        $source = $this->profile();
        $destination = $this->profile();
        $source->assayProfiles()->create($this->settings($this->assay(), ['ref_kve' => '0']));

        $this->postJson(route('research-profiles.copy', $destination), ['source_id' => $source->id])
            ->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');

        $this->assertSame(0, $destination->assayProfiles()->count());
    }

    public function test_copy_rejects_a_reference_source_from_another_client(): void
    {
        $this->operator();
        $client = Client::create(['name' => 'Client']);
        $source = $this->profile();
        $source->update(['global' => 0, 'client' => $client->id]);
        $destination = $this->profile();
        $source->assayProfiles()->create($this->settings($this->assay(), ['ref_kve' => '10'], $this->source($client->id)));

        $this->postJson(route('research-profiles.copy', $destination), ['source_id' => $source->id])
            ->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');

        $this->assertSame(0, $destination->assayProfiles()->count());
    }

    public function test_bulk_cannot_apply_a_reference_without_a_source(): void
    {
        $this->operator();
        $profile = $this->profile();
        $assay = $this->assay();

        $this->postJson(route('research-profiles.bulk.update'), [
            'profiles' => [$profile->id], 'mutation' => 'add', 'assay' => $assay->id,
            'settings' => ['reference' => ['ref_kve' => '0']],
        ])->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');

        $this->assertDatabaseCount('researchprofiles', 1);
        $this->assertTrue($profile->fresh()->active);
    }

    public function test_bulk_rolls_back_all_profiles_when_a_later_profile_has_an_unsourced_reference(): void
    {
        $this->operator();
        $first = $this->profile();
        $second = $this->profile();
        $second->assayProfiles()->create($this->settings($this->assay(), ['ref_kve' => '0']));
        $newAssay = $this->assay('New assay');

        $this->postJson(route('research-profiles.bulk.update'), [
            'profiles' => [$first->id, $second->id], 'mutation' => 'add', 'assay' => $newAssay->id,
            'settings' => ['reference' => []],
        ])->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference_source');

        $this->assertDatabaseCount('researchprofiles', 2);
        $this->assertDatabaseCount('assayprofiles', 1);
        $this->assertTrue($first->fresh()->active);
        $this->assertTrue($second->fresh()->active);
    }

    public function test_nested_reference_values_are_rejected_without_writing_data(): void
    {
        $this->operator();

        $this->postJson(route('research-profiles.store'), $this->payload([
            $this->settings($this->assay(), ['ref_kve' => ['invalid']]),
        ]))->assertUnprocessable()->assertJsonValidationErrors('assays.0.reference');

        $this->assertDatabaseCount('researchprofiles', 0);
    }

    public function test_editing_requires_permission(): void
    {
        $this->operator();
        Gate::define('research-profiles.manage', fn () => false);

        $this->postJson(route('research-profiles.store'), $this->payload([]))->assertForbidden();
        $this->assertDatabaseCount('researchprofiles', 0);
    }

    private function operator(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('research-profiles.manage', fn () => true);
    }

    private function assay(string $name = 'Plate count', string $script = 'file:maz7218'): Assay
    {
        return Assay::create([
            'original_id' => 0, 'name' => $name, 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10,
            'script' => $script, 'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'r',
            'article_code' => '',
        ]);
    }

    private function profile(array $attributes = []): ResearchProfile
    {
        $profile = ResearchProfile::create(['original_id' => 0, 'name' => 'Profile', 'global' => 1, 'client' => 0, ...$attributes]);
        $profile->update(['original_id' => $profile->id]);

        return $profile;
    }

    private function source(?int $client = null): ReferenceSource
    {
        return ReferenceSource::create(['name' => ['nl' => 'Source'], 'client' => $client]);
    }

    private function settings(Assay $assay, array $reference = [], ?ReferenceSource $source = null): array
    {
        return [
            'assay' => $assay->id, 'dillutions' => [], 'replicates' => 0,
            'reference' => $reference, 'conf_trip' => 0, 'reference_source' => $source?->id,
        ];
    }

    private function payload(array $assays): array
    {
        return ['name' => 'Profile', 'global' => true, 'client' => null, 'portal_visible' => false, 'lims_visible' => true, 'assays' => $assays];
    }
}
