<?php

namespace App\Actions\SampleBuffers;

use App\Models\Client;
use App\Models\ProductGroup;
use App\Support\Cvars;

/**
 * Turns a customer CSV into an editable preview of sample buffer rows.
 *
 * The CSV layout is positional, like in the legacy importer: sampling date, project name, sample name,
 * sample details, THT, THT date, analyses and other directions. Every further column is metadata, except
 * the special "_ProductGroep" column. Nothing is stored here; ImportSampleBuffers commits the reviewed rows.
 */
class PreviewSampleBufferImport
{
    private const POSITIONAL_COLUMNS = 8;

    private const PRODUCT_GROUP_COLUMN = '_productgroep';

    private const THT_YES_VALUES = ['JA', 'J', 'Y', 'YES', 'TRUE', 'WAAR', '1'];

    private const MONTH_PREFIXES = [
        'jan' => 1, 'feb' => 2, 'maa' => 3, 'mar' => 3, 'mrt' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    private const MAX_LISTED_SKIPPED_LINES = 10;

    public function __construct(
        private ReadImportCsv $reader,
        private ResolveImportProductGroup $resolveProductGroup,
        private Cvars $cvars,
    ) {}

    /**
     * @return array{
     *     client: array{id: int, name: string},
     *     separator: string,
     *     encoding: string,
     *     uses_assay_column: bool,
     *     product_groups: list<array{id: int, name: string, active: bool}>,
     *     meta_columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     warnings: list<string>
     * }
     */
    public function handle(Client $client, string $contents, ?string $separatorName = null): array
    {
        $csv = $this->reader->handle($contents, $separatorName);
        [$metaColumns, $metaIndexes, $productGroupIndex, $warnings] = $this->columns($csv['headers']);
        $warnings = [...$csv['warnings'], ...$warnings];

        $groups = $client->productGroups()->orderBy('id')->get();
        $defaultGroup = $groups->first(fn (ProductGroup $group): bool => (int) $group->default === 1);
        $usesAssayColumn = $this->usesAssayColumn($client);

        $rows = [];
        $skippedLines = [];

        foreach ($csv['rows'] as $csvRow) {
            [$samplingDate, $projectName, $sampleName, $sampleDetails, $tht, $thtDate, $analyses, $directions] = array_pad($csvRow['cells'], self::POSITIONAL_COLUMNS, '');

            if ($projectName === '' && $sampleName === '' && $sampleDetails === '') {
                $skippedLines[] = $csvRow['line'];

                continue;
            }

            $isTht = in_array(mb_strtoupper($tht), self::THT_YES_VALUES, true);
            $productGroupText = $usesAssayColumn ? $analyses : ($productGroupIndex === null ? '' : $csvRow['cells'][$productGroupIndex]);
            $matchedGroup = $this->resolveProductGroup->handle($groups, $productGroupText);

            $rows[] = [
                'line' => $csvRow['line'],
                'sampling_date' => $this->isoDate($samplingDate) ?? '',
                'sampling_date_source' => $samplingDate,
                'project_name' => $projectName,
                'sample_name' => $sampleName,
                'sample_details' => $sampleDetails,
                'tht' => $isTht,
                'tht_date' => $isTht ? ($this->isoDate($thtDate) ?? '') : '',
                'tht_date_source' => $isTht ? $thtDate : '',
                'analyses_selected' => $analyses,
                'misc_directions' => $directions,
                'product_group' => ($matchedGroup ?? $defaultGroup)?->portal_id,
                'product_group_text' => $productGroupText,
                'product_group_alert' => $matchedGroup === null && ($usesAssayColumn || $productGroupText !== ''),
                'meta' => array_map(fn (int $index): string => $csvRow['cells'][$index], $metaIndexes),
            ];
        }

        if ($skippedLines !== []) {
            $warnings[] = $this->skippedWarning($skippedLines);
        }

        return [
            'client' => ['id' => $client->id, 'name' => $client->name],
            'separator' => $csv['separator'],
            'encoding' => $csv['encoding'],
            'uses_assay_column' => $usesAssayColumn,
            'product_groups' => $groups->map(fn (ProductGroup $group): array => [
                'id' => (int) $group->portal_id,
                'name' => $group->name,
                'active' => $group->visible === null || (int) $group->visible !== 0,
            ])->values()->all(),
            'meta_columns' => $metaColumns,
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    /**
     * Legacy clients can supply the product group in the analyses column (cvar CLIENT_USES_ASSAY_COLUMN_IMPORT).
     */
    private function usesAssayColumn(Client $client): bool
    {
        return in_array($client->id, array_map('intval', $this->cvars->json('CLIENT_USES_ASSAY_COLUMN_IMPORT')), true);
    }

    /**
     * @param  list<string>  $headers
     * @return array{0: list<string>, 1: list<int>, 2: int|null, 3: list<string>}
     */
    private function columns(array $headers): array
    {
        $metaIndexByName = [];
        $productGroupIndex = null;
        $warnings = [];

        foreach (array_slice($headers, self::POSITIONAL_COLUMNS, null, true) as $index => $header) {
            if ($header === '') {
                continue;
            }

            if (mb_strtolower($header) === self::PRODUCT_GROUP_COLUMN) {
                $productGroupIndex = $index;

                continue;
            }

            if (isset($metaIndexByName[$header])) {
                $warnings[] = 'De kolom "'.$header.'" komt meerdere keren voor; alleen de laatste kolom wordt gebruikt.';
            }

            $metaIndexByName[$header] = $index;
        }

        return [array_map('strval', array_keys($metaIndexByName)), array_values($metaIndexByName), $productGroupIndex, $warnings];
    }

    /**
     * Dutch customers write day-month-year, also with slashes or month names ("20 sep 2026", "5 maart 2026").
     */
    private function isoDate(string $value): ?string
    {
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})(?:[ T].*)?$/', $value, $match)) {
            [$day, $month, $year] = [(int) $match[1], (int) $match[2], (int) $match[3]];
        } elseif (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})(?:[ T].*)?$/', $value, $match)) {
            [$year, $month, $day] = [(int) $match[1], (int) $match[2], (int) $match[3]];
        } elseif (preg_match('/^(\d{1,2})[ .-]+(\p{L}{3,})\.?[ .-]+(\d{2}|\d{4})$/u', $value, $match)
            && isset(self::MONTH_PREFIXES[mb_strtolower(mb_substr($match[2], 0, 3))])) {
            [$day, $month, $year] = [(int) $match[1], self::MONTH_PREFIXES[mb_strtolower(mb_substr($match[2], 0, 3))], (int) $match[3]];
        } else {
            return null;
        }

        $year = $year < 100 ? 2000 + $year : $year;

        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    /**
     * @param  list<int>  $lines
     */
    private function skippedWarning(array $lines): string
    {
        $listed = implode(', ', array_slice($lines, 0, self::MAX_LISTED_SKIPPED_LINES));
        $more = count($lines) > self::MAX_LISTED_SKIPPED_LINES ? ' en meer' : '';

        return count($lines).' rij(en) zonder projectnaam, monsternaam en details zijn overgeslagen (rij '.$listed.$more.').';
    }
}
