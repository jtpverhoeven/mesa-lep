<?php

namespace Tests\Feature;

use App\Actions\Worklists\PaginateWorklistRows;
use App\Actions\Worklists\SelectWorklistRows;
use App\Models\Assay;
use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleProcedure;
use App\Models\User;
use App\Models\Worklist;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class WorklistTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_saving_stores_the_legacy_shapes_and_groups_consecutive_columns(): void
    {
        $first = $this->assay(41, 'Totaal kiemgetal');
        $second = $this->assay(42, 'Listeria');
        $this->actingAs($this->userWithPermissions(['administration.access', 'worksheets.manage']));

        $this->post(route('worklists.store'), [
            'title' => 'Listeria', 'subtitle' => 'Ondertitel', 'footer_text' => 'Voet', 'no_columns' => '2', 'orientation' => 'L',
            'analyses' => [$first->id, $second->id, $first->id],
            'columns' => [
                ['name' => 'Telling', 'group' => 'Plaat', 'tick_on' => [$first->id]],
                ['name' => 'ALOA', 'group' => 'Plaat', 'tick_on' => []],
                ['name' => 'Opmerking', 'group' => '', 'tick_on' => []],
            ],
            'extra_rows' => "RM-LM\r\n\r\n blanco ",
            'extra_pages' => '<p><br></p>',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $worklist = Worklist::query()->firstOrFail();
        $this->assertSame([$first->id, $second->id], $worklist->analyses);
        $this->assertSame([1 => ['name' => 'Telling', 'tick_on' => [$first->id]], 2 => ['name' => 'ALOA'], 3 => ['name' => 'Opmerking']], $worklist->columns);
        $this->assertSame([['title' => '', 'colspan' => 1], ['title' => 'Plaat', 'colspan' => 2], ['title' => '', 'colspan' => 1]], $worklist->colum_groups);
        $this->assertSame(['RM-LM', 'blanco'], $worklist->extra_rows);
        $this->assertSame('', $worklist->extra_pages);
        $this->assertSame(['Plaat', 'Plaat', ''], array_column($worklist->columnList(), 'group'));
    }

    public function test_extra_pages_images_move_into_private_storage(): void
    {
        Storage::fake('local');
        $this->actingAs($this->userWithPermissions(['administration.access', 'worksheets.manage']));

        $this->post(route('worklists.store'), [
            'title' => 'Beeld', 'no_columns' => '1', 'orientation' => 'P',
            'extra_pages' => '<p>Zie <img src="data:image/png;base64,'.self::PNG.'"></p>',
        ])->assertSessionHasNoErrors();

        $worklist = Worklist::query()->firstOrFail();
        $this->assertMatchesRegularExpression('#src="/worklists/'.$worklist->id.'/images/[a-f0-9]{40}\.png"#', $worklist->extra_pages);
        $this->assertCount(1, Storage::disk('local')->files('worklists/'.$worklist->id));

        $image = basename(Storage::disk('local')->files('worklists/'.$worklist->id)[0]);
        $this->get(route('worklists.images.show', [$worklist, $image]))->assertOk();

        $this->delete(route('worklists.destroy', $worklist))->assertRedirect(route('worklists.index'));
        $this->assertSame([], Storage::disk('local')->files('worklists/'.$worklist->id));
    }

    public function test_managing_and_printing_need_their_own_permissions(): void
    {
        $worklist = Worklist::factory()->create();

        $this->actingAs($this->userWithPermissions(['lists.view']));
        $this->get(route('worklists.index'))->assertForbidden();
        $this->get(route('worklists.edit', $worklist))->assertForbidden();
        $this->get(route('worklists.print'))->assertOk()->assertSee('worklist-print', false);

        $this->actingAs($this->userWithPermissions(['administration.access', 'worksheets.manage'], 'manager'));
        $this->get(route('worklists.edit', $worklist))->assertOk()->assertSee('worklist-form', false);
        $this->get(route('worklists.print'))->assertForbidden();
        $this->get(route('worklists.pdf', ['worklist' => 'all', 'date' => '2026-11-13']))->assertForbidden();
    }

    public function test_the_editor_shows_the_newest_revision_of_the_stored_analyses(): void
    {
        $old = $this->assay(41, 'Totaal kiemgetal');
        $new = $this->assay(41, 'Totaal kiemgetal');
        $worklist = Worklist::factory()->create([
            'analyses' => [$old->id],
            'columns' => [1 => ['name' => 'Telling', 'tick_on' => [$old->id]]],
        ]);
        $this->actingAs($this->userWithPermissions(['administration.access', 'worksheets.manage']));

        $page = $this->get(route('worklists.edit', $worklist))->assertOk();

        $this->assertSame([$new->id], $page->viewData('initial')['analyses']);
        $this->assertSame([$new->id], $page->viewData('initial')['columns'][0]['tick_on']);
        $this->assertCount(1, $page->viewData('assays'));
    }

    public function test_rows_follow_the_inoculation_window_and_tick_columns_per_follow_number(): void
    {
        $plate = $this->assay(41, 'Totaal kiemgetal');
        $listeria = $this->assay(42, 'Listeria');
        $worklist = Worklist::factory()->create([
            'analyses' => [$plate->id],
            'columns' => [1 => ['name' => 'Telling', 'tick_on' => [$plate->id]], 2 => ['name' => 'Listeria', 'tick_on' => [$listeria->id]], 3 => ['name' => 'Leeg']],
        ]);
        [$start, $end] = $this->window();
        $inside = $this->sample('S1', $start + 3600);
        $this->analysis($inside, 1, $plate);
        $this->analysis($inside, 1, $plate);
        $this->analysis($inside, 1, $listeria);
        $this->analysis($inside, 2, $listeria);
        $this->analysis($this->sample('S2', $end), 1, $plate);
        $this->analysis($this->sample('S3', $start - 1), 1, $plate);

        $rows = app(SelectWorklistRows::class)->handle($worklist, $start, $end);

        $this->assertCount(1, $rows);
        $this->assertSame('S1.1.1', $rows[0]['barcode']);
        $this->assertSame('Laboratorium', $rows[0]['client']);
        $this->assertSame([true, true, false], $rows[0]['ticks']);
    }

    public function test_a_worklist_without_analyses_selects_nothing(): void
    {
        [$start, $end] = $this->window();
        $this->analysis($this->sample('S1', $start), 1, $this->assay(41, 'Totaal kiemgetal'));

        $this->assertSame([], app(SelectWorklistRows::class)->handle(Worklist::factory()->create(), $start, $end));
    }

    public function test_pages_keep_worklist_rows_at_the_end_of_the_last_column(): void
    {
        $worklist = Worklist::factory()->make(['no_columns' => 2, 'extra_rows' => ['RM-LM', 'blanco']]);
        $rows = array_map(fn (int $number): array => ['sample_number' => (string) $number], range(1, 23));

        $pages = app(PaginateWorklistRows::class)->handle($worklist, $rows);

        $kinds = fn (array $lines): array => array_count_values(array_column($lines, 'kind'));

        $this->assertCount(2, $pages);
        $this->assertSame(['sample' => 12], $kinds($pages[0][0]));
        $this->assertSame(['sample' => 10, 'custom' => 2], $kinds($pages[0][1]));
        $this->assertSame(['sample' => 1, 'blank' => 11], $kinds($pages[1][0]));
        $this->assertSame(['blank' => 10, 'custom' => 2], $kinds($pages[1][1]));
        $this->assertSame('22', $pages[0][1][9]['row']['sample_number']);
        $this->assertSame('23', $pages[1][0][0]['row']['sample_number']);
        $this->assertSame(['RM-LM', 'blanco'], array_column(array_slice($pages[1][1], -2), 'text'));
    }

    public function test_a_single_worklist_is_printed_as_pdf(): void
    {
        [$worklist, $date] = $this->printableWorklist();
        $this->actingAs($this->userWithPermissions(['lists.view']));

        $response = $this->get(route('worklists.pdf', ['worklist' => $worklist->id, 'date' => $date]))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_all_worklists_with_samples_are_zipped_with_unique_names(): void
    {
        [$worklist, $date] = $this->printableWorklist();
        Worklist::factory()->create(['title' => $worklist->title, 'analyses' => $worklist->analyses]);
        Worklist::factory()->create(['title' => 'Zonder monsters', 'analyses' => []]);
        $this->actingAs($this->userWithPermissions(['lists.view']));

        $response = $this->get(route('worklists.pdf', ['worklist' => 'all', 'date' => $date]))->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $zip->open($path);
        $names = array_map(fn (int $index): string => $zip->getNameIndex($index), range(0, $zip->numFiles - 1));
        $zip->close();
        unlink($path);

        $this->assertSame([$worklist->title.'.pdf', $worklist->title.' (1).pdf'], $names);
    }

    public function test_printing_a_day_without_samples_returns_to_the_print_page(): void
    {
        [$worklist] = $this->printableWorklist();
        $this->actingAs($this->userWithPermissions(['lists.view']));

        $this->get(route('worklists.pdf', ['worklist' => $worklist->id, 'date' => '2020-01-01']))
            ->assertRedirect(route('worklists.print'))
            ->assertSessionHasErrors('date');
        $this->get(route('worklists.pdf', ['worklist' => 'all', 'date' => 'morgen']))->assertSessionHasErrors('date');
    }

    private function userWithPermissions(array $permissions, string $role = 'worklist-test-role'): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate($role, 'web');
        $role->syncPermissions($permissions);
        $user = User::factory()->create(['enabled' => 1]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{Worklist, string} */
    private function printableWorklist(): array
    {
        $assay = $this->assay(41, 'Totaal kiemgetal');
        $worklist = Worklist::factory()->create([
            'title' => 'Kiemgetal', 'analyses' => [$assay->id], 'extra_rows' => ['blanco'], 'colum_groups' => [['title' => '', 'colspan' => 1], ['title' => 'Plaat', 'colspan' => 1]],
            'columns' => [1 => ['name' => 'Telling', 'tick_on' => [$assay->id]]],
        ]);
        [$start] = $this->window();
        $this->analysis($this->sample('S1', $start + 60), 1, $assay);

        return [$worklist, '2026-11-13'];
    }

    /** @return array{int, int} */
    private function window(): array
    {
        $start = CarbonImmutable::parse('2026-11-13', 'Europe/Amsterdam')->getTimestamp();

        return [$start, $start + 23 * 3600];
    }

    private function assay(int $originalId, string $name): Assay
    {
        return Assay::create([
            'original_id' => $originalId, 'name' => $name, 'type_base' => 0, 'media_id' => '{}',
            'dillution' => 1, 'replicates' => 1, 'confirmation' => 0, 'confirmation_script' => '{}',
            'type' => 1, 'meta_assays' => '', 'max_count' => 300, 'min_count' => 10, 'script' => '',
            'custom_fields' => '{}', 'duration' => '2', 'start_from' => 'i', 'article_code' => '', 'active' => 1,
        ]);
    }

    private function sample(string $barcode, int $inoculated): Sample
    {
        $client = Client::query()->firstOrCreate(['name' => 'Laboratorium'], ['active' => 1]);
        $project = Project::query()->firstOrCreate(['client' => $client->id], ['subclient' => 0, 'project_name' => 'Werklijst', 'lock_pass' => 'private']);
        $procedure = SampleProcedure::query()->firstOrCreate(['name' => 'Scheppen'], ['fields' => '{}', 'active' => 1, 'hide' => 0]);

        return Sample::create([
            'barcode' => $barcode, 'follow_no' => 1001, 'description' => 'Water', 'sampling_method' => $procedure->id,
            'date_registered' => '1791936000', 'registered_by' => 1, 'client' => $client->id, 'subclient' => 0,
            'project' => $project->id, 'custom_fields' => '{}', 'predicted_end' => 1791936000,
            'sample_innoculated' => (string) $inoculated, 'stored_in' => '', 'diluted_at' => '1', 'analyses_data' => '[]',
            'sample_type' => 'S', 'leg_type' => '',
        ]);
    }

    private function analysis(Sample $sample, int $follow, Assay $assay): SampleAnalysis
    {
        return SampleAnalysis::create([
            'profile_group' => 1, 'sample' => $sample->id, 'follow_number' => $follow, 'profile' => 0, 'assay' => 0,
            'assay_base' => $assay->id, 'original_assay_base' => $assay->original_id, 'roaming_id' => 0,
            'project' => $sample->project, 'predicted_end' => 1,
        ]);
    }
}
