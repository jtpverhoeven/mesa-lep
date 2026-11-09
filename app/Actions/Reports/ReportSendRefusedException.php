<?php

namespace App\Actions\Reports;

use RuntimeException;

class ReportSendRefusedException extends RuntimeException
{
    public static function unauthorized(): self
    {
        return new self('Verzenden is niet toegestaan: het project was niet geautoriseerd toen dit rapport werd gemaakt.');
    }
}
