<?php

namespace App\Actions\Worklists;

use App\Actions\Media\StoreEditorImages;
use App\Models\Worklist;
use Illuminate\Support\Facades\DB;

class SaveWorklist
{
    public function __construct(private StoreEditorImages $images) {}

    /**
     * @param  array{
     *     title: string, subtitle?: ?string, footer_text?: ?string, no_columns: int|string, orientation: string,
     *     analyses?: ?list<int|string>, extra_rows?: ?string, extra_pages?: ?string,
     *     columns?: ?list<array{name?: ?string, group?: ?string, tick_on?: ?list<int|string>}>
     * }  $data
     */
    public function handle(Worklist $worklist, array $data): Worklist
    {
        return DB::transaction(function () use ($worklist, $data): Worklist {
            $columns = array_values($data['columns'] ?? []);

            $worklist->fill([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'footer_text' => $data['footer_text'] ?? null,
                'no_columns' => (int) $data['no_columns'],
                'orientation' => $data['orientation'],
                'analyses' => $this->ids($data['analyses'] ?? []),
                'columns' => $this->columns($columns),
                'colum_groups' => $this->groups($columns),
                'extra_rows' => $this->lines($data['extra_rows'] ?? ''),
                'extra_pages' => $this->extraPages($data['extra_pages'] ?? ''),
            ])->save();

            $html = $this->images->handle($worklist, (string) $worklist->extra_pages);

            if ($html !== (string) $worklist->extra_pages) {
                $worklist->update(['extra_pages' => $html]);
            }

            return $worklist;
        });
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Legacy storage keys the columns from 1.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return array<int, array{name: string, tick_on?: list<int>}>
     */
    private function columns(array $columns): array
    {
        $stored = [];

        foreach ($columns as $index => $column) {
            $stored[$index + 1] = ['name' => trim((string) ($column['name'] ?? ''))];
            $tickOn = $this->ids($column['tick_on'] ?? []);

            if ($tickOn !== []) {
                $stored[$index + 1]['tick_on'] = $tickOn;
            }
        }

        return $stored;
    }

    /**
     * Consecutive columns with the same group title share one header cell; the first cell covers the sample column.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return list<array{title: string, colspan: int}>
     */
    private function groups(array $columns): array
    {
        $titles = array_map(fn (array $column): string => trim((string) ($column['group'] ?? '')), $columns);

        if (array_filter($titles) === []) {
            return [];
        }

        $groups = [['title' => '', 'colspan' => 1]];

        foreach ($titles as $title) {
            $last = array_key_last($groups);

            if (($last !== 0 && $groups[$last]['title'] === $title) || ($last === 0 && $title === '')) {
                $groups[$last]['colspan']++;
            } else {
                $groups[] = ['title' => $title, 'colspan' => 1];
            }
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), fn (string $line): bool => $line !== ''));
    }

    private function extraPages(string $html): string
    {
        $text = trim(str_replace("\u{a0}", ' ', html_entity_decode(strip_tags($html))));

        if ($text === '' && ! preg_match('/<img\b/i', $html)) {
            return '';
        }

        // Quill's semantic HTML turns every space into &nbsp;, which would stop text from wrapping.
        return str_replace(['&nbsp;', "\u{a0}"], ' ', $html);
    }
}
