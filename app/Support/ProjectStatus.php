<?php

namespace App\Support;

/**
 * Project status labels with the tone of the legacy Bootstrap label flair they were shown with.
 * Tones are semantic; the colours themselves are theme tokens in resources/css/theme.css.
 */
enum ProjectStatus
{
    case Received;
    case Running;
    case RunningBlocked;
    case Completed;
    case Blocked;
    case Authorized;
    case Reported;

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Ontvangen',
            self::Running => 'Lopend',
            self::RunningBlocked => 'Lopend (Geblokkeerd)',
            self::Completed => 'Afgerond',
            self::Blocked => 'Geblokkeerd',
            self::Authorized => 'Geautoriseerd',
            self::Reported => 'PDF gegenereerd',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Received => 'inverse',
            self::Running => 'info',
            self::RunningBlocked, self::Completed, self::Blocked => 'warning',
            self::Authorized => 'success',
            self::Reported => 'default',
        };
    }

    /** @return array{status: string, status_tone: string} */
    public function toArray(): array
    {
        return ['status' => $this->label(), 'status_tone' => $this->tone()];
    }
}
