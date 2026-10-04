<?php

namespace Tests\Feature\Actions\SampleBuffers;

use App\Actions\SampleBuffers\CreateSampleBuffer;
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
}
