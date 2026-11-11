<?php

namespace App\Actions\SampleBuffers;

use Illuminate\Validation\ValidationException;
use Normalizer;

/**
 * Decodes a customer-supplied CSV into a header row and data rows.
 *
 * Customers export from Excel on different locales, so the file can be UTF-8
 * (with or without BOM), UTF-16 or Windows-1252 and use ; , tab or | as separator.
 */
class ReadImportCsv
{
    /** The legacy importer refused files with fewer header fields than the seven positional columns. */
    public const MIN_COLUMNS = 7;

    public const MAX_ROWS = 1000;

    public const SEPARATORS = ['semicolon' => ';', 'comma' => ',', 'tab' => "\t", 'pipe' => '|'];

    /**
     * @return array{
     *     separator: string,
     *     encoding: string,
     *     headers: list<string>,
     *     rows: list<array{line: int, cells: list<string>}>,
     *     warnings: list<string>
     * }
     */
    public function handle(string $contents, ?string $separatorName = null): array
    {
        [$text, $encoding] = $this->decode($contents);

        if (trim($text) === '') {
            $this->fail('Het bestand is leeg.');
        }

        $separator = array_key_exists((string) $separatorName, self::SEPARATORS) ? $separatorName : $this->detectSeparator($text);
        $records = $this->records($text, self::SEPARATORS[$separator]);
        $headers = array_map($this->clean(...), $records[0] ?? []);

        if (count($headers) < self::MIN_COLUMNS) {
            $this->fail('Het bestand kon niet worden gelezen. Controleer of het scheidingsteken juist is en of de koptekst minimaal '.self::MIN_COLUMNS.' kolommen bevat.');
        }

        $rows = [];
        $warnings = [];

        foreach (array_slice($records, 1, null, true) as $index => $record) {
            $cells = array_map($this->clean(...), $record);

            if (implode('', $cells) === '') {
                continue;
            }

            if (count($cells) > count($headers) && implode('', array_slice($cells, count($headers))) !== '') {
                $warnings[] = 'Rij '.($index + 1).' bevat meer waarden dan er kolommen in de koptekst staan; de extra waarden zijn genegeerd.';
            }

            $rows[] = [
                'line' => $index + 1,
                'cells' => array_slice(array_pad($cells, count($headers), ''), 0, count($headers)),
            ];
        }

        if (count($rows) > self::MAX_ROWS) {
            $this->fail('Het bestand bevat meer dan '.self::MAX_ROWS.' rijen. Splits het bestand en importeer het in delen.');
        }

        return compact('separator', 'encoding', 'headers', 'rows', 'warnings');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function decode(string $contents): array
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return [substr($contents, 3), 'UTF-8'];
        }

        if (str_starts_with($contents, "\xFF\xFE")) {
            return [mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE'), 'UTF-16LE'];
        }

        if (str_starts_with($contents, "\xFE\xFF")) {
            return [mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE'), 'UTF-16BE'];
        }

        if (mb_check_encoding($contents, 'UTF-8')) {
            return [$contents, 'UTF-8'];
        }

        // Excel's "CSV (comma delimited)" is Windows-1252, which also covers € and curly quotes.
        return [mb_convert_encoding($contents, 'UTF-8', 'Windows-1252'), 'Windows-1252'];
    }

    /**
     * The separator that splits the header row into the most fields; ties prefer the legacy default (;).
     */
    private function detectSeparator(string $text): string
    {
        $best = 'semicolon';
        $bestCount = 0;

        foreach (self::SEPARATORS as $name => $separator) {
            $count = count($this->records($text, $separator, limit: 1)[0] ?? []);

            if ($count > $bestCount) {
                [$best, $bestCount] = [$name, $count];
            }
        }

        return $best;
    }

    /**
     * @return list<list<string|null>>
     */
    private function records(string $text, string $separator, ?int $limit = null): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $text);
        rewind($stream);

        $records = [];

        while (($record = fgetcsv($stream, null, $separator, '"', '')) !== false) {
            $records[] = $record;

            if ($limit !== null && count($records) >= $limit) {
                break;
            }
        }

        fclose($stream);

        return $records;
    }

    private function clean(?string $cell): string
    {
        $cell = str_replace("\0", '', (string) $cell);
        $cell = Normalizer::normalize($cell, Normalizer::FORM_C) ?: $cell;

        return preg_replace('/^[\s\x{00A0}\x{200B}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{FEFF}]+$/u', '', $cell) ?? $cell;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => [$message]]);
    }
}
