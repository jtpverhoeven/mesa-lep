<?php

namespace App\Models;

use Database\Factories\WorklistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'analyses', 'no_columns', 'title', 'subtitle', 'footer_text', 'columns',
    'colum_groups', 'extra_rows', 'extra_pages', 'orientation',
])]
class Worklist extends Model
{
    /** @use HasFactory<WorklistFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Rows per column on a printed page.
     */
    public const ROWS_PER_COLUMN = 12;

    protected function casts(): array
    {
        return [
            'analyses' => 'array',
            'no_columns' => 'integer',
            'columns' => 'array',
            'colum_groups' => 'array',
            'extra_rows' => 'array',
        ];
    }

    /**
     * The columns as an ordered list of name, group title and the analyses that tick it.
     *
     * Legacy groups span columns starting at the sample column, so the first group cell covers that one.
     *
     * @return list<array{name: string, tick_on: list<int>, group: string}>
     */
    public function columnList(): array
    {
        $groups = array_values($this->colum_groups ?? []);
        $titles = [];

        foreach ($groups as $group) {
            $titles = [...$titles, ...array_fill(0, max(1, (int) ($group['colspan'] ?? 1)), (string) ($group['title'] ?? ''))];
        }

        return collect(array_values($this->columns ?? []))
            ->map(fn (array $column, int $index): array => [
                'name' => (string) ($column['name'] ?? ''),
                'tick_on' => array_map('intval', $column['tick_on'] ?? []),
                'group' => $titles[$index + 1] ?? '',
            ])
            ->all();
    }
}
