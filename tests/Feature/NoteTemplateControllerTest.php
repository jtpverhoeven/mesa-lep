<?php

namespace Tests\Feature;

use App\Models\NoteTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Concerns\CreatesNoteFixtures;
use Tests\TestCase;

class NoteTemplateControllerTest extends TestCase
{
    use CreatesNoteFixtures;
    use RefreshDatabase;

    public function test_templates_require_the_project_notes_permission(): void
    {
        $this->actingAs($this->user())->getJson(route('note-templates.index'))->assertForbidden();
        $this->actingAs($this->user())->postJson(route('note-templates.store'), ['name' => 'X', 'content' => '<p>X</p>'])->assertForbidden();
    }

    public function test_templates_are_listed_alphabetically(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        NoteTemplate::factory()->create(['name' => 'Zeta']);
        NoteTemplate::factory()->create(['name' => 'Alfa']);

        $this->actingAs($this->user())->getJson(route('note-templates.index'))->assertOk()
            ->assertJsonPath('data.0.name', 'Alfa')->assertJsonPath('data.1.name', 'Zeta');
    }

    public function test_saving_under_an_existing_name_overwrites_the_sanitized_content(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $user = $this->user();

        $this->actingAs($user)->postJson(route('note-templates.store'), ['name' => ' Monster bevroren ', 'content' => '<p>Een</p>'])->assertCreated();
        $this->actingAs($user)->postJson(route('note-templates.store'), ['name' => 'Monster bevroren', 'content' => '<p>Twee</p><script>x()</script>'])->assertCreated()
            ->assertJsonCount(1, 'data');

        $this->assertSame('<p>Twee</p>', NoteTemplate::query()->sole()->content);
    }

    public function test_templates_can_be_deleted(): void
    {
        Gate::define('projects.notes.create', fn () => true);
        $template = NoteTemplate::factory()->create();

        $this->actingAs($this->user())->deleteJson(route('note-templates.destroy', $template))->assertOk()->assertJsonCount(0, 'data');

        $this->assertModelMissing($template);
    }
}
