<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RunningConfirmationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.view', fn () => true);
    }

    public function test_lists_only_unfinished_requested_confirmations_on_confirmation_assays(): void
    {
        $client = Client::create(['name' => 'Bakkerij Jansen']);
        $sample = $this->sample('26091000', $client->id);
        $confirmationAssay = $this->assay(['name' => 'Salmonella', 'confirmation' => 1]);
        $otherConfirmationAssay = $this->assay(['name' => 'Listeria', 'confirmation' => 1]);
        $plainAssay = $this->assay(['name' => 'Totaal kiemgetal']);

        $running = $this->analysis($sample, $confirmationAssay, ['follow_number' => 2]);
        $this->analysis($sample, $otherConfirmationAssay, ['follow_number' => 3]);
        $this->analysis($sample, $confirmationAssay, ['conf_requested' => 1, 'is_ready' => 1]);
        $this->analysis($sample, $confirmationAssay, ['conf_requested' => 0]);
        $this->analysis($sample, $confirmationAssay, ['conf_requested' => 2]);
        $this->analysis($sample, $plainAssay);
        SampleAnalysis::create(['profile_group' => 1, 'sample' => 999999, 'follow_number' => 1, 'profile' => 0, 'assay' => 0, 'predicted_end' => -1, 'assay_base' => $confirmationAssay->id, 'conf_requested' => 1, 'is_ready' => 0, 'project' => $sample->project]);

        $this->get(route('samples.running-confirmations'))
            ->assertOk()
            ->assertViewIs('samples.running-confirmations')
            ->assertViewHas('confirmations', fn (array $confirmations): bool => count($confirmations) === 2
                && $confirmations[0]['assay_name'] === 'Listeria'
                && $confirmations[1] === [
                    'id' => $running->id,
                    'barcode' => '26091000',
                    'barcode_specific' => '26091000.2',
                    'description' => 'Water sample',
                    'client_id' => $client->id,
                    'client' => 'Bakkerij Jansen',
                    'assay_name' => 'Salmonella',
                    'project' => (int) $sample->project,
                ]);
    }

    public function test_page_requires_sample_view_permission(): void
    {
        Gate::define('samples.view', fn () => false);

        $this->get(route('samples.running-confirmations'))->assertForbidden();
    }

    private function sample(string $barcode, int $clientId): Sample
    {
        $projectId = Project::create(['client' => $clientId, 'subclient' => 0, 'project_name' => 'Project water'])->id;

        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1000, 'description' => 'Water sample',
            'sampling_method' => 1, 'date_registered' => '1788998400', 'registered_by' => 1,
            'client' => $clientId, 'subclient' => 0, 'project' => $projectId,
            'custom_fields' => '{}', 'predicted_end' => 1789171200,
            'sample_innoculated' => '', 'analyses_data' => '[]',
        ]);
    }

    private function assay(array $attributes = []): Assay
    {
        return Assay::create([
            'original_id' => 1, 'name' => 'Total count', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10,
            'script' => '', 'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
            ...$attributes,
        ]);
    }

    private function analysis(Sample $sample, Assay $assay, array $attributes = []): SampleAnalysis
    {
        return SampleAnalysis::create([
            'profile_group' => 1, 'sample' => $sample->id, 'follow_number' => 1, 'profile' => 0, 'assay' => 0, 'predicted_end' => -1,
            'assay_base' => $assay->id, 'original_assay_base' => $assay->id,
            'conf_requested' => 1, 'is_ready' => 0, 'project' => $sample->project,
            ...$attributes,
        ]);
    }
}
