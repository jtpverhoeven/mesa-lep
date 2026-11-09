<?php

namespace Tests\Feature;

use App\Models\Cvar;
use App\Support\Cvars;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CvarsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_a_shared_instance_that_loads_the_table_once(): void
    {
        Cvar::create(['cvar' => 'A', 'value' => '1', 'default' => '0', 'description' => '']);

        $this->assertSame(app(Cvars::class), app(Cvars::class));

        DB::enableQueryLog();
        app(Cvars::class)->value('A');
        app(Cvars::class)->value('A');
        app(Cvars::class)->value('missing');

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_value_or_default_falls_back_from_value_to_default_to_fallback(): void
    {
        Cvar::create(['cvar' => 'SET', 'value' => '5', 'default' => '9', 'description' => '']);
        Cvar::create(['cvar' => 'UNSET', 'value' => null, 'default' => '9', 'description' => '']);

        $cvars = app(Cvars::class);

        $this->assertSame('5', $cvars->valueOrDefault('SET', 'x'));
        $this->assertSame('9', $cvars->valueOrDefault('UNSET', 'x'));
        $this->assertSame('x', $cvars->valueOrDefault('MISSING', 'x'));
        $this->assertNull($cvars->value('MISSING'));
    }

    public function test_json_returns_an_empty_array_for_missing_or_invalid_values(): void
    {
        Cvar::create(['cvar' => 'LIST', 'value' => '["a","b"]', 'default' => '', 'description' => '']);
        Cvar::create(['cvar' => 'BAD', 'value' => 'nope', 'default' => '', 'description' => '']);

        $cvars = app(Cvars::class);

        $this->assertSame(['a', 'b'], $cvars->json('LIST'));
        $this->assertSame([], $cvars->json('BAD'));
        $this->assertSame([], $cvars->json('MISSING'));
    }

    public function test_changes_through_eloquent_or_set_are_visible_immediately(): void
    {
        $cvars = app(Cvars::class);
        $this->assertNull($cvars->value('A'));

        $cvars->set('A', '1', ['default' => '0', 'description' => 'first']);
        $this->assertSame('1', $cvars->value('A'));

        Cvar::query()->where('cvar', 'A')->firstOrFail()->update(['value' => '2']);
        $this->assertSame('2', $cvars->value('A'));
        $this->assertSame('first', $cvars->find('A')->description);

        Cvar::query()->where('cvar', 'A')->firstOrFail()->delete();
        $this->assertNull($cvars->value('A'));
    }
}
