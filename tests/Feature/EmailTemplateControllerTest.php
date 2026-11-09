<?php

namespace Tests\Feature;

use App\Actions\EmailTemplates\BuildEmailTemplateSelector;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmailTemplateControllerTest extends TestCase
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

    public function test_index_passes_templates_in_id_order_with_default_flags_and_type_headers(): void
    {
        $this->administrator();
        EmailTemplate::factory()->create(['id' => 20, 'name' => 'Later template', 'defaultmail' => 1, 'template' => 'Tekst', 'template_en' => '']);
        EmailTemplate::factory()->create(['id' => 10, 'name' => 'Earlier template', 'defaultmail_for_files' => 1, 'template' => '', 'template_en' => 'Text']);

        $response = $this->get(route('email-templates.index'))
            ->assertOk()
            ->assertViewIs('email-templates.index')
            ->assertViewHas('emailTemplates', fn (Collection $templates): bool => $templates->modelKeys() === [10, 20])
            ->assertSee('<email-template-directory', false);

        $props = $this->componentProps($response->getContent(), 'email-template-directory');

        $this->assertSame([10, 20], array_column($props['templates'], 'id'));
        $this->assertSame([false, true], array_column($props['templates'], 'defaultmail'));
        $this->assertSame([true, false], array_column($props['templates'], 'defaultmail_for_files'));
        $this->assertSame([false, false], array_column($props['templates'], 'defaultmail_with_files'));
        $this->assertSame([false, true], array_column($props['templates'], 'hasTemplate'));
        $this->assertSame([true, false], array_column($props['templates'], 'hasTemplateEn'));
        $this->assertSame(
            ['Standaard rapport email', 'Standaard rapport email (met bestanden)', 'Standaard email voor bestanden'],
            array_column($props['types'], 'header'),
        );
        $this->assertSame(
            ['reports', 'report_with_files', 'for_files'],
            array_column($props['types'], 'value'),
        );
    }

    public function test_edit_form_lists_current_defaults_and_keeps_old_input_after_a_validation_error(): void
    {
        $this->administrator();
        $template = EmailTemplate::factory()->create([
            'name' => 'Rapport',
            'defaultmail' => 1,
            'defaultmail_for_files' => 1,
        ]);

        $props = $this->componentProps(
            $this->get(route('email-templates.edit', $template))->assertOk()->getContent(),
            'email-template-form',
        );

        $this->assertSame(['Rapportages', 'Bestand emails'], $props['defaults']);
        $this->assertTrue($props['updating']);
        $this->assertSame(route('email-templates.update', $template), $props['actionUrl']);

        $response = $this->followingRedirects()
            ->from(route('email-templates.edit', $template))
            ->put(route('email-templates.update', $template), ['name' => '', 'template' => 'Bewaard', 'bcc' => 'bcc@example.test']);
        $props = $this->componentProps($response->getContent(), 'email-template-form');

        $this->assertSame('Bewaard', $props['initial']['template']);
        $this->assertSame('bcc@example.test', $props['initial']['bcc']);
        $this->assertSame(['name'], array_keys($props['errors']));
    }

    public function test_create_form_starts_empty(): void
    {
        $this->administrator();

        $props = $this->componentProps(
            $this->get(route('email-templates.edit'))->assertOk()->getContent(),
            'email-template-form',
        );

        $this->assertFalse($props['updating']);
        $this->assertSame(route('email-templates.store'), $props['actionUrl']);
        $this->assertSame([], $props['defaults']);
    }

    public function test_templates_can_be_created_with_empty_bodies_and_updated(): void
    {
        $this->administrator();

        $this->post(route('email-templates.store'), [
            'name' => 'Rapport',
            'template' => '',
            'template_en' => '',
            'bcc' => '',
        ])->assertRedirect(route('email-templates.index'))->assertSessionHasNoErrors();

        $template = EmailTemplate::query()->sole();
        $this->assertSame('Rapport', $template->name);
        $this->assertSame('', $template->template ?? '');
        $this->assertSame('', $template->template_en ?? '');
        $this->assertSame('', $template->bcc ?? '');

        $this->put(route('email-templates.update', $template), [
            'name' => 'Rapport aangepast',
            'template' => 'Nederlandse tekst',
            'template_en' => 'English text',
            'bcc' => 'bcc@example.test',
        ])->assertRedirect(route('email-templates.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('emailtemplates', [
            'id' => $template->id,
            'name' => 'Rapport aangepast',
            'template' => 'Nederlandse tekst',
            'template_en' => 'English text',
            'bcc' => 'bcc@example.test',
        ]);
    }

    public function test_name_is_required_and_limited_to_128_characters(): void
    {
        $this->administrator();

        $this->from(route('email-templates.edit'))
            ->post(route('email-templates.store'), ['name' => str_repeat('x', 129)])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('emailtemplates', 0);
    }

    #[DataProvider('defaultTemplateTypes')]
    public function test_setting_a_default_clears_all_other_defaults_for_that_type(string $type, string $column): void
    {
        $this->administrator();
        $previousDefault = EmailTemplate::factory()->create([$column => 1]);
        $duplicateDefault = EmailTemplate::factory()->create([$column => 1]);
        $selectedTemplate = EmailTemplate::factory()->create();

        $this->post(route('email-templates.default', $selectedTemplate), ['defaultFor' => $type])
            ->assertRedirect(route('email-templates.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('emailtemplates', ['id' => $previousDefault->id, $column => 0]);
        $this->assertDatabaseHas('emailtemplates', ['id' => $duplicateDefault->id, $column => 0]);
        $this->assertDatabaseHas('emailtemplates', ['id' => $selectedTemplate->id, $column => 1]);
    }

    public static function defaultTemplateTypes(): array
    {
        return [
            'reports' => ['reports', 'defaultmail'],
            'reports with files' => ['reports_with_files', 'defaultmail_with_files'],
            'files' => ['for_files', 'defaultmail_for_files'],
        ];
    }

    public function test_a_default_template_can_be_deleted(): void
    {
        $this->administrator();
        $defaultTemplate = EmailTemplate::factory()->defaultForReports()->create();
        EmailTemplate::factory()->create(['name' => 'Fallback']);

        $this->delete(route('email-templates.destroy', $defaultTemplate))
            ->assertRedirect(route('email-templates.index'));

        $this->assertModelMissing($defaultTemplate);
        $this->assertDatabaseCount('emailtemplates', 1);
        $this->assertSame(
            'Fallback',
            app(BuildEmailTemplateSelector::class)->handle('reports')['options'][0]['name'],
        );
    }

    public function test_load_template_returns_the_requested_language_and_bcc(): void
    {
        $this->administrator();
        $template = EmailTemplate::factory()->create([
            'template' => 'Nederlandse mail',
            'template_en' => 'English email',
            'bcc' => 'archive@example.test',
        ]);

        $this->get(route('email-templates.load-template', [$template, 'nl']))
            ->assertOk()
            ->assertExactJson(['template' => 'Nederlandse mail', 'bcc' => 'archive@example.test']);

        $this->get(route('email-templates.load-template', [$template, 'en']))
            ->assertOk()
            ->assertExactJson(['template' => 'English email', 'bcc' => 'archive@example.test']);
    }

    public function test_an_unknown_template_edit_redirects_to_the_index(): void
    {
        $this->administrator();

        $this->get(route('email-templates.edit', ['emailTemplate' => 999]))
            ->assertRedirect(route('email-templates.index'));
    }

    public function test_users_without_advanced_settings_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));

        $this->get(route('email-templates.index'))->assertForbidden();
        $this->post(route('email-templates.store'), ['name' => 'Denied'])->assertForbidden();
        $this->assertDatabaseCount('emailtemplates', 0);
    }

    public function test_selector_uses_the_first_default_and_the_forced_template_wins(): void
    {
        $firstDefault = EmailTemplate::factory()->defaultWithFiles()->create([
            'template_en' => 'First default',
            'bcc' => 'first@example.test',
        ]);
        EmailTemplate::factory()->defaultWithFiles()->create(['template_en' => 'Duplicate default']);
        $forced = EmailTemplate::factory()->create(['template_en' => 'Forced template']);
        $selector = app(BuildEmailTemplateSelector::class);

        $defaultSelection = $selector->handle('reports_with_files', language: 'en');
        $this->assertSame($firstDefault->id, $defaultSelection['options'][0]['id']);
        $this->assertSame('First default', $defaultSelection['template']);
        $this->assertSame('first@example.test', $defaultSelection['bcc']);
        $this->assertTrue($defaultSelection['options'][0]['selected']);
        $this->assertFalse($defaultSelection['options'][2]['selected']);

        $forcedSelection = $selector->handle('report_with_files', $forced->id, 'en');
        $this->assertSame('Forced template', $forcedSelection['template']);
        $this->assertTrue($forcedSelection['options'][2]['selected']);
    }

    public function test_selector_falls_back_to_first_template_or_empty_values(): void
    {
        $first = EmailTemplate::factory()->create([
            'name' => 'First',
            'template' => 'First body',
            'bcc' => 'first@example.test',
        ]);
        EmailTemplate::factory()->create(['name' => 'Second']);
        $selector = app(BuildEmailTemplateSelector::class);

        $selection = $selector->handle('reports');
        $this->assertSame($first->id, $selection['options'][0]['id']);
        $this->assertTrue($selection['options'][0]['selected']);
        $this->assertSame('First body', $selection['template']);

        EmailTemplate::query()->delete();
        $emptySelection = $selector->handle('reports');
        $this->assertSame([], $emptySelection['options']);
        $this->assertSame('', $emptySelection['template']);
        $this->assertSame('', $emptySelection['bcc']);
    }

    /** @return array<string, mixed> */
    private function componentProps(string $html, string $component): array
    {
        $this->assertSame(1, preg_match('/<'.$component.' v-bind="(.*?)"><\/'.$component.'>/s', $html, $match));
        $this->assertSame(1, preg_match("/^JSON\.parse\('(.*)'\)$/s", html_entity_decode($match[1]), $json));

        $literal = preg_replace_callback(
            '/\\\\(u[0-9a-fA-F]{4}|.)/s',
            fn (array $escape): string => strlen($escape[1]) === 5 ? mb_chr(hexdec(substr($escape[1], 1))) : $escape[1],
            $json[1],
        );

        return json_decode($literal, true, flags: JSON_THROW_ON_ERROR);
    }

    private function administrator(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::before(fn (): bool => true);
    }
}
