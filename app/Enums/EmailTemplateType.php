<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    case Reports = 'reports';
    case ReportWithFiles = 'report_with_files';
    case ReportsWithFiles = 'reports_with_files';
    case ForFiles = 'for_files';

    public function defaultColumn(): string
    {
        return match ($this) {
            self::Reports => 'defaultmail',
            self::ReportWithFiles, self::ReportsWithFiles => 'defaultmail_with_files',
            self::ForFiles => 'defaultmail_for_files',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Reports => 'Rapportages',
            self::ReportWithFiles, self::ReportsWithFiles => 'Rapportages met bestanden',
            self::ForFiles => 'Bestand emails',
        };
    }

    public function columnHeader(): string
    {
        return match ($this) {
            self::Reports => 'Standaard rapport email',
            self::ReportWithFiles, self::ReportsWithFiles => 'Standaard rapport email (met bestanden)',
            self::ForFiles => 'Standaard email voor bestanden',
        };
    }

    /** @return list<array{value: string, column: string, label: string, header: string}> */
    public static function directoryTypes(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'column' => $type->defaultColumn(),
            'label' => $type->label(),
            'header' => $type->columnHeader(),
        ], [self::Reports, self::ReportWithFiles, self::ForFiles]);
    }
}
