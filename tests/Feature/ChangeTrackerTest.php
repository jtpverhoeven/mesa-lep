<?php

namespace Tests\Feature;

use App\Actions\AssuranceForms\UpdateAssuranceFormField;
use App\Actions\Metadata\CreateMetadata;
use App\Actions\Metadata\DeleteMetadata;
use App\Actions\Metadata\UpdateMetadata;
use App\Actions\Projects\UpdateProjectSearchSample;
use App\ChangeTracking\ChangeTracker;
use App\Models\AssuranceForm;
use App\Models\ChangeRevision;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChangeTrackerTest extends TestCase
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

    public function test_records_legacy_values_without_normalizing_them(): void
    {
        $this->travelTo(now()->setTimestamp(1700000000));

        $revision = app(ChangeTracker::class)->changed('9', 12, false, false,
            'Project authorisatie gewijzigd', '0', '1', false, 7)->fresh();

        $this->assertSame('1700000000', $revision->timestamp);
        $this->assertSame('9', $revision->type);
        $this->assertSame('0', $revision->from);
        $this->assertSame('1', $revision->to);
        $this->assertDatabaseHas('changetracker', ['id' => $revision->id, 'project' => 12, 'user_id' => 7, 'sample' => null]);
    }

    public function test_sample_only_changes_are_visible_in_their_project(): void
    {
        $this->sample();

        $revision = app(ChangeTracker::class)->changed(18, sample: 15, event: 'Meta toegevoegd', from: false, to: '001.50', userId: 7);

        $this->assertDatabaseHas('changetracker', ['id' => $revision->id, 'project' => 12, 'sample' => 15, 'from' => '', 'to' => '001.50']);
    }

    public function test_revisions_roll_back_with_the_business_transaction(): void
    {
        DB::beginTransaction();
        app(ChangeTracker::class)->changed(13, assuranceForm: 4, from: 'key:0', to: 'key:1', userId: 7);
        DB::rollBack();

        $this->assertDatabaseCount('changetracker', 0);
    }

    public function test_unknown_legacy_types_and_missing_users_remain_readable(): void
    {
        $revision = app(ChangeTracker::class)->changed('custom-old-type', project: 12, userId: 999);

        $this->assertSame('custom-old-type', $revision->fresh()->type);
        $this->assertNull($revision->user);
    }

    public function test_does_not_write_an_unattributed_change(): void
    {
        try {
            app(ChangeTracker::class)->changed(1);
            $this->fail('An unattributed change must not be recorded.');
        } catch (LogicException) {
            $this->assertDatabaseCount('changetracker', 0);
        }
    }

    public function test_project_history_includes_legacy_sample_rows_and_filters_types(): void
    {
        $user = User::factory()->create(['enabled' => 1]);
        $this->actingAs($user);
        Gate::define('projects.view', fn () => true);
        Project::forceCreate(['id' => 12, 'client' => 1, 'subclient' => 0, 'project_name' => 'History']);
        $this->sample();
        ChangeRevision::create(['user_id' => 999, 'timestamp' => '1700000000', 'type' => '18',
            'project' => 0, 'sample' => 15, 'from' => '', 'to' => '001.50']);
        app(ChangeTracker::class)->changed(9, project: 12, from: '1', to: '0');
        app(ChangeTracker::class)->changed(9, project: 99, event: 'Unrelated');

        $this->getJson(route('revisions.index', ['scope' => 'project', 'id' => 12]))
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.type', '9')
            ->assertJsonPath('data.0.user_name', $user->name)->assertJsonMissing(['event' => 'Unrelated']);
        $this->getJson(route('revisions.index', ['scope' => 'project', 'id' => 12, 'types' => ['18']]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.to', '001.50');
    }

    public function test_history_requires_authentication_and_scope_permission(): void
    {
        $url = route('revisions.index', ['scope' => 'project', 'id' => 12]);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn () => false);

        $this->getJson($url)->assertForbidden();
    }

    public function test_history_rejects_invalid_scopes_and_cross_project_samples(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn () => true);
        $this->sample();

        $this->getJson(route('revisions.index', ['scope' => 'sample', 'id' => 15, 'project_id' => 99]))->assertNotFound();
        $this->getJson(route('revisions.index', ['scope' => 'from', 'id' => 15]))->assertUnprocessable()->assertJsonValidationErrors('scope');
        $this->getJson(route('revisions.index', ['scope' => 'analysis', 'id' => 15]))->assertUnprocessable()->assertJsonValidationErrors('sample_id');
    }

    private function sample(): Sample
    {
        return Sample::forceCreate([
            'id' => 15, 'project' => 12, 'client' => 1, 'subclient' => 0,
            'barcode' => 'TRACK-15', 'follow_no' => 1, 'description' => 'Sample', 'sampling_method' => 0,
            'date_registered' => '1700000000', 'registered_by' => 7, 'custom_fields' => '{}',
            'predicted_end' => 0, 'sample_innoculated' => '0', 'analyses_data' => '{}',
        ]);
    }

    public function test_history_paginates_without_leaking_other_projects_and_preserves_unknown_types(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('projects.view', fn () => true);
        Project::forceCreate(['id' => 12, 'client' => 1, 'subclient' => 0, 'project_name' => 'History']);
        for ($index = 1; $index <= 27; $index++) {
            app(ChangeTracker::class)->changed('old-custom-code', project: 12, event: 'Event '.$index, userId: 999);
        }

        $this->getJson(route('revisions.index', ['scope' => 'project', 'id' => 12]))
            ->assertOk()->assertJsonCount(25, 'data')->assertJsonPath('meta.total', 27)
            ->assertJsonPath('data.0.event', 'Event 27')->assertJsonPath('data.0.user_name', 'Gebruiker #999')
            ->assertJsonPath('data.0.type_label', 'Type old-custom-code');
        $this->getJson(route('revisions.index', ['scope' => 'project', 'id' => 12, 'page' => 2]))
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.1.event', 'Event 1');
    }

    #[DataProvider('revisionScopes')]
    public function test_history_returns_only_revisions_for_the_requested_scope(string $scope, string $permission, int $id): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define($permission, fn () => true);
        $this->sample();
        SampleAnalysis::forceCreate(['id' => 20, 'sample' => 15, 'project' => 12, 'profile_group' => 1, 'follow_number' => 1,
            'profile' => 0, 'assay' => 0, 'assay_base' => 1, 'predicted_end' => 0, 'project_order' => 1]);
        AssuranceForm::create(['id' => 30, 'date' => '1700000000', 'data' => '{}']);
        $matching = app(ChangeTracker::class)->changed(14, project: 12, sample: 15, said: 20, assuranceForm: 30, event: '<script>old value</script>');
        app(ChangeTracker::class)->changed(14, project: 99, sample: 99, said: 99, assuranceForm: 99);

        $this->getJson(route('revisions.index', ['scope' => $scope, 'id' => $id, 'sample_id' => 15]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.event', '<script>old value</script>');
        Gate::define($permission, fn () => false);
        $this->getJson(route('revisions.index', ['scope' => $scope, 'id' => $id, 'sample_id' => 15]))->assertForbidden();
    }

    public static function revisionScopes(): array
    {
        return ['sample' => ['sample', 'samples.view', 15], 'analysis' => ['analysis', 'samples.view', 20],
            'assurance form' => ['assurance-form', 'assurance-form.view', 30]];
    }

    public function test_missing_legacy_parents_do_not_prevent_recording_a_revision(): void
    {
        $revision = app(ChangeTracker::class)->changed(1, sample: 999, said: 888, userId: 7);

        $this->assertDatabaseHas('changetracker', ['id' => $revision->id, 'project' => null, 'sample' => 999, 'said' => 888]);
    }

    public function test_metadata_mutations_preserve_complete_old_and_new_values(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        $sample = $this->sample();
        $old = str_repeat('Original ', 100);
        $new = str_repeat('Updated ', 100);

        $metadata = app(CreateMetadata::class)->handle($sample, 'Long metadata', $old);
        app(UpdateMetadata::class)->handle($metadata, 'Long metadata', $new);
        app(DeleteMetadata::class)->handle($metadata->fresh());

        $this->assertDatabaseCount('changetracker', 3);
        $this->assertDatabaseHas('changetracker', ['type' => '18', 'sample' => 15, 'from' => 'Long metadata', 'to' => $old]);
        $this->assertDatabaseHas('changetracker', ['type' => '18', 'sample' => 15, 'from' => $old, 'to' => $new]);
        $this->assertDatabaseHas('changetracker', ['type' => '19', 'sample' => 15, 'from' => 'Long metadata', 'to' => $new]);
        $this->assertModelMissing($metadata);
    }

    public function test_audit_failure_rolls_back_the_sample_edit(): void
    {
        $sample = $this->sample();
        $project = Project::forceCreate(['id' => 12, 'client' => 1, 'subclient' => 0, 'project_name' => 'History']);

        try {
            app(UpdateProjectSearchSample::class)->handle($project, $sample, ['source' => 'attribute', 'field' => 'description', 'value' => 'Changed']);
            $this->fail('An unattributed edit must roll back.');
        } catch (LogicException) {
            $this->assertSame('Sample', $sample->fresh()->description);
            $this->assertDatabaseCount('changetracker', 0);
        }
    }

    public function test_assurance_user_fields_record_legacy_names_rather_than_user_ids(): void
    {
        $user = User::factory()->create(['enabled' => 1]);
        $this->actingAs($user);
        $form = AssuranceForm::create(['date' => '1700000000', 'data' => json_encode([
            0 => ['beheer' => '99'], 'users_available_at_time' => ['99' => 'Former analyst', (string) $user->id => $user->name],
        ])]);

        app(UpdateAssuranceFormField::class)->handle($form, 'b0_beheer', (string) $user->id);

        $this->assertDatabaseHas('changetracker', ['type' => '13', 'assurance_form' => $form->id,
            'event' => 'Veld gewijzigd: Beheer monsteronderzoek', 'from' => 'Former analyst', 'to' => $user->name]);
    }
}
