<?php

namespace Tests\Feature\Actions\SampleBuffers;

use App\Actions\SampleBuffers\ReadImportCsv;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReadImportCsvTest extends TestCase
{
    private const HEADER = ['Datum', 'Project', 'Monster', 'Details', 'THT', 'THT datum', 'Analyses', 'Overige'];

    /** @return array<string, array{string, string}> */
    public static function separators(): array
    {
        return [
            'semicolon' => [';', 'semicolon'],
            'comma' => [',', 'comma'],
            'tab' => ["\t", 'tab'],
            'pipe' => ['|', 'pipe'],
        ];
    }

    #[DataProvider('separators')]
    public function test_detects_the_separator_from_the_header_row(string $separator, string $name): void
    {
        $result = app(ReadImportCsv::class)->handle($this->csv([['20-09-2026', 'Project', 'Monster', 'Detail', 'nee', '', 'Legionella', '']], $separator));

        $this->assertSame($name, $result['separator']);
        $this->assertSame(self::HEADER, $result['headers']);
        $this->assertSame(['20-09-2026', 'Project', 'Monster', 'Detail', 'nee', '', 'Legionella', ''], $result['rows'][0]['cells']);
    }

    public function test_a_chosen_separator_overrides_detection(): void
    {
        $this->expectException(ValidationException::class);

        app(ReadImportCsv::class)->handle($this->csv([['20-09-2026', 'Project', 'Monster', 'Detail', 'nee', '', 'Legionella', '']]), 'comma');
    }

    public function test_decodes_windows_1252_exports_with_dutch_diacritics(): void
    {
        $contents = mb_convert_encoding($this->csv([['20-09-2026', 'Café Zoë', 'Prüfung ïnkt', '€ 5,- “goed”', 'nee', '', '', '']]), 'Windows-1252', 'UTF-8');

        $result = app(ReadImportCsv::class)->handle($contents);

        $this->assertSame('Windows-1252', $result['encoding']);
        $this->assertSame(['20-09-2026', 'Café Zoë', 'Prüfung ïnkt', '€ 5,- “goed”'], array_slice($result['rows'][0]['cells'], 0, 4));
    }

    public function test_decodes_utf8_with_bom_without_leaking_the_bom_into_the_first_header(): void
    {
        $result = app(ReadImportCsv::class)->handle("\xEF\xBB\xBF".$this->csv([['20-09-2026', 'Zoë', 'Monster', 'Detail', 'nee', '', '', '']]));

        $this->assertSame('UTF-8', $result['encoding']);
        $this->assertSame('Datum', $result['headers'][0]);
        $this->assertSame('Zoë', $result['rows'][0]['cells'][1]);
    }

    public function test_decodes_utf16_exports(): void
    {
        $contents = "\xFF\xFE".mb_convert_encoding($this->csv([['20-09-2026', 'Zoë', 'Monster', 'Detail', 'nee', '', '', '']], "\t"), 'UTF-16LE', 'UTF-8');

        $result = app(ReadImportCsv::class)->handle($contents);

        $this->assertSame('UTF-16LE', $result['encoding']);
        $this->assertSame('tab', $result['separator']);
        $this->assertSame('Zoë', $result['rows'][0]['cells'][1]);
    }

    public function test_composes_decomposed_diacritics_so_they_compare_equal_to_stored_names(): void
    {
        $decomposed = "Cafe\u{0301} Zoe\u{0308}";

        $result = app(ReadImportCsv::class)->handle($this->csv([['20-09-2026', $decomposed, 'Monster', 'Detail', 'nee', '', '', '']]));

        $this->assertSame("Caf\u{00E9} Zo\u{00EB}", $result['rows'][0]['cells'][1]);
    }

    public function test_keeps_separators_and_line_breaks_inside_quoted_cells(): void
    {
        $contents = implode(';', self::HEADER)."\n20-09-2026;\"Project; A\";\"Regel 1\nRegel 2\";Detail;nee;;;\n";

        $result = app(ReadImportCsv::class)->handle($contents);

        $this->assertSame('Project; A', $result['rows'][0]['cells'][1]);
        $this->assertSame("Regel 1\nRegel 2", $result['rows'][0]['cells'][2]);
    }

    public function test_pads_short_rows_and_ignores_extra_values_with_a_warning(): void
    {
        $contents = implode(';', self::HEADER)."\n20-09-2026;Kort;Monster\n20-09-2026;Lang;Monster;Detail;nee;;;;extra waarde\n";

        $result = app(ReadImportCsv::class)->handle($contents);

        $this->assertSame(['20-09-2026', 'Kort', 'Monster', '', '', '', '', ''], $result['rows'][0]['cells']);
        $this->assertCount(8, $result['rows'][1]['cells']);
        $this->assertSame(['Rij 3 bevat meer waarden dan er kolommen in de koptekst staan; de extra waarden zijn genegeerd.'], $result['warnings']);
    }

    public function test_skips_blank_lines_but_keeps_the_spreadsheet_line_numbers(): void
    {
        $contents = implode(';', self::HEADER)."\n;;;;;;;\n\n20-09-2026;Project;Monster;;;;;\n";

        $result = app(ReadImportCsv::class)->handle($contents);

        $this->assertCount(1, $result['rows']);
        $this->assertSame(4, $result['rows'][0]['line']);
    }

    public function test_rejects_a_file_without_content(): void
    {
        try {
            app(ReadImportCsv::class)->handle("\n  \n");
            $this->fail('Een leeg bestand had afgewezen moeten worden.');
        } catch (ValidationException $exception) {
            $this->assertSame(['file' => ['Het bestand is leeg.']], $exception->errors());
        }
    }

    public function test_rejects_a_header_with_fewer_than_seven_columns(): void
    {
        $this->expectException(ValidationException::class);

        app(ReadImportCsv::class)->handle("Datum;Project;Monster\n20-09-2026;Project;Monster\n");
    }

    public function test_rejects_files_with_more_rows_than_the_import_limit(): void
    {
        $rows = array_fill(0, ReadImportCsv::MAX_ROWS + 1, ['20-09-2026', 'Project', 'Monster', 'Detail', 'nee', '', '', '']);

        try {
            app(ReadImportCsv::class)->handle($this->csv($rows));
            $this->fail('Te veel rijen hadden afgewezen moeten worden.');
        } catch (ValidationException $exception) {
            $this->assertSame(['file' => ['Het bestand bevat meer dan 1000 rijen. Splits het bestand en importeer het in delen.']], $exception->errors());
        }
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function csv(array $rows, string $separator = ';'): string
    {
        return implode("\n", array_map(fn (array $row): string => implode($separator, $row), [self::HEADER, ...$rows]))."\n";
    }
}
