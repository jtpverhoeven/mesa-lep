<?php

namespace Tests\Feature;

use App\Actions\Assays\UpdateAssay;
use App\Models\Assay;
use App\Models\DocgenAssociation;
use App\Models\DocgenTemplate;
use App\Models\Footer;
use App\Models\User;
use Database\Seeders\ReportConfigurationSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportAdministrationTest extends TestCase
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

    public function test_footer_administration_preserves_the_approved_text_and_legacy_columns(): void
    {
        $this->administrator();
        $text = "  <p>Q = door de RvA geaccrediteerde verrichting.</p>\n<p>Deze tekst blijft ongewijzigd.</p>\n";

        $this->post(route('footers.store'), ['name' => 'maz2020', 'lang' => 'nl', 'q' => '1', 'data' => $text])->assertSessionHasNoErrors();
        $footer = Footer::query()->firstOrFail();

        $this->assertDatabaseHas('footers', ['id' => $footer->id, 'name' => 'maz2020', 'lang' => 'nl', 'q' => 1, 'data' => $text]);
        $this->get(route('footers.index'))->assertSee('maz2020')->assertSee('Ja');
        $this->get(route('footers.edit', $footer))->assertSee($text);

        $this->put(route('footers.update', $footer), ['name' => 'maz2020', 'lang' => 'en', 'q' => 0, 'data' => 'Replacement text.'])
            ->assertRedirect(route('footers.edit', $footer));
        $this->assertDatabaseHas('footers', ['id' => $footer->id, 'lang' => 'en', 'q' => 0, 'data' => 'Replacement text.']);

        $this->delete(route('footers.destroy', $footer))->assertRedirect(route('footers.index'));
        $this->assertModelMissing($footer);
    }

    public function test_template_administration_preserves_folders_order_and_active_state(): void
    {
        $this->administrator();

        $this->post(route('docgen-templates.store'), ['name' => 'Certificaat', 'folder' => 'maz2020', 'active' => 1, 'view_order' => 3])
            ->assertSessionHasNoErrors();
        $template = DocgenTemplate::query()->firstOrFail();
        $this->assertDatabaseHas('docgentemplates', ['id' => $template->id, 'folder' => 'maz2020', 'active' => 1, 'view_order' => 3]);
        $this->get(route('docgen-templates.index'))->assertSee('Certificaat')->assertSee('Beschikbaar');

        $this->put(route('docgen-templates.update', $template), ['name' => 'Inactief', 'folder' => 'legacyTemplate', 'active' => 0, 'view_order' => null])
            ->assertRedirect(route('docgen-templates.edit', $template));
        $this->get(route('docgen-templates.index'))->assertSee('Nog niet geport');
        $this->assertDatabaseHas('docgentemplates', ['id' => $template->id, 'active' => 0, 'folder' => 'legacyTemplate', 'view_order' => null]);
    }

    public function test_hidden_associations_are_preserved_until_the_inactive_assays_are_explicitly_shown(): void
    {
        $this->administrator();
        $template = DocgenTemplate::factory()->create();
        $active = $this->assay();
        $inactive = $this->assay(['active' => 0, 'name' => 'Historische analyse']);
        $template->associations()->create(['assay_id' => $inactive->id, 'original_assay_id' => $inactive->original_id]);

        $this->put(route('docgen-associations.update', $template), ['show_hidden' => 0, 'assay_ids' => [$active->id]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('docgenassociations', ['occurs_in' => $template->id, 'assay_id' => $active->id, 'original_assay_id' => $active->original_id]);
        $this->assertDatabaseHas('docgenassociations', ['occurs_in' => $template->id, 'assay_id' => $inactive->id]);
        $this->get(route('docgen-associations.edit', $template))->assertDontSee('Historische analyse');
        $this->get(route('docgen-associations.edit', [$template, 'show_hidden' => 1]))->assertSee('Historische analyse');

        $this->put(route('docgen-associations.update', $template), ['show_hidden' => 1, 'assay_ids' => [$active->id]])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('docgenassociations', ['occurs_in' => $template->id, 'assay_id' => $inactive->id]);
    }

    public function test_copying_associations_replaces_the_target_and_preserves_imported_original_ids(): void
    {
        $this->administrator();
        $source = DocgenTemplate::factory()->create();
        $target = DocgenTemplate::factory()->create(['folder' => 'other']);
        $sourceAssay = $this->assay(['active' => 0]);
        $otherAssay = $this->assay();
        $source->associations()->create(['assay_id' => $sourceAssay->id, 'original_assay_id' => 9876]);
        $target->associations()->create(['assay_id' => $otherAssay->id, 'original_assay_id' => $otherAssay->original_id]);

        $this->post(route('docgen-associations.copy', $target), ['source_template' => $source->id])
            ->assertRedirect(route('docgen-associations.edit', $target));

        $this->assertDatabaseHas('docgenassociations', ['occurs_in' => $target->id, 'assay_id' => $sourceAssay->id, 'original_assay_id' => 9876]);
        $this->assertDatabaseMissing('docgenassociations', ['occurs_in' => $target->id, 'assay_id' => $otherAssay->id]);
        $this->assertSame(1, $source->associations()->count());
    }

    #[DataProvider('legacyOriginalIds')]
    public function test_assay_revisions_keep_historical_associations_and_use_legacy_root_ids(int $originalId, int $expected): void
    {
        $template = DocgenTemplate::factory()->create();
        $assay = $this->assay(['original_id' => $originalId]);
        $template->associations()->create(['assay_id' => $assay->id, 'original_assay_id' => 1234]);

        $revision = app(UpdateAssay::class)->handle($assay->fresh(), ['name' => 'Nieuwe revisie', 'type_base' => 1]);

        $this->assertDatabaseHas('docgenassociations', ['occurs_in' => $template->id, 'assay_id' => $revision->id, 'original_assay_id' => $expected]);
        $this->assertDatabaseHas('docgenassociations', ['occurs_in' => $template->id, 'assay_id' => $assay->id, 'original_assay_id' => 1234]);
        $this->assertSame(0, $assay->fresh()->active);
    }

    public static function legacyOriginalIds(): array
    {
        return ['explicit original' => [17, 17], 'zero original falls back to previous ID' => [0, 1]];
    }

    public function test_deleting_a_template_removes_only_its_associations_and_retains_certificate_text(): void
    {
        $this->administrator();
        $template = DocgenTemplate::factory()->create();
        $other = DocgenTemplate::factory()->create(['folder' => 'other']);
        DocgenAssociation::factory()->create(['occurs_in' => $template->id]);
        $retained = DocgenAssociation::factory()->create(['occurs_in' => $other->id]);
        $footer = Footer::factory()->create();

        $this->delete(route('docgen-templates.destroy', $template))->assertRedirect(route('docgen-templates.index'));

        $this->assertModelMissing($template);
        $this->assertDatabaseMissing('docgenassociations', ['occurs_in' => $template->id]);
        $this->assertModelExists($retained);
        $this->assertModelExists($footer);
    }

    public function test_report_configuration_seeding_is_idempotent_and_does_not_invent_certificate_text(): void
    {
        $this->seed(ReportConfigurationSeeder::class);
        $this->seed(ReportConfigurationSeeder::class);

        $this->assertDatabaseCount('docgentemplates', 1);
        $this->assertDatabaseCount('footers', 0);
        $this->assertDatabaseHas('permissions', ['name' => 'reports.generate', 'guard_name' => 'web']);
    }

    public function test_administration_rejects_invalid_configuration_and_foreign_associations(): void
    {
        $this->administrator();
        $template = DocgenTemplate::factory()->create();

        $this->postJson(route('docgen-templates.store'), ['name' => 'Invalid', 'folder' => '../maz2020', 'active' => 1])->assertUnprocessable()->assertJsonValidationErrors('folder');
        $this->postJson(route('footers.store'), ['name' => 'maz2020', 'lang' => 'xx', 'q' => 7, 'data' => 'text'])->assertUnprocessable()->assertJsonValidationErrors(['lang', 'q']);
        $this->putJson(route('docgen-associations.update', $template), ['show_hidden' => 1, 'assay_ids' => [99999]])->assertUnprocessable()->assertJsonValidationErrors('assay_ids.0');
        $this->postJson(route('docgen-associations.copy', $template), ['source_template' => $template->id])->assertUnprocessable()->assertJsonValidationErrors('source_template');
        $this->assertDatabaseCount('docgentemplates', 1);
        $this->assertDatabaseCount('footers', 0);
        $this->assertDatabaseCount('docgenassociations', 0);
    }

    #[DataProvider('administrationPages')]
    public function test_administration_requires_authentication_and_permission(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        $this->get(route($route))->assertForbidden();
    }

    public static function administrationPages(): array
    {
        return [
            'footers' => ['footers.index'],
            'create footer' => ['footers.create'],
            'templates' => ['docgen-templates.index'],
            'create template' => ['docgen-templates.create'],
        ];
    }

    private function administrator(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::before(fn (): bool => true);
    }

    private function assay(array $overrides = []): Assay
    {
        return Assay::query()->create([
            'original_id' => 1, 'name' => 'Testanalyse', 'type_base' => 1, 'media_id' => '[]',
            'dillution' => 0, 'replicates' => 0, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
            'custom_fields' => '{}', 'duration' => '48', 'start_from' => '', 'article_code' => '',
            ...$overrides,
        ]);
    }
}
