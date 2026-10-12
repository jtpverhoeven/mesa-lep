<?php

namespace Tests\Feature;

use App\Support\LaboratoryCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LaboratoryCalendarTest extends TestCase
{
    #[DataProvider('specialDates')]
    public function test_read_dates_follow_the_legacy_dutch_laboratory_calendar(string $date, string $type, string $readDate): void
    {
        $calendar = app(LaboratoryCalendar::class);

        $this->assertSame($type, $calendar->getTypeOfSpecialDate($date));
        $this->assertSame($type === 'normal', $calendar->isWorkingDay($date));
        $this->assertSame($readDate.' 00:00:00', $calendar->getReadDate($date)->format('d-m-Y H:i:s'));
    }

    public static function specialDates(): array
    {
        return [
            'ordinary weekday' => ['09-10-2026', 'normal', '09-10-2026'],
            'saturday' => ['10-10-2026', 'saturday', '12-10-2026'],
            'sunday' => ['11-10-2026', 'sunday', '12-10-2026'],
            'new year' => ['01-01-2026', 'holiday', '02-01-2026'],
            'easter sunday' => ['05-04-2026', 'holiday', '07-04-2026'],
            'easter monday' => ['06-04-2026', 'holiday', '07-04-2026'],
            'ascension' => ['14-05-2026', 'holiday', '15-05-2026'],
            'pentecost sunday' => ['24-05-2026', 'holiday', '26-05-2026'],
            'pentecost monday' => ['25-05-2026', 'holiday', '26-05-2026'],
            'kings day' => ['27-04-2026', 'holiday', '28-04-2026'],
            'kings day shifted to saturday' => ['26-04-2025', 'holiday', '28-04-2025'],
            'shifted kings day leaves sunday' => ['27-04-2025', 'sunday', '28-04-2025'],
            'liberation day in fifth year' => ['05-05-2025', 'holiday', '06-05-2025'],
            'liberation day otherwise ordinary' => ['05-05-2026', 'normal', '05-05-2026'],
            'christmas followed by weekend' => ['25-12-2026', 'holiday', '28-12-2026'],
            'boxing day' => ['26-12-2026', 'holiday', '28-12-2026'],
            'year boundary and new year' => ['31-12-2022', 'saturday', '02-01-2023'],
        ];
    }

    public function test_read_date_does_not_mutate_the_input_date(): void
    {
        $date = Carbon::parse('2026-12-25 14:30:00', 'Europe/Amsterdam');

        $readDate = app(LaboratoryCalendar::class)->getReadDate($date);

        $this->assertSame('2026-12-28 00:00:00', $readDate->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Amsterdam', $readDate->timezoneName);
        $this->assertSame('2026-12-25 14:30:00', $date->format('Y-m-d H:i:s'));
    }

    public function test_timestamp_read_dates_keep_the_legacy_utc_date_and_application_midnight(): void
    {
        config(['app.timezone' => 'Europe/Amsterdam']);
        $timestamp = CarbonImmutable::parse('2026-10-09 23:30:00', 'UTC')->timestamp;

        $readDate = app(LaboratoryCalendar::class)->getReadDateFromTimestamp($timestamp);

        $this->assertSame(CarbonImmutable::parse('2026-10-09 00:00:00', 'Europe/Amsterdam')->timestamp, $readDate);
    }
}
