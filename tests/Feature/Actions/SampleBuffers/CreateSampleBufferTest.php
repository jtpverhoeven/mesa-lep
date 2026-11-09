<?php

namespace Tests\Feature\Actions\SampleBuffers;

use App\Actions\SampleBuffers\CreateSampleBuffer;
use App\Models\Cvar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSampleBufferTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_buffer_does_not_store_portal_order_information(): void
    {
        $buffer = app(CreateSampleBuffer::class)->handle([
            'client' => 42,
            'register_as' => 'buffer',
            'sampling_date' => '20-09-2026',
            'project' => null,
            'project_custom_fields' => ['Reference' => 'manual'],
            'sample_note' => 'Entered manually',
        ]);

        $this->assertSame(2, $buffer->source);
        $this->assertNull($buffer->getRawOriginal('portal_order_info'));
    }

    public function test_buffer_uses_the_default_sampling_method_unless_one_is_given(): void
    {
        Cvar::create(['cvar' => 'MESA_STD_SMPL_METHOD', 'value' => '7', 'default' => '0', 'description' => '']);

        $defaulted = app(CreateSampleBuffer::class)->handle(['client' => 42, 'register_as' => 'buffer']);
        $explicit = app(CreateSampleBuffer::class)->handle(['client' => 42, 'register_as' => 'buffer', 'sampling_method' => 3]);

        $this->assertSame(7, (int) $defaulted->sampling_method);
        $this->assertSame(3, (int) $explicit->sampling_method);
    }
}
