<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class LaboratoryCalendar
{
    public function isWeekend(DateTimeInterface|string $date): bool
    {
        return CarbonImmutable::parse($date, config('app.timezone'))->isWeekend();
    }

    public function isHoliday(DateTimeInterface|string $date): bool
    {
        $day = CarbonImmutable::parse($date, config('app.timezone'));
        $easter = CarbonImmutable::create($day->year, 3, 21, 0, 0, 0, 'Europe/Amsterdam')->addDays(easter_days($day->year));
        $kingsDay = CarbonImmutable::create($day->year, 4, 27);
        if ($kingsDay->isSunday()) {
            $kingsDay = $kingsDay->subDay();
        }

        $holidays = ['01-01', '12-25', '12-26', $kingsDay->format('m-d')];
        foreach ([0, 1, 39, 49, 50] as $offset) {
            $holidays[] = $easter->addDays($offset)->format('m-d');
        }

        return in_array($day->format('m-d'), $holidays, true)
            || ($day->year % 5 === 0 && $day->format('m-d') === '05-05');
    }

    public function isWorkingDay(DateTimeInterface|string $date): bool
    {
        return ! $this->isWeekend($date) && ! $this->isHoliday($date);
    }

    public function getTypeOfSpecialDate(DateTimeInterface|string $date): string
    {
        $day = CarbonImmutable::parse($date, config('app.timezone'));

        return match (true) {
            $this->isHoliday($day) => 'holiday',
            $day->isSaturday() => 'saturday',
            $day->isSunday() => 'sunday',
            default => 'normal',
        };
    }

    public function getReadDate(DateTimeInterface|string $date): CarbonImmutable
    {
        $day = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
        while (! $this->isWorkingDay($day)) {
            $day = $day->addDay();
        }

        return $day;
    }

    public function getReadDateFromTimestamp(int $timestamp): int
    {
        $date = CarbonImmutable::createFromTimestampUTC($timestamp)->format('Y-m-d');

        return $this->getReadDate($date)->timestamp;
    }
}
