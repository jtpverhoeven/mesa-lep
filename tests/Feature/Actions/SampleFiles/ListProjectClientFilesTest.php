<?php

namespace Tests\Feature\Actions\SampleFiles;

use App\Actions\SampleFiles\ListProjectClientFiles;
use App\Models\FileHistory;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListProjectClientFilesTest extends TestCase
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

    public function test_files_are_visible_project_scoped_ordered_and_include_delivery_status(): void
    {
        $project = $this->project();
        $laterSample = $this->sample($project, 'B-200');
        $earlierSample = $this->sample($project, 'A-100');
        $outsideProject = $this->project();
        $outsideSample = $this->sample($outsideProject, 'A-001');
        $laterFile = SampleFile::factory()->create(['sample_id' => $laterSample->id, 'visible_for_client' => 1]);
        $earlierFile = SampleFile::factory()->create(['sample_id' => $earlierSample->id, 'visible_for_client' => 1]);
        SampleFile::factory()->create(['sample_id' => $earlierSample->id, 'visible_for_client' => 0]);
        SampleFile::factory()->create(['sample_id' => $outsideSample->id, 'visible_for_client' => 1]);
        FileHistory::factory()->create([
            'sample_id' => $laterSample->id,
            'samplefile_id' => $laterFile->id,
            'hash_name' => $laterFile->hash_name,
        ]);

        $files = app(ListProjectClientFiles::class)->handle($project);

        $this->assertSame([$earlierFile->id, $laterFile->id], $files->modelKeys());
        $this->assertSame(0, $files[0]->sent_count);
        $this->assertSame('Nog niet verstuurd', $files[0]->sent_label);
        $this->assertSame(1, $files[1]->sent_count);
        $this->assertSame('Verstuurd, 1 keer', $files[1]->sent_label);
        $this->assertSame('A-100', $files[0]->sample->barcode);
    }

    public function test_sample_scope_is_restricted_to_visible_files_in_the_given_project(): void
    {
        $project = $this->project();
        $sample = $this->sample($project, 'P-100');
        $outsideProject = $this->project();
        $outsideSample = $this->sample($outsideProject, 'X-100');
        $visibleFile = SampleFile::factory()->create(['sample_id' => $sample->id, 'visible_for_client' => 1]);
        SampleFile::factory()->create(['sample_id' => $outsideSample->id, 'visible_for_client' => 1]);

        $action = app(ListProjectClientFiles::class);

        $files = $action->handle($project, [$sample->id, $outsideSample->id]);
        $this->assertSame([$visibleFile->id], $files->modelKeys());
        $this->assertSame([], $action->handle($project, [])->modelKeys());
    }

    public function test_project_without_samples_returns_an_empty_file_collection(): void
    {
        $project = $this->project();

        $this->assertCount(0, app(ListProjectClientFiles::class)->handle($project));
    }

    private function project(): Project
    {
        return Project::query()->create(['client' => 1, 'subclient' => 0]);
    }

    private function sample(Project $project, string $barcode): Sample
    {
        return Sample::query()->create([
            'barcode' => $barcode,
            'follow_no' => 0,
            'description' => '',
            'sampling_method' => 1,
            'date_registered' => '2026-11-08',
            'registered_by' => 1,
            'client' => 1,
            'subclient' => 0,
            'project' => $project->id,
            'custom_fields' => '{}',
            'predicted_end' => 0,
            'sample_innoculated' => '2026-11-08',
            'analyses_data' => '{}',
        ]);
    }
}
