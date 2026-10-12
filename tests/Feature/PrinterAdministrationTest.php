<?php

namespace Tests\Feature;

use App\Models\Printer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrinterAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_shared_printer_with_legacy_fields(): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->post('/admin/printers', [
            'name' => 'Monsterbalie',
            'adres' => '\\\\printserver\\zebra',
            'port' => '',
            'type' => '0',
        ])->assertRedirect('/admin/printers')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('printers', [
            'name' => 'Monsterbalie',
            'adres' => '\\\\printserver\\zebra',
            'port' => 0,
            'type' => 0,
        ]);
    }

    public function test_administrator_can_create_a_print_server_without_accepting_a_posted_id(): void
    {
        $existingPrinter = Printer::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->post(route('printers.store'), [
            'id' => $existingPrinter->id,
            'name' => str_repeat('Z', 128),
            'adres' => '2001:db8::1',
            'port' => '65535',
            'type' => '1',
        ])->assertRedirect(route('printers.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('printers', 2);
        $this->assertDatabaseHas('printers', [
            'name' => str_repeat('Z', 128), 'adres' => '2001:db8::1', 'port' => 65535, 'type' => 1,
        ]);
        $this->assertSame($existingPrinter->name, $existingPrinter->fresh()->name);
    }

    public function test_administrator_can_view_the_printer_list_and_creation_form(): void
    {
        $printer = Printer::factory()->create(['name' => 'Monsterbalie']);
        $this->actingAs($this->userWithPermissions());

        $this->get(route('printers.index'))
            ->assertViewHas('printers', fn ($printers): bool => $printers->modelKeys() === [$printer->id])
            ->assertSee('Labels')->assertSee('Printers')->assertSee('Monsterbalie');
        $this->get(route('printers.create'))->assertSee('Printer toevoegen')->assertSee('Gedeelde printer')->assertSee('Print server');
    }

    public function test_printer_list_and_edit_form_escape_user_provided_fields(): void
    {
        $printer = Printer::factory()->create([
            'name' => '<script>alert("name")</script>',
            'adres' => '<script>alert("adres")</script>',
        ]);
        $this->actingAs($this->userWithPermissions());

        $this->get(route('printers.index'))
            ->assertDontSee($printer->name, false)->assertDontSee($printer->adres, false);
        $this->get(route('printers.edit', $printer))
            ->assertSee($printer->name)->assertSee($printer->adres)
            ->assertDontSee($printer->name, false)->assertDontSee($printer->adres, false);
    }

    public function test_administrator_can_update_a_printer_without_changing_its_name_or_identity(): void
    {
        $printer = Printer::factory()->create();
        $otherPrinter = Printer::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->put(route('printers.update', $printer), [
            'id' => $otherPrinter->id,
            'name' => $printer->name,
            'adres' => 'zebra.lab.local',
            'port' => '',
            'type' => '0',
        ])->assertRedirect(route('printers.edit', $printer))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('printers', [
            'id' => $printer->id, 'name' => $printer->name, 'adres' => 'zebra.lab.local', 'port' => 0, 'type' => 0,
        ]);
        $this->assertSame($otherPrinter->adres, $otherPrinter->fresh()->adres);
        $this->assertDatabaseCount('printers', 2);
    }

    public function test_administrator_can_delete_only_the_selected_printer(): void
    {
        $printer = Printer::factory()->create();
        $otherPrinter = Printer::factory()->create();
        $this->actingAs($this->userWithPermissions());

        $this->delete(route('printers.destroy', $printer))->assertRedirect(route('printers.index'));

        $this->assertModelMissing($printer);
        $this->assertModelExists($otherPrinter);
    }

    #[DataProvider('writeMethods')]
    public function test_duplicate_names_are_rejected_without_changing_printers(string $method): void
    {
        $existingPrinter = Printer::factory()->create(['name' => 'Monsterbalie']);
        $printer = Printer::factory()->create();
        $this->actingAs($this->userWithPermissions());
        $url = $method === 'POST' ? route('printers.store') : route('printers.update', $printer);

        $this->call($method, $url, [
            'name' => $existingPrinter->name, 'adres' => '192.168.0.50', 'port' => 9100, 'type' => 1,
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('printers', 2);
        $this->assertSame($printer->name, $printer->fresh()->name);
    }

    #[DataProvider('invalidPrinterData')]
    public function test_invalid_printer_data_is_rejected_without_writing(string $method, array $invalidData, array $errorKeys): void
    {
        $printer = Printer::factory()->create()->refresh();
        $original = $printer->getAttributes();
        $this->actingAs($this->userWithPermissions());
        $url = $method === 'POST' ? route('printers.store') : route('printers.update', $printer);

        $this->call($method, $url, $invalidData)->assertSessionHasErrors($errorKeys);

        $this->assertDatabaseCount('printers', 1);
        $this->assertSame($original, $printer->fresh()->getAttributes());
    }

    #[DataProvider('printerEndpoints')]
    public function test_guests_are_redirected_to_login_on_all_printer_endpoints(string $method, string $path): void
    {
        $this->call($method, '/admin/printers'.$path)->assertRedirect(route('login'));
    }

    #[DataProvider('printerEndpoints')]
    public function test_users_without_advanced_settings_permission_cannot_access_printers(string $method, string $path): void
    {
        $printer = Printer::factory()->create();
        $this->actingAs($this->userWithPermissions(['administration.access']));

        $this->call($method, '/admin/printers'.str_replace('__ID__', (string) $printer->id, $path), [
            'name' => 'Changed', 'adres' => '192.168.0.50', 'port' => 9100, 'type' => 1,
        ])->assertForbidden();

        $this->assertModelExists($printer);
        $this->assertSame($printer->name, $printer->fresh()->name);
        $this->assertDatabaseCount('printers', 1);
    }

    public function test_printer_navigation_is_hidden_without_advanced_settings_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['administration.access']));

        $this->get(route('beheer.dashboard'))->assertDontSee(route('printers.index'), false);
    }

    #[DataProvider('boundPrinterEndpoints')]
    public function test_unknown_printers_return_404_without_writing(string $method, string $path): void
    {
        $this->actingAs($this->userWithPermissions());

        $this->call($method, '/admin/printers'.$path)->assertNotFound();

        $this->assertDatabaseCount('printers', 0);
    }

    public static function writeMethods(): array
    {
        return ['create' => ['POST'], 'update' => ['PUT']];
    }

    public static function invalidPrinterData(): array
    {
        $validData = ['name' => 'Monsterbalie', 'adres' => '192.168.0.50', 'port' => 9100, 'type' => 1];
        $cases = [
            'missing required fields' => [[], ['name', 'adres', 'type']],
            'long name' => [[...$validData, 'name' => str_repeat('Z', 129)], ['name']],
            'non-string name' => [[...$validData, 'name' => ['Zebra']], ['name']],
            'long address' => [[...$validData, 'adres' => str_repeat('A', 46)], ['adres']],
            'non-string address' => [[...$validData, 'adres' => ['192.168.0.50']], ['adres']],
            'unknown type' => [[...$validData, 'type' => 2], ['type']],
            'non-integer type' => [[...$validData, 'type' => 'shared'], ['type']],
            'negative port' => [[...$validData, 'port' => -1], ['port']],
            'oversized port' => [[...$validData, 'port' => 65536], ['port']],
            'non-integer port' => [[...$validData, 'port' => '9100.5'], ['port']],
        ];
        $data = [];

        foreach (['POST', 'PUT'] as $method) {
            foreach ($cases as $name => [$payload, $errorKeys]) {
                $data[$method.' '.$name] = [$method, $payload, $errorKeys];
            }
        }

        return $data;
    }

    public static function printerEndpoints(): array
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

    public static function boundPrinterEndpoints(): array
    {
        return [
            'edit' => ['GET', '/999999/edit'],
            'update' => ['PUT', '/999999'],
            'delete' => ['DELETE', '/999999'],
        ];
    }

    private function userWithPermissions(array $permissions = ['administration.access', 'settings.advanced']): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('printer-test-role', 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole($role);

        return $user;
    }
}
