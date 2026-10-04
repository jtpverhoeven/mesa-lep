<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SampleBufferPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_includes_legacy_csv_and_portal_analysis_summaries(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('portal.access', fn (): bool => true);
        DB::table('samplebuffers')->insert([
            $this->buffer([
                'id' => 1,
                'source' => 1,
                'analyses_selected' => 'Legionella + kiemgetal',
                'misc_directions' => 'Koel vervoeren',
            ]),
            $this->buffer([
                'id' => 2,
                'source' => 3,
                'portal_analyses' => json_encode([
                    'profile_id' => 8,
                    'profile' => 'Waterprofiel',
                    'assays_addition' => ['Extra analyse'],
                    'assays_substraction' => ['Vervallen analyse'],
                ]),
            ]),
        ]);

        $response = $this->getJson(route('sample-buffers.data', [
            'tab' => 'normal',
            'sort' => 'project',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.analysis_summary.0.label', 'Legionella + kiemgetal')
            ->assertJsonPath('data.0.analysis_summary.0.kind', 'requested')
            ->assertJsonPath('data.0.misc_directions', 'Koel vervoeren')
            ->assertJsonPath('data.1.analysis_summary.0.label', 'Waterprofiel')
            ->assertJsonPath('data.1.analysis_summary.0.kind', 'profile')
            ->assertJsonPath('data.1.analysis_summary.1.kind', 'added')
            ->assertJsonPath('data.1.analysis_summary.2.kind', 'removed');
    }

    private function buffer(array $overrides): array
    {
        return [
            'id' => 1,
            'client' => 1,
            'source' => 1,
            'project' => 'PRJ-1-20092026',
            'sampling_date' => '20-09-2026',
            'sampling_method' => 0,
            'sample_name' => 'Watermonster',
            'sample_details' => '',
            'tht' => 0,
            'meta' => '{}',
            'analyses_selected' => '',
            'misc_directions' => null,
            'authorized' => 0,
            'portal_analyses' => null,
            'sample_research_type' => '0',
            'sample_properties' => '[]',
            ...$overrides,
        ];
    }
}
