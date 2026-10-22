<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SampleClientDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_details_include_contact_information_particulars_and_file_descriptions(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.create', fn (): bool => true);
        $client = Client::create([
            'name' => 'Testklant', 'reference' => 'TEST', 'telephone' => '0101234567',
            'street_name' => 'Teststraat', 'street_number' => '12', 'postal_code' => '1234AB', 'place' => 'Teststad',
            'notes' => '<p>Gekoeld vervoeren</p>',
            'attachment' => json_encode([
                'first' => ['file_description' => 'Werkinstructie', 'file_name' => 'private.pdf'],
                'second' => ['file_name' => 'other.pdf'],
            ]),
        ]);

        $response = $this->getJson(route('samples.clients.details', $client));

        $response->assertExactJson(['data' => [
            'reference' => 'TEST',
            'telephone' => '0101234567',
            'address' => 'Teststraat 12 1234AB Teststad',
            'notes' => '<p>Gekoeld vervoeren</p>',
            'files' => [['name' => 'Werkinstructie'], ['name' => 'Bestand']],
        ]]);
    }

    public function test_details_without_particulars_or_files_return_an_empty_file_list(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.create', fn (): bool => true);
        $client = Client::create(['name' => 'Testklant']);

        $response = $this->getJson(route('samples.clients.details', $client));

        $response->assertExactJson(['data' => [
            'reference' => null, 'telephone' => null, 'address' => '', 'notes' => null, 'files' => [],
        ]]);
    }

    public function test_unauthenticated_details_request_returns_401(): void
    {
        $this->getJson(route('samples.clients.details', 1))->assertUnauthorized();
    }

    public function test_details_request_without_sample_creation_permission_returns_403(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.create', fn (): bool => false);
        $client = Client::create(['name' => 'Testklant']);

        $this->getJson(route('samples.clients.details', $client))->assertForbidden();
    }

    public function test_details_for_an_unknown_client_return_404(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.create', fn (): bool => true);

        $this->getJson(route('samples.clients.details', 999999))->assertNotFound();
    }

    #[DataProvider('registrationPages')]
    public function test_registration_pages_supply_the_shared_client_details_endpoint(string $route, array $parameters): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.create', fn (): bool => true);

        $this->get(route($route, $parameters))->assertSee(
            trim(Js::encode(Js::encode(route('samples.clients.details', ['client' => '__CLIENT__']))), '"'),
            escape: false,
        );
    }

    public static function registrationPages(): array
    {
        return [
            'regular' => ['samples.create', []],
            'legionella' => ['samples.bulk.create', ['registrationType' => 'legionella']],
            'rodac' => ['samples.bulk.create', ['registrationType' => 'rodac']],
        ];
    }
}
