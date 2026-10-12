<?php

namespace Tests\Feature;

use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabelDesignAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_designs_preserve_pasted_zpl_and_independent_default_flags(): void
    {
        $zpl = "\n^XA\r\n^FO40,40^FD{sample_name}^FS\r\n^XZ\n ";
        $this->actingAs($this->userWithPermissions());

        $this->post('/admin/label-designs', [
            'name' => 'Monsteretiket',
            'zpl' => $zpl,
            'default_sample' => '1',
            'default_analysis' => '1',
            'default_printer' => '',
        ])->assertRedirect('/admin/label-designs')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labeldesigns', [
            'name' => 'Monsteretiket',
            'zpl' => $zpl,
            'default_sample' => 1,
            'default_analysis' => 1,
            'default_printer' => null,
        ]);
    }

    public function test_label_designs_can_select_an_existing_printer_without_accepting_a_posted_id(): void
    {
        $printer = Printer::factory()->create();
        $existing = LabelDesign::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->post(route('label-designs.store'), [
            'id' => $existing->id,
            'name' => str_repeat('L', 128),
            'zpl' => '^XA^FD{sample_name}^FS^XZ',
            'default_sample' => '0',
            'default_analysis' => '0',
            'default_printer' => (string) $printer->id,
        ])->assertRedirect(route('label-designs.index'))->assertSessionHasNoErrors();

        $labelDesign = LabelDesign::query()->where('name', str_repeat('L', 128))->firstOrFail();
        $this->assertSame($printer->id, $labelDesign->default_printer);
        $this->assertSame($printer->id, $labelDesign->printer->id);
        $this->assertFalse($labelDesign->default_sample);
        $this->assertFalse($labelDesign->default_analysis);
        $this->assertDatabaseCount('labeldesigns', 2);
        $this->assertSame($existing->name, $existing->fresh()->name);
    }

    #[DataProvider('defaultSelections')]
    public function test_selecting_defaults_clears_only_the_matching_defaults_on_other_designs(string $method, int $sample, int $analysis): void
    {
        $existing = LabelDesign::factory()->create([
            'name' => 'Monsteretiket', 'default_sample' => true, 'default_analysis' => true,
        ]);
        $labelDesign = $method === 'PUT' ? LabelDesign::factory()->create() : null;
        $this->actingAs($this->userWithPermissions());
        $url = $method === 'POST' ? route('label-designs.store') : route('label-designs.update', $labelDesign);
        $redirect = $method === 'POST' ? route('label-designs.index') : route('label-designs.edit', $labelDesign);

        $this->call($method, $url, [
            'name' => $existing->name,
            'zpl' => '^XA^XZ',
            'default_sample' => $sample,
            'default_analysis' => $analysis,
        ])->assertRedirect($redirect)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labeldesigns', [
            'id' => $existing->id, 'default_sample' => 1 - $sample, 'default_analysis' => 1 - $analysis,
        ]);
        $this->assertDatabaseHas('labeldesigns', [
            'name' => $existing->name, 'zpl' => '^XA^XZ', 'default_sample' => $sample, 'default_analysis' => $analysis,
        ]);
        $this->assertSame(1, LabelDesign::query()->where('default_sample', 1)->count());
        $this->assertSame(1, LabelDesign::query()->where('default_analysis', 1)->count());
        $this->assertDatabaseCount('labeldesigns', 2);
    }

    public function test_all_label_designs_and_their_printers_are_listed_without_pagination(): void
    {
        $printer = Printer::factory()->create(['name' => 'Balieprinter']);
        LabelDesign::factory()->count(26)->for($printer, 'printer')->create();
        $this->actingAs($this->userWithPermissions());

        $this->get(route('label-designs.index'))
            ->assertViewHas('labelDesigns', fn ($designs): bool => $designs->count() === 26 && $designs->first()->printer->name === 'Balieprinter')
            ->assertSee('Labelontwerpen')->assertSee('Balieprinter');
    }

    public function test_creation_form_supports_no_printers_and_no_defaults(): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->get(route('label-designs.create'))
            ->assertSee('Geen standaardprinter')->assertSee('Standaard voor monsters')->assertSee('Standaard voor analyses')->assertSee('ZPL-code');
    }

    public function test_edit_form_safely_embeds_verbatim_zpl_and_escapes_label_and_printer_names(): void
    {
        $zpl = "\n^XA\n^FD{{ sample_name }} </textarea><script>alert(1)</script>^FS\n^XZ\n";
        $printer = Printer::factory()->create(['name' => '<script>alert("printer")</script>']);
        $labelDesign = LabelDesign::factory()->for($printer, 'printer')->create([
            'name' => '<script>alert("label")</script>', 'zpl' => $zpl,
        ]);
        $this->actingAs($this->userWithPermissions());

        $this->get(route('label-designs.edit', $labelDesign))
            ->assertSee($labelDesign->name)->assertSee($printer->name)->assertSee((string) Js::from($zpl), false)
            ->assertDontSee($labelDesign->name, false)->assertDontSee($printer->name, false)->assertDontSee($zpl, false);
        $this->get(route('label-designs.index'))
            ->assertDontSee($labelDesign->name, false)->assertDontSee($printer->name, false)->assertDontSee($zpl, false);
    }

    public function test_update_preserves_zpl_and_moves_only_the_selected_default_to_the_updated_label(): void
    {
        $printer = Printer::factory()->create();
        $labelDesign = LabelDesign::factory()->for($printer, 'printer')->create([
            'default_sample' => true, 'default_analysis' => false,
        ]);
        $other = LabelDesign::factory()->create(['default_sample' => true, 'default_analysis' => true]);
        $zpl = "\n^XA\n^FD{analysis_name}^FS\n^XZ\n ";
        $this->actingAs($this->userWithPermissions());

        $this->put(route('label-designs.update', $labelDesign), [
            'id' => $other->id,
            'name' => 'Analyselabel',
            'zpl' => $zpl,
            'default_sample' => '0',
            'default_analysis' => '1',
            'default_printer' => '',
        ])->assertRedirect(route('label-designs.edit', $labelDesign))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labeldesigns', [
            'id' => $labelDesign->id, 'name' => 'Analyselabel', 'zpl' => $zpl,
            'default_sample' => 0, 'default_analysis' => 1, 'default_printer' => null,
        ]);
        $this->assertDatabaseHas('labeldesigns', [
            'id' => $other->id, 'name' => $other->name, 'default_sample' => 1, 'default_analysis' => 0,
        ]);
        $this->assertDatabaseCount('labeldesigns', 2);
    }

    public function test_saving_the_current_default_keeps_it_selected_and_unchecking_it_does_not_promote_another_design(): void
    {
        $labelDesign = LabelDesign::factory()->create(['default_sample' => true, 'default_analysis' => true]);
        $other = LabelDesign::factory()->create();
        $this->actingAs($this->userWithPermissions());
        $payload = ['name' => $labelDesign->name, 'zpl' => $labelDesign->zpl, 'default_sample' => 1, 'default_analysis' => 1];

        $this->put(route('label-designs.update', $labelDesign), $payload)
            ->assertRedirect(route('label-designs.edit', $labelDesign))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labeldesigns', ['id' => $labelDesign->id, 'default_sample' => 1, 'default_analysis' => 1]);
        $this->assertDatabaseHas('labeldesigns', ['id' => $other->id, 'default_sample' => 0, 'default_analysis' => 0]);

        $this->put(route('label-designs.update', $labelDesign), [...$payload, 'default_sample' => 0, 'default_analysis' => 0])
            ->assertRedirect(route('label-designs.edit', $labelDesign))->assertSessionHasNoErrors();

        $this->assertSame(0, LabelDesign::query()->where('default_sample', 1)->count());
        $this->assertSame(0, LabelDesign::query()->where('default_analysis', 1)->count());
    }

    public function test_deleting_a_label_leaves_other_labels_and_the_printer_intact(): void
    {
        $printer = Printer::factory()->create();
        $labelDesign = LabelDesign::factory()->for($printer, 'printer')->create();
        $other = LabelDesign::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->delete(route('label-designs.destroy', $labelDesign))->assertRedirect(route('label-designs.index'));

        $this->assertModelMissing($labelDesign);
        $this->assertModelExists($other);
        $this->assertModelExists($printer);
    }

    #[DataProvider('invalidLabelData')]
    public function test_invalid_label_data_is_rejected_without_writing(string $method, array $payload, array $errorKeys): void
    {
        $labelDesign = LabelDesign::factory()->create()->refresh();
        $original = $labelDesign->getAttributes();
        $this->actingAs($this->userWithPermissions());
        $url = $method === 'POST' ? route('label-designs.store') : route('label-designs.update', $labelDesign);

        $this->call($method, $url, $payload)->assertSessionHasErrors($errorKeys);

        $this->assertDatabaseCount('labeldesigns', 1);
        $this->assertSame($original, $labelDesign->fresh()->getAttributes());
    }

    #[DataProvider('labelEndpoints')]
    public function test_guests_are_redirected_to_login_on_label_design_endpoints(string $method, string $path): void
    {
        $this->call($method, '/admin/label-designs'.$path)->assertRedirect(route('login'));
    }

    #[DataProvider('labelEndpoints')]
    public function test_users_without_label_design_permission_cannot_read_or_modify_labels(string $method, string $path): void
    {
        $labelDesign = LabelDesign::factory()->create()->refresh();
        $original = $labelDesign->getAttributes();
        $this->actingAs($this->userWithPermissions(['administration.access', 'settings.advanced']));

        $this->call($method, '/admin/label-designs'.str_replace('__ID__', (string) $labelDesign->id, $path), [
            'name' => 'Changed', 'zpl' => '^XA^XZ', 'default_sample' => 0, 'default_analysis' => 0,
        ])->assertForbidden();

        $this->assertDatabaseCount('labeldesigns', 1);
        $this->assertSame($original, $labelDesign->fresh()->getAttributes());
    }

    public function test_navigation_is_hidden_without_label_design_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['administration.access']));

        $this->get(route('beheer.dashboard'))->assertDontSee(route('label-designs.index'), false);
    }

    #[DataProvider('boundLabelEndpoints')]
    public function test_unknown_labels_return_404_without_writing(string $method, string $path): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->call($method, '/admin/label-designs'.$path)->assertNotFound();

        $this->assertDatabaseCount('labeldesigns', 0);
    }

    public static function defaultSelections(): array
    {
        $cases = ['neither' => [0, 0], 'sample only' => [1, 0], 'analysis only' => [0, 1], 'both' => [1, 1]];
        $data = [];

        foreach (['POST', 'PUT'] as $method) {
            foreach ($cases as $name => [$sample, $analysis]) {
                $data[$method.' '.$name] = [$method, $sample, $analysis];
            }
        }

        return $data;
    }

    public static function invalidLabelData(): array
    {
        $valid = ['name' => 'Monsteretiket', 'zpl' => '^XA^XZ', 'default_sample' => 0, 'default_analysis' => 0];
        $cases = [
            'missing required fields' => [[], ['name', 'zpl', 'default_sample', 'default_analysis']],
            'long name' => [[...$valid, 'name' => str_repeat('L', 129)], ['name']],
            'non-string name' => [[...$valid, 'name' => ['Label']], ['name']],
            'empty ZPL' => [[...$valid, 'zpl' => '   '], ['zpl']],
            'non-string ZPL' => [[...$valid, 'zpl' => ['^XA^XZ']], ['zpl']],
            'oversized ZPL' => [[...$valid, 'zpl' => str_repeat('A', 65536)], ['zpl']],
            'invalid sample default' => [[...$valid, 'default_sample' => 2], ['default_sample']],
            'invalid analysis default' => [[...$valid, 'default_analysis' => 'yes'], ['default_analysis']],
            'missing printer' => [[...$valid, 'default_printer' => 999999], ['default_printer']],
            'non-integer printer' => [[...$valid, 'default_printer' => 'Zebra'], ['default_printer']],
        ];
        $data = [];

        foreach (['POST', 'PUT'] as $method) {
            foreach ($cases as $name => [$payload, $errorKeys]) {
                $data[$method.' '.$name] = [$method, $payload, $errorKeys];
            }
        }

        return $data;
    }

    public static function labelEndpoints(): array
    {
        return [
            'index' => ['GET', ''],
            'create' => ['GET', '/create'],
            'store' => ['POST', ''],
            'edit' => ['GET', '/__ID__/edit'],
            'update' => ['PUT', '/__ID__'],
            'delete' => ['DELETE', '/__ID__'],
        ];
    }

    public static function boundLabelEndpoints(): array
    {
        return [
            'edit' => ['GET', '/999999/edit'],
            'update' => ['PUT', '/999999'],
            'delete' => ['DELETE', '/999999'],
        ];
    }

    private function userWithPermissions(array $permissions = ['administration.access', 'labels.design']): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('label-design-test-role', 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole($role);

        return $user;
    }
}
