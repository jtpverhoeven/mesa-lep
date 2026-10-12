<?php

namespace Tests\Feature;

use App\Actions\LabelEvents\SaveLabelEvent;
use App\Models\Assay;
use App\Models\AssayType;
use App\Models\Client;
use App\Models\LabelDesign;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\SampleField;
use App\Models\SampleProcedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabelEventAdministrationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('legacyConditions')]
    public function test_saving_rules_preserves_legacy_condition_shapes(int $group, array $condition): void
    {
        $printer = Printer::factory()->create();
        $labelDesign = LabelDesign::factory()->create();

        $event = (new SaveLabelEvent)->handle(new LabelEvent, [
            'id' => 999999,
            'event' => 'L',
            'event_group' => (string) $group,
            'event_data' => [...$condition, 'unused' => 'discard'],
            'print_label' => (string) $labelDesign->id,
            'print_to' => (string) $printer->id,
            'copies' => '3',
        ]);

        $this->assertDatabaseHas('labelevents', [
            'id' => $event->id, 'event' => 'L', 'event_group' => $group,
            'event_data' => json_encode($condition, JSON_FORCE_OBJECT),
            'print_label' => $labelDesign->id, 'print_to' => $printer->id, 'copies' => 3,
        ]);
        $this->assertSame($condition, json_decode($event->fresh()->event_data, true));
        $this->assertSame($printer->id, $event->printer->id);
        $this->assertSame($labelDesign->id, $event->labelDesign->id);
        $this->assertNotSame(999999, $event->id);
    }

    public function test_switching_a_rule_to_unconditional_clears_old_logic_without_creating_a_new_rule(): void
    {
        $event = LabelEvent::factory()->conditional(['type' => 'sClient', 'inspect_value' => '12', 'negative' => '1'])->create();

        (new SaveLabelEvent)->handle($event, [
            'event' => 'P', 'event_group' => 3, 'print_label' => $event->print_label,
            'print_to' => $event->print_to, 'copies' => 2, 'event_data' => ['type' => 'sClient'],
        ]);

        $this->assertDatabaseHas('labelevents', ['id' => $event->id, 'event' => 'P', 'event_group' => 3, 'event_data' => '', 'copies' => 2]);
        $this->assertDatabaseCount('labelevents', 1);
    }

    public function test_authorized_users_can_create_and_update_rules_without_trusting_a_posted_id(): void
    {
        $existing = LabelEvent::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->post(route('label-events.store'), [
            'id' => $existing->id, 'event' => 'L', 'event_group' => '1',
            'event_data' => '{"type":"mType","inspect_value":"R","negative":"1"}',
            'print_label' => $existing->print_label, 'print_to' => $existing->print_to, 'copies' => '4',
        ])->assertRedirect(route('label-events.index', ['group' => 1]))->assertSessionHasNoErrors();

        $created = LabelEvent::query()->whereKeyNot($existing->id)->firstOrFail();
        $this->assertDatabaseHas('labelevents', [
            'id' => $created->id, 'event' => 'L', 'copies' => 4,
            'event_data' => '{"type":"mType","inspect_value":"R","negative":"1"}',
        ]);

        $this->put(route('label-events.update', $created), [
            'id' => $existing->id, 'event' => 'P', 'event_group' => '3', 'event_data' => ['type' => 'mType'],
            'print_label' => $existing->print_label, 'print_to' => $existing->print_to, 'copies' => '2',
        ])->assertRedirect(route('label-events.edit', $created))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labelevents', ['id' => $created->id, 'event' => 'P', 'event_group' => 3, 'event_data' => '', 'copies' => 2]);
        $this->assertSame('P', $existing->fresh()->event);
        $this->assertDatabaseCount('labelevents', 2);
    }

    public function test_deleting_a_rule_preserves_other_rules_designs_and_printers(): void
    {
        $event = LabelEvent::factory()->create(['event_group' => 2]);
        $other = LabelEvent::factory()->create();
        $printer = $event->printer;
        $design = $event->labelDesign;
        $this->actingAs($this->userWithPermissions());

        $this->delete(route('label-events.destroy', $event))
            ->assertRedirect(route('label-events.index', ['group' => 2]));

        $this->assertModelMissing($event);
        $this->assertModelExists($other);
        $this->assertModelExists($printer);
        $this->assertModelExists($design);
    }

    #[DataProvider('invalidRuleData')]
    public function test_invalid_rules_are_rejected_without_changing_existing_data(string $method, array $overrides, array $errors): void
    {
        $event = LabelEvent::factory()->create()->refresh();
        $original = $event->getAttributes();
        $this->actingAs($this->userWithPermissions());
        $payload = [
            'event' => 'L', 'event_group' => 1, 'print_label' => $event->print_label,
            'print_to' => $event->print_to, 'copies' => 1,
            'event_data' => ['type' => 'mType', 'inspect_value' => 'S', 'negative' => '0'],
            ...$overrides,
        ];
        $url = $method === 'POST' ? route('label-events.store') : route('label-events.update', $event);

        $this->call($method, $url, $payload)->assertSessionHasErrors($errors);

        $this->assertDatabaseCount('labelevents', 1);
        $this->assertSame($original, $event->fresh()->getAttributes());
    }

    #[DataProvider('ruleEndpoints')]
    public function test_guests_cannot_access_rule_endpoints(string $method, string $path): void
    {
        $event = LabelEvent::factory()->create();

        $this->call($method, '/admin/label-events'.str_replace('__ID__', (string) $event->id, $path))
            ->assertRedirect(route('login'));

        $this->assertModelExists($event);
    }

    #[DataProvider('ruleEndpoints')]
    public function test_users_without_print_settings_permission_cannot_read_or_modify_rules(string $method, string $path): void
    {
        $event = LabelEvent::factory()->create()->refresh();
        $original = $event->getAttributes();
        $this->actingAs($this->userWithPermissions(['administration.access', 'labels.design', 'settings.advanced']));

        $this->call($method, '/admin/label-events'.str_replace('__ID__', (string) $event->id, $path))
            ->assertForbidden();

        $this->assertDatabaseCount('labelevents', 1);
        $this->assertSame($original, $event->fresh()->getAttributes());
    }

    #[DataProvider('unknownRuleEndpoints')]
    public function test_missing_rules_return_404_without_writing(string $method, string $path): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->call($method, '/admin/label-events'.$path)->assertNotFound();

        $this->assertDatabaseCount('labelevents', 0);
    }

    #[DataProvider('acceptedConditionTypes')]
    public function test_each_supported_condition_can_be_created_through_request_validation(string $type, bool $emptyFieldValue): void
    {
        $printer = Printer::factory()->create();
        $design = LabelDesign::factory()->create();
        $condition = ['type' => $type];
        $group = in_array($type, ['assayIs', 'assayTypeIs'], true) ? 2 : 1;

        if ($type === 'sField') {
            SampleField::create(['name' => 'matrix', 'alias' => 'Matrix', 'type' => 'text', 'std_value' => '', 'position' => 1]);
            $condition['inspect'] = 'matrix';
            $value = $emptyFieldValue ? '' : 'Water';
        } elseif ($type === 'sClient') {
            $value = (string) Client::create(['name' => 'Waterbedrijf', 'active' => 1])->id;
        } elseif ($type === 'mMethod') {
            $value = (string) SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0])->id;
        } elseif ($type === 'assayTypeIs') {
            $value = (string) AssayType::create(['name' => 'Kiemgetal', 'description' => '', 'added_by' => 1, 'added_date' => '0'])->id;
        } elseif ($type === 'assayIs') {
            $assay = Assay::create([
                'original_id' => 0, 'name' => 'Plate count', 'type_base' => 0, 'media_id' => '{}',
                'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
                'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
                'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '',
            ]);
            $second = $assay->replicate();
            $second->save();
            $value = $assay->id.','.$second->id;
        } else {
            $value = 'R';
        }

        $condition['inspect_value'] = $value;
        $condition['negative'] = '1';
        $this->actingAs($this->userWithPermissions());

        $this->post(route('label-events.store'), [
            'event' => 'L', 'event_group' => $group, 'event_data' => $condition,
            'print_label' => $design->id, 'print_to' => $printer->id, 'copies' => 1,
        ])->assertRedirect(route('label-events.index', ['group' => $group]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labelevents', ['event_group' => $group, 'event_data' => json_encode($condition, JSON_FORCE_OBJECT)]);
    }

    public static function acceptedConditionTypes(): array
    {
        return [
            'sample field' => ['sField', false], 'empty sample field' => ['sField', true],
            'client' => ['sClient', false], 'method' => ['mMethod', false], 'sample type' => ['mType', false],
            'assay type' => ['assayTypeIs', false], 'multiple assays' => ['assayIs', false],
        ];
    }

    public function test_rules_are_listed_with_their_designs_printers_and_all_trigger_groups(): void
    {
        $printer = Printer::factory()->create(['name' => 'Balieprinter']);
        $design = LabelDesign::factory()->create(['name' => 'Monsteretiket']);
        LabelEvent::factory()->for($printer, 'printer')->for($design, 'labelDesign')->count(3)
            ->sequence(['event_group' => 1], ['event_group' => 2], ['event_group' => 3])->create();
        $this->actingAs($this->userWithPermissions());

        $this->get(route('label-events.index'))
            ->assertViewHas('labelEvents', fn ($events): bool => $events->count() === 3 && $events->first()->printer->name === 'Balieprinter')
            ->assertSee('Monsterregistratie')->assertSee('Analyses toevoegen')->assertSee('THT-goedkeuring')
            ->assertSee('Monsteretiket')->assertSee('Balieprinter');
    }

    public function test_creation_form_can_open_for_tht_without_printers_or_designs(): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->get(route('label-events.create', ['group' => 3]))
            ->assertViewHas('labelEvent', fn (LabelEvent $event): bool => $event->event_group === 3 && $event->event === 'P')
            ->assertViewHas('options', fn (array $options): bool => $options['printers'] === [] && $options['labelDesigns'] === []);
    }

    public function test_editing_legacy_rules_safely_restores_conditions_and_escapes_user_supplied_names(): void
    {
        $value = '</script><script>alert("condition")</script>';
        $printer = Printer::factory()->create(['name' => '<script>alert("printer")</script>']);
        $design = LabelDesign::factory()->create(['name' => '<script>alert("label")</script>']);
        $event = LabelEvent::factory()->for($printer, 'printer')->for($design, 'labelDesign')
            ->conditional(['type' => 'sField', 'inspect' => 'matrix', 'inspect_value' => $value, 'negative' => '1'])->create();
        $this->actingAs($this->userWithPermissions());

        $this->get(route('label-events.edit', $event))
            ->assertViewHas('labelEvent', fn (LabelEvent $rule): bool => $rule->event_data === $event->event_data)
            ->assertDontSee($value, false)->assertDontSee($design->name, false)->assertDontSee($printer->name, false);
        $this->get(route('label-events.index'))
            ->assertDontSee($value, false)->assertDontSee($design->name, false)->assertDontSee($printer->name, false);
    }

    public function test_failed_validation_preserves_the_rule_draft_and_returns_the_condition_error(): void
    {
        $event = LabelEvent::factory()->create();
        $payload = [
            'event' => 'L', 'event_group' => '1', 'print_label' => (string) $event->print_label,
            'print_to' => (string) $event->print_to, 'copies' => '2',
            'event_data' => ['type' => 'assayIs', 'inspect_value' => '12', 'negative' => '0'],
        ];
        $this->actingAs($this->userWithPermissions());

        $this->from(route('label-events.create'))->post(route('label-events.store'), $payload)
            ->assertRedirect(route('label-events.create'))
            ->assertSessionHasErrors(['event_data.type' => 'Kies een voorwaarde die bij dit printmoment hoort.'])
            ->assertSessionHasInput('event_data', $payload['event_data'])->assertSessionHasInput('copies', '2');
    }

    public function test_forms_render_validation_errors_and_restore_the_previous_condition(): void
    {
        $event = LabelEvent::factory()->create();
        $message = 'Kies een voorwaarde die bij dit printmoment hoort.';
        $this->actingAs($this->userWithPermissions());

        $this->withSession([
            'errors' => ['default' => ['format' => ':message', 'messages' => ['event_data.type' => [$message]]]],
            '_old_input' => [
                'event' => 'L', 'event_group' => '1', 'print_label' => $event->print_label,
                'print_to' => $event->print_to, 'copies' => '2',
                'event_data' => ['type' => 'assayIs', 'inspect_value' => '12', 'negative' => '0'],
            ],
        ])->get(route('label-events.create'))
            ->assertSee('assayIs')->assertSee('Kies een voorwaarde die bij dit printmoment hoort.');
    }

    public function test_rule_navigation_is_hidden_without_print_settings_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['administration.access']));

        $this->get(route('beheer.dashboard'))->assertDontSee(route('label-events.index'), false);
    }

    public static function ruleEndpoints(): array
    {
        return [
            'index' => ['GET', ''], 'create' => ['GET', '/create'], 'store' => ['POST', ''],
            'edit' => ['GET', '/__ID__/edit'], 'update' => ['PUT', '/__ID__'], 'delete' => ['DELETE', '/__ID__'],
        ];
    }

    public static function unknownRuleEndpoints(): array
    {
        return [
            'edit' => ['GET', '/999999/edit'], 'update' => ['PUT', '/999999'], 'delete' => ['DELETE', '/999999'],
        ];
    }

    public static function invalidRuleData(): array
    {
        $condition = ['type' => 'mType', 'inspect_value' => 'S', 'negative' => '0'];
        $cases = [
            'required fields' => [['event' => null, 'event_group' => null, 'print_label' => null, 'print_to' => null, 'copies' => null], ['event', 'event_group', 'print_label', 'print_to', 'copies']],
            'unknown event' => [['event' => 'X'], ['event']],
            'unknown group' => [['event_group' => 4], ['event_group', 'event_data.type']],
            'conditional THT' => [['event_group' => 3], ['event', 'event_data.type']],
            'missing printer' => [['print_to' => 999999], ['print_to']],
            'missing design' => [['print_label' => 999999], ['print_label']],
            'non-integer printer' => [['print_to' => 'Zebra'], ['print_to']],
            'zero copies' => [['copies' => 0], ['copies']],
            'negative copies' => [['copies' => -1], ['copies']],
            'fractional copies' => [['copies' => 1.5], ['copies']],
            'excessive copies' => [['copies' => 10000], ['copies']],
            'missing condition' => [['event_data' => null], ['event_data']],
            'broken JSON' => [['event_data' => '{bad'], ['event_data']],
            'unknown condition' => [['event_data' => [...$condition, 'type' => 'unknown']], ['event_data.type']],
            'condition from other group' => [['event_group' => 2], ['event_data.type']],
            'extra condition keys' => [['event_data' => [...$condition, 'extra' => 'bad']], ['event_data']],
            'unknown sample type' => [['event_data' => [...$condition, 'inspect_value' => 'X']], ['event_data.inspect_value']],
            'invalid negation' => [['event_data' => [...$condition, 'negative' => 'yes']], ['event_data.negative']],
            'missing negation' => [['event_data' => ['type' => 'mType', 'inspect_value' => 'S']], ['event_data.negative']],
            'missing sample field' => [['event_data' => ['type' => 'sField', 'inspect' => 'missing', 'inspect_value' => 'water', 'negative' => '0']], ['event_data.inspect']],
            'missing client' => [['event_data' => ['type' => 'sClient', 'inspect_value' => '999999', 'negative' => '0']], ['event_data.inspect_value']],
            'missing procedure' => [['event_data' => ['type' => 'mMethod', 'inspect_value' => '999999', 'negative' => '0']], ['event_data.inspect_value']],
            'missing assay type' => [['event_group' => 2, 'event_data' => ['type' => 'assayTypeIs', 'inspect_value' => '999999', 'negative' => '0']], ['event_data.inspect_value']],
            'malformed assay list' => [['event_group' => 2, 'event_data' => ['type' => 'assayIs', 'inspect_value' => '1,broken', 'negative' => '0']], ['event_data.inspect_value']],
            'missing assay' => [['event_group' => 2, 'event_data' => ['type' => 'assayIs', 'inspect_value' => '999999', 'negative' => '0']], ['event_data.inspect_value']],
        ];
        $data = [];

        foreach (['POST', 'PUT'] as $method) {
            foreach ($cases as $name => [$payload, $errors]) {
                $data[$method.' '.$name] = [$method, $payload, $errors];
            }
        }

        return $data;
    }

    public static function legacyConditions(): array
    {
        return [
            'sample field' => [1, ['type' => 'sField', 'inspect' => 'matrix', 'inspect_value' => 'Water', 'negative' => '0']],
            'client' => [1, ['type' => 'sClient', 'inspect_value' => '12', 'negative' => '1']],
            'sampling method' => [1, ['type' => 'mMethod', 'inspect_value' => '7', 'negative' => '0']],
            'sample type' => [1, ['type' => 'mType', 'inspect_value' => 'R', 'negative' => '1']],
            'assays' => [2, ['type' => 'assayIs', 'inspect_value' => '12,34', 'negative' => '0']],
            'assay type' => [2, ['type' => 'assayTypeIs', 'inspect_value' => '3', 'negative' => '1']],
        ];
    }

    private function userWithPermissions(array $permissions = ['administration.access', 'labels.print-settings']): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('label-event-test-role', 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole($role);

        return $user;
    }
}
