<?php

namespace Tests\Feature;

use App\Models\Cvar;
use App\Models\SampleProcedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SampleFormDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_data_exposes_the_configured_default_sampling_method(): void
    {
        $this->signIn();
        $procedure = SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0]);
        Cvar::create(['cvar' => 'MESA_STD_SMPL_METHOD', 'value' => (string) $procedure->id, 'default' => '0', 'description' => '']);

        $this->getJson(route('samples.form-data'))->assertJsonPath('default_sampling_method', $procedure->id);
    }

    public function test_form_data_has_no_default_when_the_cvar_is_unset_or_points_at_an_inactive_procedure(): void
    {
        $this->signIn();
        $inactive = SampleProcedure::create(['name' => 'Oud', 'fields' => '{}', 'active' => 0, 'hide' => 0]);

        $this->getJson(route('samples.form-data'))->assertJsonPath('default_sampling_method', null);

        Cvar::create(['cvar' => 'MESA_STD_SMPL_METHOD', 'value' => (string) $inactive->id, 'default' => '0', 'description' => '']);

        $this->getJson(route('samples.form-data'))->assertJsonPath('default_sampling_method', null);
    }

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create(['enabled' => 1]));
        Gate::define('samples.create', fn (): bool => true);
    }
}
