<?php

namespace Tests\Feature;

use App\Actions\Projects\DeleteProjectSearchSample;
use App\Actions\Samples\CreateRegisteredSample;
use App\Enums\NoteKind;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Concerns\CreatesNoteFixtures;
use Tests\TestCase;

class SampleNotePayloadsTest extends TestCase
{
    use CreatesNoteFixtures;
    use RefreshDatabase;

    public function test_project_search_flags_samples_with_notes_and_returns_the_project_note(): void
    {
        Gate::define('projects.view', fn () => true);
        $project = $this->project();
        $withInternal = $this->sample($project, '26111201');
        $withReport = $this->sample($project, '26111202');
        $this->sample($project, '26111203');
        Note::factory()->forNotable($withInternal)->create();
        Note::factory()->report()->forNotable($withReport)->create();
        Note::factory()->report()->forNotable($project)->create(['body' => '<p>Projecttekst</p>']);

        $response = $this->actingAs($this->user())->getJson(route('projects.search.show', $project))->assertOk();

        $response->assertJsonPath('data.project_info.report_note.body', '<p>Projecttekst</p>');
        $this->assertSame(
            [[true, false], [false, true], [false, false]],
            array_map(fn (array $sample): array => [$sample['has_internal_note'], $sample['has_report_note']], $response->json('data.samples')),
        );
    }

    public function test_project_search_sample_details_include_both_note_kinds(): void
    {
        Gate::define('projects.view', fn () => true);
        $project = $this->project();
        $sample = $this->sample($project);
        Note::factory()->forNotable($sample)->create(['body' => 'Intern']);
        Note::factory()->report()->forNotable($sample)->create(['body' => '<p>Rapport</p>']);

        $response = $this->actingAs($this->user())->getJson(route('projects.search.samples.show', [$project, $sample]))->assertOk();

        $response->assertJsonPath('data.notes.0.body', 'Intern');
        $response->assertJsonPath('data.report_note.body', '<p>Rapport</p>');
    }

    public function test_registering_a_sample_stores_its_note_as_an_internal_note_of_the_registering_user(): void
    {
        $registrar = $this->user();
        $existing = $this->sample($this->project());

        $sample = app(CreateRegisteredSample::class)->handle([
            'client' => 1, 'registered_by' => $registrar->id, 'sample_note' => 'Bij binnenkomst bevroren',
        ], $existing->project()->first(), printLabels: false);

        $note = $sample->internalNotes()->sole();
        $this->assertSame('Bij binnenkomst bevroren', $note->body);
        $this->assertSame($registrar->id, $note->user_id);
        $this->assertNull($sample->fresh()->sample_note);
    }

    public function test_registering_a_sample_without_a_note_creates_none(): void
    {
        $existing = $this->sample($this->project());

        app(CreateRegisteredSample::class)->handle(['client' => 1, 'registered_by' => $this->user()->id], $existing->project()->first(), printLabels: false);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_deleting_a_sample_removes_its_notes(): void
    {
        $project = $this->project();
        $sample = $this->sample($project);
        $other = $this->sample($project, '26111202');
        Note::factory()->forNotable($sample)->create();
        Note::factory()->report()->forNotable($sample)->create();
        Note::factory()->forNotable($other)->create();

        $this->actingAs($this->user());
        app(DeleteProjectSearchSample::class)->handle($project, $sample);

        $this->assertSame([$other->id], Note::query()->pluck('notable_id')->map(fn ($id): int => (int) $id)->all());
        $this->assertSame(1, Note::query()->ofKind(NoteKind::Internal)->count());
    }
}
