<?php

namespace Tests\Feature;

use App\Models\ChangeRevision;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Concerns\CreatesNoteFixtures;
use Tests\TestCase;

class ReportNoteControllerTest extends TestCase
{
    use CreatesNoteFixtures;
    use RefreshDatabase;

    public function test_saving_requires_the_project_notes_permission(): void
    {
        $project = $this->project();

        $this->actingAs($this->user())->putJson(route('projects.report-note.update', $project), ['body' => '<p>Tekst</p>'])->assertForbidden();
    }

    public function test_project_note_is_sanitized_attributed_and_tracked(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $author = $this->user(['name' => 'Anna Analist']);
        $project = $this->project();

        $response = $this->actingAs($author)->putJson(route('projects.report-note.update', $project), [
            'body' => '<p onclick="x()">Aandacht&nbsp;<strong>nodig</strong></p><script>alert(1)</script>',
        ])->assertOk();

        $note = $project->reportNote()->sole();
        $this->assertSame('<p>Aandacht <strong>nodig</strong></p>', $note->body);
        $this->assertSame($author->id, $note->user_id);
        $response->assertJsonPath('data.author.name', 'Anna Analist');
        $revision = ChangeRevision::query()->where('type', '20')->sole();
        $this->assertSame('Globale project notities gewijzigd', $revision->event);
        $this->assertSame($project->id, (int) $revision->project);
        $this->assertSame('Aandacht nodig', $revision->to);
    }

    public function test_saving_again_updates_the_single_note_and_a_blank_body_removes_it(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $project = $this->project();
        Note::factory()->report()->forNotable($project)->create();
        $editor = $this->user();

        $this->actingAs($editor)->putJson(route('projects.report-note.update', $project), ['body' => '<p>Twee</p>'])->assertOk();

        $this->assertSame('<p>Twee</p>', $project->reportNote()->sole()->body);
        $this->assertSame($editor->id, $project->reportNote()->sole()->user_id);

        $this->putJson(route('projects.report-note.update', $project), ['body' => '<p><br></p>'])->assertOk()->assertJsonPath('data', null);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_sample_note_is_scoped_to_its_project_and_tracked_with_the_barcode(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $project = $this->project();
        $sample = $this->sample($project);

        $this->actingAs($this->user())->putJson(route('projects.samples.report-note.update', [$project, $sample]), ['body' => '<p>Monsteropmerking</p>'])->assertOk();

        $this->assertSame('<p>Monsteropmerking</p>', $sample->reportNote()->sole()->body);
        $this->assertSame('Project notitie voor monster gewijzigd, '.$sample->barcode, ChangeRevision::query()->where('type', '20')->sole()->event);

        $other = $this->project(['project_name' => 'Ander project']);
        $this->putJson(route('projects.samples.report-note.update', [$other, $sample]), ['body' => '<p>Fout</p>'])->assertNotFound();
    }

    public function test_notes_of_authorized_projects_are_locked(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $project = $this->project(['auth_status' => 1]);

        $this->actingAs($this->user())->putJson(route('projects.report-note.update', $project), ['body' => '<p>Te laat</p>'])->assertStatus(423);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_internal_notes_never_replace_the_report_note(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $project = $this->project();
        $sample = $this->sample($project);
        Note::factory()->forNotable($sample)->create(['body' => 'Intern']);

        $this->actingAs($this->user())->putJson(route('projects.samples.report-note.update', [$project, $sample]), ['body' => '<p>Rapport</p>'])->assertOk();

        $this->assertSame(['Intern', '<p>Rapport</p>'], Note::query()->orderBy('id')->pluck('body')->all());
    }
}
