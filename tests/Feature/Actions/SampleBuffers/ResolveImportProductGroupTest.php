<?php

namespace Tests\Feature\Actions\SampleBuffers;

use App\Actions\SampleBuffers\ResolveImportProductGroup;
use App\Models\ProductGroup;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ResolveImportProductGroupTest extends TestCase
{
    public function test_prefers_the_exact_name_over_looser_matches(): void
    {
        $groups = $this->productGroups(['Drinkwater', 'drinkwater']);

        $match = app(ResolveImportProductGroup::class)->handle($groups, ' drinkwater ');

        $this->assertSame(2, $match->portal_id);
    }

    public function test_ignores_case_and_repeated_whitespace_when_exactly_one_group_fits(): void
    {
        $match = app(ResolveImportProductGroup::class)->handle($this->productGroups(['Zwemwater  Oppervlakte']), 'zwemwater oppervlakte');

        $this->assertSame(1, $match->portal_id);
    }

    public function test_ignores_diacritics_when_exactly_one_group_fits(): void
    {
        $match = app(ResolveImportProductGroup::class)->handle($this->productGroups(['Café Zoë', 'Drinkwater']), 'cafe zoe');

        $this->assertSame(1, $match->portal_id);
    }

    public function test_composes_decomposed_diacritics_before_comparing(): void
    {
        $match = app(ResolveImportProductGroup::class)->handle($this->productGroups(['Café']), "Cafe\u{0301}");

        $this->assertSame(1, $match->portal_id);
    }

    public function test_does_not_guess_between_groups_that_only_differ_by_diacritics(): void
    {
        $match = app(ResolveImportProductGroup::class)->handle($this->productGroups(['Café', 'Cafè']), 'cafe');

        $this->assertNull($match);
    }

    public function test_never_matches_a_group_that_is_inactive_for_the_client(): void
    {
        $groups = $this->productGroups(['Drinkwater', 'Zwemwater'], inactive: ['Zwemwater']);

        $this->assertNull(app(ResolveImportProductGroup::class)->handle($groups, 'Zwemwater'));
        $this->assertSame(1, app(ResolveImportProductGroup::class)->handle($groups, 'Drinkwater')->portal_id);
    }

    public function test_treats_a_group_without_visibility_as_active(): void
    {
        $groups = collect([new ProductGroup(['portal_id' => 5, 'name' => 'Drinkwater', 'visible' => null])]);

        $this->assertSame(5, app(ResolveImportProductGroup::class)->handle($groups, 'Drinkwater')->portal_id);
    }

    public function test_returns_nothing_for_blank_or_unknown_text(): void
    {
        $groups = $this->productGroups(['Drinkwater']);

        $this->assertNull(app(ResolveImportProductGroup::class)->handle($groups, '   '));
        $this->assertNull(app(ResolveImportProductGroup::class)->handle($groups, 'Afvalwater'));
    }

    /**
     * @param  list<string>  $names
     * @param  list<string>  $inactive
     * @return Collection<int, ProductGroup>
     */
    private function productGroups(array $names, array $inactive = []): Collection
    {
        return collect($names)->map(fn (string $name, int $index): ProductGroup => new ProductGroup([
            'portal_id' => $index + 1,
            'name' => $name,
            'visible' => in_array($name, $inactive, true) ? 0 : 1,
        ]));
    }
}
