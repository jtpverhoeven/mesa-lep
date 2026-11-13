<?php

namespace Tests\Feature;

use App\Models\Assay;
use App\Models\ConfirmationTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfirmationTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (glob(database_path('migrations/*.php')) as $path) {
            (require $path)->up();
        }

        Storage::fake('local');
    }

    public function test_index_lists_tables_with_the_number_of_active_assays_using_them(): void
    {
        $this->administrator();
        $table = ConfirmationTable::factory()->create(['name' => 'Salmonella']);
        ConfirmationTable::factory()->create(['name' => 'Listeria']);
        $this->assay(['active' => 1])->confirmationTables()->attach($table);
        $this->assay(['active' => 0])->confirmationTables()->attach($table);

        $this->get(route('confirmation-tables.index'))
            ->assertOk()
            ->assertSee('<confirmation-table-directory', false)
            ->assertViewHas('tables', fn ($tables): bool => $tables->pluck('assays_count', 'name')->all() === ['Listeria' => 0, 'Salmonella' => 1]);
    }

    public function test_a_table_is_created_and_updated_with_its_html(): void
    {
        $this->administrator();

        $this->post(route('confirmation-tables.store'), ['name' => 'Vibrio', 'html' => '<p>Eén</p>'])
            ->assertRedirect(route('confirmation-tables.edit', 1));
        $this->put(route('confirmation-tables.update', 1), ['name' => 'Vibrio spp', 'html' => '<p>Twee</p>'])
            ->assertRedirect(route('confirmation-tables.edit', 1));

        $this->assertDatabaseHas('confirmationtables', ['id' => 1, 'name' => 'Vibrio spp', 'html' => '<p>Twee</p>']);
        $this->get(route('confirmation-tables.edit', 1))->assertOk()->assertSee('<confirmation-table-editor', false);
    }

    public function test_the_name_is_required_and_unique_but_may_be_kept_when_updating(): void
    {
        $this->administrator();
        $existing = ConfirmationTable::factory()->create(['name' => 'Salmonella']);
        $other = ConfirmationTable::factory()->create(['name' => 'Listeria']);

        $this->post(route('confirmation-tables.store'), ['name' => 'Salmonella'])->assertSessionHasErrors('name');
        $this->post(route('confirmation-tables.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->put(route('confirmation-tables.update', $other), ['name' => 'Salmonella'])->assertSessionHasErrors('name');
        $this->put(route('confirmation-tables.update', $existing), ['name' => 'Salmonella', 'html' => '<p>Nieuw</p>'])->assertSessionDoesntHaveErrors();
    }

    public function test_embedded_images_are_stored_privately_and_served_to_signed_in_users(): void
    {
        $this->administrator();
        $png = $this->png(0xFF0000);

        $this->post(route('confirmation-tables.store'), [
            'name' => 'Afbeelding',
            'html' => '<p><img src="data:image/png;base64,'.base64_encode($png).'"></p>',
        ]);

        $table = ConfirmationTable::query()->firstOrFail();
        $name = sha1($png).'.png';
        $url = route('confirmation-tables.images.show', [$table, $name], false);

        $this->assertSame('<p><img src="'.$url.'"></p>', $table->html);
        Storage::disk('local')->assertExists("confirmation-tables/{$table->id}/{$name}");
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_images_that_are_not_valid_pictures_stay_untouched(): void
    {
        $this->administrator();
        $html = '<p><img src="data:image/png;base64,'.base64_encode('geen afbeelding').'"></p>';

        $this->post(route('confirmation-tables.store'), ['name' => 'Kapot', 'html' => $html]);

        $this->assertSame($html, ConfirmationTable::query()->firstOrFail()->html);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_images_that_are_removed_from_the_html_are_removed_from_storage(): void
    {
        $this->administrator();
        $red = $this->png(0xFF0000);
        $blue = $this->png(0x0000FF);
        $this->post(route('confirmation-tables.store'), [
            'name' => 'Twee',
            'html' => '<img src="data:image/png;base64,'.base64_encode($red).'"><img src="data:image/png;base64,'.base64_encode($blue).'">',
        ]);
        $table = ConfirmationTable::query()->firstOrFail();

        $this->put(route('confirmation-tables.update', $table), ['name' => 'Twee', 'html' => $table->html.'<p>Tekst</p>'])->assertSessionDoesntHaveErrors();
        $this->assertCount(2, Storage::disk('local')->files("confirmation-tables/{$table->id}"));

        $remaining = '<img src="'.route('confirmation-tables.images.show', [$table, sha1($blue).'.png'], false).'">';
        $this->put(route('confirmation-tables.update', $table), ['name' => 'Twee', 'html' => $remaining]);

        $this->assertSame([sha1($blue).'.png'], array_map('basename', Storage::disk('local')->files("confirmation-tables/{$table->id}")));
    }

    public function test_deleting_a_table_unlinks_it_from_assays_and_removes_its_images(): void
    {
        $this->administrator();
        $this->post(route('confirmation-tables.store'), ['name' => 'Weg', 'html' => '<img src="data:image/png;base64,'.base64_encode($this->png(0x00FF00)).'">']);
        $table = ConfirmationTable::query()->firstOrFail();
        $assay = $this->assay();
        $assay->confirmationTables()->attach($table);

        $this->delete(route('confirmation-tables.destroy', $table))->assertRedirect(route('confirmation-tables.index'));

        $this->assertDatabaseMissing('confirmationtables', ['id' => $table->id]);
        $this->assertSame(0, $assay->confirmationTables()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_management_requires_the_media_permission_but_images_only_require_viewing_access(): void
    {
        $table = ConfirmationTable::factory()->create();
        $user = User::factory()->create(['enabled' => 1]);
        $this->actingAs($user);

        $this->get(route('confirmation-tables.index'))->assertForbidden();
        $this->post(route('confirmation-tables.store'), ['name' => 'Nieuw'])->assertForbidden();
        $this->delete(route('confirmation-tables.destroy', $table))->assertForbidden();
        $this->get(route('confirmation-tables.images.show', [$table, str_repeat('a', 40).'.png']))->assertForbidden();
        $this->assertDatabaseHas('confirmationtables', ['id' => $table->id]);
    }

    public function test_the_migration_links_assays_to_the_table_their_legacy_column_names(): void
    {
        $table = ConfirmationTable::factory()->create();
        $linked = $this->assay(['show_conf_table' => (string) $table->id]);
        $this->assay(['show_conf_table' => '999']);
        Schema::drop('assay_confirmation_table');

        (require database_path('migrations/2026_11_12_181312_create_assay_confirmation_table.php'))->up();

        $this->assertSame([$linked->id], DB::table('assay_confirmation_table')->pluck('assay_id')->all());
    }

    private function administrator(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::before(fn (): bool => true);
    }

    /** @param array<string, mixed> $attributes */
    private function assay(array $attributes = []): Assay
    {
        return Assay::create([
            'original_id' => 0, 'name' => 'Assay', 'type_base' => 1, 'media_id' => '[]', 'dillution' => 0, 'replicates' => 0,
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 0, 'script' => '', 'custom_fields' => '{}',
            'duration' => '1', 'start_from' => 'r', 'article_code' => '', 'active' => 1, 'confirmation' => 0, 'confirmation_script' => '[]', ...$attributes,
        ]);
    }

    private function png(int $color): string
    {
        $image = imagecreatetruecolor(1, 1);
        imagesetpixel($image, 0, 0, $color);
        ob_start();
        imagepng($image);

        return ob_get_clean();
    }
}
