<?php

namespace Tests\Feature;

use App\Actions\SampleBuffers\CommitSampleBuffers;
use App\ClientPortal\ClientPortalService;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\ResearchProfile;
use App\Models\SampleBuffer;
use App\Models\SampleProcedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommitSampleBuffersTest extends TestCase
{
    use RefreshDatabase;

    public function test_legionella_buffers_are_committed_with_their_legacy_type_extra_data_and_project(): void
    {
        $this->cvars(['LEGIONELLA_STD_DESCRIPTION' => 'Legionella monster', 'LEGIONELLA_FILTER_VOLUME_B' => '500', 'LEGIONELLA_FILTER_VOLUME_A' => '1000']);
        $profile = $this->profile();
        $client = Client::create(['name' => 'Waterbedrijf', 'active' => 1]);
        $this->projectFields();

        foreach ([1, 2] as $position) {
            $this->buffer($client, 3, [
                ['property_name' => 'matrixType', 'value' => 'B'],
                ['property_name' => 'matrixBaseProfile', 'value' => (string) $profile->id],
                ['property_name' => 'waterLineType', 'value' => 'Koud'],
                ['property_name' => 'waterTemperature', 'value' => '21'],
                ['property_name' => 'waterSamplingTime', 'value' => '09:30'],
            ], ['sample_name' => 'Kraan '.$position]);
        }

        $samples = $this->commit(SampleBuffer::pluck('id')->all());

        $this->assertCount(2, $samples);
        $this->assertDatabaseCount('samplebuffers', 0);
        $first = $samples->first()->fresh();
        $this->assertSame('L', $first->sample_type);
        $this->assertSame('B', $first->leg_type);
        $this->assertStringStartsWith('LB', $first->barcode);
        $this->assertSame('Legionella monster', $first->description);
        $this->assertSame('Kraan 1', $first->client_description);
        $this->assertSame(['follow' => 1, 'type' => 'Koud', 'temperature' => '21', 'filter_volume' => 500], json_decode($first->sample_extra, true));
        $this->assertSame(2, json_decode($samples->last()->fresh()->sample_extra, true)['follow']);
        $this->assertCount(1, $first->analyses);

        $project = Project::findOrFail($first->project);
        $this->assertSame(1, (int) $project->special_type);
        $this->assertSame($project->id, $samples->last()->project);
        $this->assertSame($first->barcode, $project->project_name);
        $fields = json_decode($project->custom_fields, true);
        $this->assertSame('09:30', $fields['project_bemonster_tijd']);
        $this->assertSame('???', $fields['project_extra_field']);
        $this->assertSame('20-09-2026', $fields['project_monster']);
        $this->assertSame('10-11-2026', $fields['project_ontvangst']);
        $this->assertSame('11:15', $fields['project_tijd_ontvangst']);
    }

    public function test_rodac_buffers_are_committed_with_their_legacy_type_location_and_project_extra(): void
    {
        $this->cvars(['RODAC_STD_DESCRIPTION' => 'Rodac afdrukje', 'MESA_STD_RODACSMPL_METHOD' => '7']);
        $client = Client::create(['name' => 'Ziekenhuis', 'active' => 1]);
        $this->projectFields();
        $this->buffer($client, 2, [
            ['property_name' => 'rodacRoom', 'value' => 'OK 3'],
            ['property_name' => 'rodacSamplingTime', 'value' => '08:00'],
        ]);

        $sample = $this->commit(SampleBuffer::pluck('id')->all())->first()->fresh();

        $this->assertSame('R', $sample->sample_type);
        $this->assertSame('Rodac afdrukje', $sample->description);
        $this->assertSame(['follow' => 1, 'location' => 'OK 3'], json_decode($sample->sample_extra, true));
        $project = Project::findOrFail($sample->project);
        $this->assertSame(2, (int) $project->special_type);
        $this->assertSame(['sample_method' => '7', 'number_of_blanks' => '0'], json_decode($project->project_extra, true));
        $this->assertSame('08:00', json_decode($project->custom_fields, true)['project_bemonster_tijd']);
    }

    public function test_rodac_buffers_are_inoculated_only_when_auto_registration_is_enabled(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        $client = Client::create(['name' => 'Ziekenhuis', 'active' => 1]);
        $this->projectFields();

        foreach (['0' => false, '1' => true] as $setting => $expected) {
            $this->cvars(['RODAC_AUTO_REGISTER_INNOCULATION' => (string) $setting]);
            $this->buffer($client, 2, [['property_name' => 'rodacRoom', 'value' => 'OK 3']], ['project' => 'PRJ-'.$setting]);

            $sample = $this->commit(SampleBuffer::pluck('id')->all())->first()->fresh();

            $this->assertSame($expected, filled($sample->sample_innoculated));
            $this->assertSame($expected, (bool) Project::findOrFail($sample->project)->started);
        }
    }

    public function test_normal_buffers_keep_legacy_sample_data_and_a_blank_project_name_falls_back_to_the_barcode(): void
    {
        $client = Client::create(['name' => 'Waterbedrijf', 'active' => 1]);
        foreach ([1, 2] as $position) {
            $this->buffer($client, 0, [], ['source' => 1, 'sample_name' => 'Monster '.$position, 'misc_directions' => 'Koel']);
        }
        DB::table('samplebuffers')->update(['analyses_selected' => 'Legionella']);

        $samples = $this->commit(SampleBuffer::pluck('id')->all());

        $sample = $samples->first()->fresh();
        $this->assertSame('S', $sample->sample_type);
        $this->assertSame(['details' => 'Detail'], json_decode($sample->custom_fields, true));
        $this->assertSame(['misc_directions' => 'Koel', 'analyses_selected' => 'Legionella'], json_decode($sample->analyses_data, true));
        $this->assertSame('Koel', $sample->portal_notes);
        $this->assertSame($sample->barcode, Project::findOrFail($sample->project)->project_name);
        $this->assertSame($sample->project, $samples->last()->project);
    }

    private function commit(array $ids)
    {
        $this->mock(ClientPortalService::class);

        return app(CommitSampleBuffers::class)->handle($ids, 1, ['receive_date' => '2026-11-10', 'receive_time' => '11:15']);
    }

    private function buffer(Client $client, int $type, array $properties, array $overrides = []): SampleBuffer
    {
        return SampleBuffer::create([
            'client' => $client->id, 'source' => 3, 'project' => 'PRJ-1-20092026', 'sampling_date' => '20-09-2026',
            'sampling_method' => SampleProcedure::query()->value('id') ?? SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0])->id,
            'sample_name' => 'Monster', 'sample_details' => 'Detail', 'tht' => false, 'meta' => [], 'analyses_selected' => '',
            'authorized' => false, 'project_name' => '', 'date_registered' => (string) now()->timestamp,
            'sample_research_type' => (string) $type, 'sample_properties' => $properties, ...$overrides,
        ]);
    }

    private function projectFields(): void
    {
        ProjectField::create(['name' => 'project_bemonster_tijd', 'alias' => 'Tijd', 'type' => 'text', 'std_value' => '', 'position' => 1]);
        ProjectField::create(['name' => 'project_extra_field', 'alias' => 'Extra', 'type' => 'text', 'std_value' => 'x', 'position' => 2]);
    }

    private function profile(): ResearchProfile
    {
        $profile = ResearchProfile::create(['original_id' => 1, 'name' => 'Legionella', 'global' => 1, 'client' => 0, 'active' => 1]);
        $assay = Assay::create([
            'original_id' => 41, 'name' => 'Legionella', 'type_base' => 0, 'media_id' => '{}',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
            'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '', 'active' => 1,
        ]);
        AssayProfile::create([
            'research_profile' => $profile->id, 'assay' => $assay->id, 'dillutions' => [1],
            'replicates' => 0, 'reference' => [], 'hidden' => 0, 'project_order' => 1,
        ]);

        return $profile;
    }

    private function cvars(array $values): void
    {
        foreach ($values as $name => $value) {
            Cvar::updateOrCreate(['cvar' => $name], ['value' => $value, 'default' => '', 'description' => '']);
        }
    }
}
