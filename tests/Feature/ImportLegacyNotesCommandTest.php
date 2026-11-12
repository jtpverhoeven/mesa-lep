<?php

namespace Tests\Feature;

use App\Enums\NoteKind;
use App\Models\Note;
use App\Models\NoteTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesNoteFixtures;
use Tests\TestCase;

class ImportLegacyNotesCommandTest extends TestCase
{
    use CreatesNoteFixtures;
    use RefreshDatabase;

    public function test_legacy_columns_become_system_and_report_notes(): void
    {
        $project = $this->project(['project_notes' => json_encode(['project' => "Eerste regel\n<b>Tweede</b>"], JSON_FORCE_OBJECT), 'last_edit' => 1791590400]);
        $sample = $this->sample($project, attributes: ['sample_note' => ' Intern & belangrijk ']);
        $project->update(['project_notes' => json_encode(['project' => "Eerste regel\n<b>Tweede</b>", $sample->id => 'Monsterregel', '999' => 'Weg'], JSON_FORCE_OBJECT)]);

        $this->artisan('legacy:import-notes', ['--templates' => 'missing.csv'])->assertSuccessful();

        $internal = $sample->notes()->ofKind(NoteKind::Internal)->sole();
        $this->assertSame('Intern & belangrijk', $internal->body);
        $this->assertNull($internal->user_id);
        $this->assertSame(Note::ORIGIN_LEGACY, $internal->origin);
        $this->assertSame('1791590400', (string) $internal->created_at->timestamp);
        $this->assertSame('<p>Eerste regel</p><p>&lt;b&gt;Tweede&lt;/b&gt;</p>', $project->reportNote()->sole()->body);
        $this->assertSame('<p>Monsterregel</p>', $sample->reportNote()->sole()->body);
        $this->assertDatabaseCount('notes', 3);
    }

    public function test_rerunning_does_not_duplicate_or_overwrite_notes(): void
    {
        $project = $this->project(['project_notes' => '{"project":"Oud"}']);
        $sample = $this->sample($project, attributes: ['sample_note' => 'Oud']);
        $this->artisan('legacy:import-notes')->assertSuccessful();
        $project->reportNote->update(['body' => '<p>Nieuw bewerkt</p>']);

        $this->artisan('legacy:import-notes')->assertSuccessful();

        $this->assertDatabaseCount('notes', 2);
        $this->assertSame('<p>Nieuw bewerkt</p>', $project->reportNote()->sole()->body);
        $this->assertSame(1, $sample->internalNotes()->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $project = $this->project(['project_notes' => '{"project":"Oud"}']);
        $this->sample($project, attributes: ['sample_note' => 'Oud']);

        $this->artisan('legacy:import-notes', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_templates_are_imported_once_from_the_csv(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'notes');
        file_put_contents($path, "id,name,content\n1,Bevroren,\"Regel een\nRegel twee\"\n2,Leeg,\"\"\n");
        NoteTemplate::factory()->create(['name' => 'Bevroren', 'content' => '<p>Eigen versie</p>']);

        $this->artisan('legacy:import-notes', ['--templates' => $path])->assertSuccessful();
        unlink($path);

        $this->assertSame(['<p>Eigen versie</p>'], NoteTemplate::query()->pluck('content')->all());
    }

    public function test_new_templates_are_converted_to_html(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'notes');
        file_put_contents($path, "id,name,content\n1,Bevroren,\"Regel een\nRegel twee\"\n");

        $this->artisan('legacy:import-notes', ['--templates' => $path])->assertSuccessful();
        unlink($path);

        $this->assertSame('<p>Regel een</p><p>Regel twee</p>', NoteTemplate::query()->sole()->content);
    }
}
