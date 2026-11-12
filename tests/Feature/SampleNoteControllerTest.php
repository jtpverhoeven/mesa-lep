<?php

namespace Tests\Feature;

use App\Events\UsersMentionedInNote;
use App\Models\ChangeRevision;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Concerns\CreatesNoteFixtures;
use Tests\TestCase;

class SampleNoteControllerTest extends TestCase
{
    use CreatesNoteFixtures;
    use RefreshDatabase;

    public function test_notes_are_listed_newest_first_with_author_and_system_notes(): void
    {
        Gate::define('samples.view', fn () => true);
        $author = $this->user(['name' => 'Anna Analist']);
        $sample = $this->sample($this->project());
        Note::factory()->forNotable($sample)->system()->create(['body' => 'Legacy tekst', 'created_at' => '2026-01-01 10:00:00']);
        Note::factory()->forNotable($sample)->for($author)->create(['body' => 'Nieuwe notitie', 'created_at' => '2026-02-01 10:00:00']);
        Note::factory()->report()->forNotable($sample)->create();

        $response = $this->actingAs($this->user())->getJson(route('samples.notes.index', $sample))->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.body', 'Nieuwe notitie');
        $response->assertJsonPath('data.0.author.name', 'Anna Analist');
        $response->assertJsonPath('data.1.body', 'Legacy tekst');
        $response->assertJsonPath('data.1.author', null);
    }

    public function test_listing_requires_a_view_permission(): void
    {
        $sample = $this->sample($this->project());

        $this->actingAs($this->user())->getJson(route('samples.notes.index', $sample))->assertForbidden();
    }

    public function test_adding_a_note_requires_the_notes_permission(): void
    {
        $sample = $this->sample($this->project());

        $this->actingAs($this->user())->postJson(route('samples.notes.store', $sample), ['body' => 'Hallo'])->assertForbidden();

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_adding_a_note_stores_it_for_the_author_and_keeps_only_mentions_present_in_the_text(): void
    {
        Event::fake([UsersMentionedInNote::class]);
        Gate::define('samples.notes.update', fn () => true);
        Gate::define('samples.view', fn () => true);
        $author = $this->user();
        $mentioned = $this->user(['name' => 'Piet Pipet']);
        $absent = $this->user(['name' => 'Karin Kweek']);
        $disabled = $this->user(['name' => 'Dirk Disabled', 'enabled' => false]);
        $sample = $this->sample($this->project());

        $response = $this->actingAs($author)->postJson(route('samples.notes.store', $sample), [
            'body' => 'Graag controleren @Piet Pipet en @Dirk Disabled',
            'mentions' => [$mentioned->id, $absent->id, $disabled->id],
        ])->assertCreated();

        $note = Note::query()->sole();
        $this->assertSame($author->id, $note->user_id);
        $this->assertSame([$mentioned->id], $note->mentions()->pluck('users.id')->all());
        $response->assertJsonPath('data.0.mentions.0.name', 'Piet Pipet');
        Event::assertDispatched(UsersMentionedInNote::class, fn (UsersMentionedInNote $event): bool => $event->userIds === [$mentioned->id]);
    }

    public function test_a_blank_note_is_rejected(): void
    {
        Gate::define('samples.notes.update', fn () => true);
        $sample = $this->sample($this->project());

        $this->actingAs($this->user())->postJson(route('samples.notes.store', $sample), ['body' => ''])->assertUnprocessable();
    }

    public function test_authors_can_delete_their_own_notes_and_the_removal_is_tracked(): void
    {
        Gate::define('samples.notes.update', fn () => true);
        Gate::define('samples.view', fn () => true);
        $author = $this->user();
        $sample = $this->sample($this->project());
        $note = Note::factory()->forNotable($sample)->for($author)->create(['body' => 'Verwijder mij']);

        $this->actingAs($author)->deleteJson(route('samples.notes.destroy', [$sample, $note]))->assertOk()->assertJsonCount(0, 'data');

        $this->assertModelMissing($note);
        $this->assertSame('Verwijder mij', ChangeRevision::query()->where('type', '29')->sole()->from);
    }

    public function test_notes_of_other_authors_and_report_notes_cannot_be_deleted_here(): void
    {
        Gate::define('samples.notes.update', fn () => true);
        $sample = $this->sample($this->project());
        $foreign = Note::factory()->forNotable($sample)->create();
        $report = Note::factory()->report()->forNotable($sample)->create();
        $user = $this->user();

        $this->actingAs($user)->deleteJson(route('samples.notes.destroy', [$sample, $foreign]))->assertForbidden();
        $this->actingAs($report->user)->deleteJson(route('samples.notes.destroy', [$sample, $report]))->assertNotFound();

        $this->assertDatabaseCount('notes', 2);
    }
}
