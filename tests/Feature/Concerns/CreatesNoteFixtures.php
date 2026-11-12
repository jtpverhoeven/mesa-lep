<?php

namespace Tests\Feature\Concerns;

use App\Models\Project;
use App\Models\Sample;
use App\Models\User;

trait CreatesNoteFixtures
{
    private function user(array $attributes = []): User
    {
        return User::factory()->create(['enabled' => true, ...$attributes]);
    }

    private function project(array $attributes = []): Project
    {
        return Project::create(['client' => 1, 'subclient' => 0, 'project_name' => 'Notitieproject', ...$attributes]);
    }

    private function sample(Project $project, string $barcode = '26111201', array $attributes = []): Sample
    {
        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1, 'description' => 'Notitiemonster', 'sampling_method' => 1,
            'date_registered' => '1791590400', 'registered_by' => 1, 'client' => 1, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => -1, 'sample_innoculated' => '',
            'analyses_data' => '[]', 'sample_type' => 'S', 'sample_extra' => '{}', ...$attributes,
        ]);
    }
}
