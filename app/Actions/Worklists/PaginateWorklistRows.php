<?php

namespace App\Actions\Worklists;

use App\Models\Worklist;

class PaginateWorklistRows
{
    /**
     * Fills fixed-size pages; the worklist specific rows close the last column of every page.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<list<list<array{kind: string, row?: array<string, mixed>, text?: string}>>> Pages of columns of lines.
     */
    public function handle(Worklist $worklist, array $rows): array
    {
        $custom = $worklist->extra_rows ?? [];
        $columnCount = $worklist->no_columns === 2 ? 2 : 1;
        $slots = [];

        for ($column = 0; $column < $columnCount; $column++) {
            $slots[] = max(1, Worklist::ROWS_PER_COLUMN - ($column === $columnCount - 1 ? count($custom) : 0));
        }

        $pages = [];

        foreach (array_chunk($rows, array_sum($slots)) as $chunk) {
            $page = [];
            $offset = 0;

            foreach ($slots as $column => $count) {
                $lines = [];

                for ($index = 0; $index < $count; $index++) {
                    $row = $chunk[$offset + $index] ?? null;
                    $lines[] = $row === null ? ['kind' => 'blank'] : ['kind' => 'sample', 'row' => $row];
                }

                if ($column === $columnCount - 1) {
                    foreach ($custom as $text) {
                        $lines[] = ['kind' => 'custom', 'text' => (string) $text];
                    }
                }

                $offset += $count;
                $page[] = $lines;
            }

            $pages[] = $page;
        }

        return $pages;
    }
}
