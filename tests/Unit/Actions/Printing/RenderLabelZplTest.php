<?php

namespace Tests\Unit\Actions\Printing;

use App\Actions\Printing\RenderLabelZpl;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RenderLabelZplTest extends TestCase
{
    public function test_legacy_global_placeholders_keep_their_formats_and_misspelled_tomorrow_key(): void
    {
        $clock = new DateTimeImmutable('2026-10-12 09:08:07+02:00');
        $template = '{date_today}|{yesterday}|{tommorow}|{current_time}|{current_year}|{current_user}';

        $zpl = (new RenderLabelZpl)->handle($template, [], $clock, 0, 'Laborant');

        $this->assertSame('12-10-2026|11-10-2026|13-10-2026|09:08|2026|Laborant', $zpl);
    }

    public function test_date_offset_changes_only_the_label_today_date(): void
    {
        $clock = new DateTimeImmutable('2026-10-12 09:08:07+02:00');

        $zpl = (new RenderLabelZpl)->handle('{date_today}|{yesterday}|{tommorow}', [], $clock, -2);

        $this->assertSame('10-10-2026|11-10-2026|13-10-2026', $zpl);
    }

    public function test_sample_client_method_assay_and_project_placeholders_expand_their_legacy_custom_fields(): void
    {
        $context = [
            'sample' => ['barcode' => 'M260001', 'custom_fields' => '{"matrix":"Water"}'],
            'client' => ['name' => 'Klant'],
            'method' => ['name' => 'Scheppen', 'fields' => ['volume' => '100 ml']],
            'assay' => ['name' => 'Kiemgetal', 'custom_fields' => '{"iso":"ISO 4833"}'],
            'project' => ['name' => 'Project', 'custom_fields' => ['location' => 'Hal 1']],
        ];
        $template = '^XA^FD{sample_barcode}|{sample_custom_matrix}|{client_name}|{method_name}|{method_custom_volume}|{assay_name}|{assay_custom_iso}|{project_name}|{project_custom_location}^FS^XZ';

        $zpl = (new RenderLabelZpl)->handle($template, $context, new DateTimeImmutable('2026-10-12'));

        $this->assertSame('^XA^FDM260001|Water|Klant|Scheppen|100 ml|Kiemgetal|ISO 4833|Project|Hal 1^FS^XZ', $zpl);
    }

    public function test_replacement_values_are_literal_and_unknown_placeholders_remain_unchanged(): void
    {
        $context = ['sample' => ['description' => '$1\\path {client_name}', 'custom_fields' => ['a.b' => '$2']]];

        $zpl = (new RenderLabelZpl)->handle('{sample_description}|{sample_custom_a.b}|{unknown}', $context, new DateTimeImmutable('2026-10-12'));

        $this->assertSame('$1\\path {client_name}|$2|{unknown}', $zpl);
    }

    public function test_timestamps_tht_and_custom_dates_keep_legacy_storage_and_conversion_rules(): void
    {
        $context = [
            'sample' => [
                'date_registered' => 1735689600, 'sample_innoculated' => 1735689600, 'tht_date' => '2026-11-04',
                'custom_fields' => ['collected' => 1735689600, 'string_date' => '1735689600'],
            ],
            'sample_field_types' => ['collected' => 'date', 'string_date' => 'date'],
        ];

        $zpl = (new RenderLabelZpl)->handle('{sample_date_registered}|{sample_sample_innoculated}|{sample_tht_date}|{sample_custom_collected}|{sample_custom_string_date}', $context, new DateTimeImmutable('2026-10-12T00:00:00+00:00'));

        $this->assertSame('1735689600|01-01-2025|04-11-2026|01-01-2025|1735689600', $zpl);
    }

    #[DataProvider('emptyInoculationDates')]
    public function test_unset_inoculation_dates_render_as_empty_text(mixed $value): void
    {
        $zpl = (new RenderLabelZpl)->handle('^FD{sample_sample_innoculated}^FS', ['sample' => ['sample_innoculated' => $value]], new DateTimeImmutable('2026-10-12'));

        $this->assertSame('^FD^FS', $zpl);
    }

    public function test_degree_symbols_use_the_legacy_zebra_escape(): void
    {
        $zpl = (new RenderLabelZpl)->handle("^FD37\u{00B0}C^FS", [], new DateTimeImmutable('2026-10-12'));

        $this->assertSame('^FD37\\F8C^FS', $zpl);
    }

    public function test_invalid_json_and_non_scalar_values_do_not_trigger_warnings_or_accidental_array_text(): void
    {
        $context = ['sample' => ['description' => null, 'custom_fields' => '{broken'], 'method' => ['fields' => ['array' => ['bad']]]];

        $zpl = (new RenderLabelZpl)->handle('{sample_description}|{method_custom_array}|{sample_custom_missing}', $context, new DateTimeImmutable('2026-10-12'));

        $this->assertSame('||{sample_custom_missing}', $zpl);
    }

    public static function emptyInoculationDates(): array
    {
        return ['null' => [null], 'empty' => [''], 'zero' => [0], 'negative' => [-1], 'invalid' => ['not a timestamp']];
    }
}
