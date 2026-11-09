<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\SampleAnalysis;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectReadyStateTest extends TestCase
{
    private array $analysisAttributes = ['profile_group' => 1, 'sample' => 1, 'follow_number' => 1, 'profile' => 1, 'assay' => 1, 'assay_base' => 1, 'predicted_end' => 0];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }
    }

    public function test_project_follows_the_readiness_of_its_analyses(): void
    {
        $project = Project::query()->create(['client' => 1, 'subclient' => 0, 'is_ready' => 0]);
        $first = SampleAnalysis::query()->create([...$this->analysisAttributes, 'project' => $project->id, 'is_ready' => false]);
        $second = SampleAnalysis::query()->create([...$this->analysisAttributes, 'project' => $project->id, 'is_ready' => false]);

        $first->update(['is_ready' => true]);
        $this->assertSame(0, (int) $project->fresh()->is_ready);

        $second->update(['is_ready' => true]);
        $this->assertSame(1, (int) $project->fresh()->is_ready);
        $this->assertNotNull($project->fresh()->became_ready_on);

        $second->update(['is_ready' => false]);
        $this->assertSame(0, (int) $project->fresh()->is_ready);

        $second->delete();
        $this->assertSame(1, (int) $project->fresh()->is_ready);
    }
}
