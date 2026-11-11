<?php

namespace Tests\Feature\Actions\SampleBuffers;

use App\Actions\SampleBuffers\CommitSampleBuffers;
use App\Actions\SampleBuffers\ImportSampleBuffers;
use App\ClientPortal\ClientPortalService;
use App\Models\Client;
use App\Models\Cvar;
use App\Models\SampleBuffer;
use App\Models\SampleProcedure;
use App\Models\User;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportSampleBuffersTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_csv_row_in_the_buffer_like_the_legacy_import(): void
    {
        $this->travelTo('2026-09-21 10:00:00');
        Cvar::create(['cvar' => 'MESA_STD_SMPL_METHOD', 'value' => '7', 'default' => '0', 'description' => '']);
        $client = $this->client();

        $buffers = app(ImportSampleBuffers::class)->handle($client, [$this->row([
            'misc_directions' => 'Koel vervoeren',
            'analyses_selected' => 'Legionella + kiemgetal',
            'product_group' => 11,
        ])], []);

        $stored = (array) DB::table('samplebuffers')->first();
        $this->assertSame($buffers->first()->id, $stored['id']);
        $this->assertSame([
            'client' => $client->id,
            'source' => 1,
            'project' => 'PRJ-'.$client->id.'-20092026',
            'sampling_date' => '20-09-2026',
            'sampling_method' => 7,
            'sample_name' => 'Kraan Zoë',
            'sample_details' => 'Keuken',
            'tht' => 0,
            'tht_date' => null,
            'analyses_selected' => 'Legionella + kiemgetal',
            'misc_directions' => 'Koel vervoeren',
            'authorized' => 0,
            'project_name' => 'Project A',
            'portal_product_group_id' => 11,
            'date_registered' => (string) now()->timestamp,
        ], array_intersect_key($stored, array_flip([
            'client', 'source', 'project', 'sampling_date', 'sampling_method', 'sample_name', 'sample_details', 'tht', 'tht_date',
            'authorized', 'project_name', 'analyses_selected', 'misc_directions', 'portal_product_group_id', 'date_registered',
        ])));
    }

    public function test_a_tht_row_keeps_its_tht_date_and_other_rows_drop_it(): void
    {
        app(ImportSampleBuffers::class)->handle($this->client(), [
            $this->row(['tht' => true, 'tht_date' => '2026-12-25']),
            $this->row(['tht' => false, 'tht_date' => '2026-12-25']),
        ], []);

        $this->assertSame([[1, '2026-12-25'], [0, null]], DB::table('samplebuffers')->orderBy('id')->get(['tht', 'tht_date'])->map(fn (object $row): array => [$row->tht, $row->tht_date])->all());
    }

    public function test_keeps_only_the_chosen_metadata_columns_and_drops_blank_values(): void
    {
        app(ImportSampleBuffers::class)->handle($this->client(), [
            $this->row(['meta' => ['Gebouw 2', '', '0', 'wordt niet gekozen']]),
        ], ['Locatie', 'Opmerking', 'Aantal']);

        $this->assertSame('{"Locatie":"Gebouw 2","Aantal":"0"}', DB::table('samplebuffers')->value('meta'));
    }

    public function test_stores_a_blank_project_name_as_null(): void
    {
        app(ImportSampleBuffers::class)->handle($this->client(), [$this->row(['project_name' => null])], []);

        $this->assertNull(DB::table('samplebuffers')->value('project_name'));
    }

    public function test_imports_nothing_when_one_row_cannot_be_stored(): void
    {
        $this->expectException(InvalidFormatException::class);

        try {
            app(ImportSampleBuffers::class)->handle($this->client(), [$this->row(), $this->row(['sampling_date' => 'geen datum'])], []);
        } finally {
            $this->assertDatabaseCount('samplebuffers', 0);
        }
    }

    public function test_imported_rows_can_be_committed_to_samples_by_the_existing_buffer_flow(): void
    {
        $this->actingAs($user = User::factory()->create(['enabled' => 1]));
        $this->mock(ClientPortalService::class);
        $procedure = SampleProcedure::create(['name' => 'Scheppen', 'fields' => '{}', 'active' => 1, 'hide' => 0]);
        Cvar::create(['cvar' => 'MESA_STD_SMPL_METHOD', 'value' => (string) $procedure->id, 'default' => '0', 'description' => '']);
        $buffers = app(ImportSampleBuffers::class)->handle($this->client(), [$this->row([
            'misc_directions' => 'Koel',
            'analyses_selected' => 'Legionella',
            'product_group' => 11,
        ])], []);

        $sample = app(CommitSampleBuffers::class)
            ->handle($buffers->pluck('id')->all(), $user->id, ['receive_date' => '2026-11-10', 'receive_time' => '11:15'])
            ->first()->fresh();

        $this->assertSame('Kraan Zoë', $sample->client_description);
        $this->assertSame(11, (int) $sample->portal_product_group_id);
        $this->assertSame(['misc_directions' => 'Koel', 'analyses_selected' => 'Legionella'], json_decode($sample->analyses_data, true));
        $this->assertSame(1, (int) $sample->source);
        $this->assertSame(0, SampleBuffer::count());
    }

    private function client(): Client
    {
        return Client::create(['name' => 'Ziekenhuis Zoë', 'active' => 1]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return [
            'sampling_date' => '2026-09-20',
            'project_name' => 'Project A',
            'sample_name' => 'Kraan Zoë',
            'sample_details' => 'Keuken',
            'tht' => false,
            'tht_date' => null,
            'analyses_selected' => '',
            'misc_directions' => null,
            'product_group' => null,
            'meta' => [],
            ...$overrides,
        ];
    }
}
