<?php

namespace App\Actions\Reports;

use RuntimeException;

class ReportTransactionUnavailableException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('Error: Kon rapport-transactie database rij niet vinden.');
    }

    public static function missingPdf(): self
    {
        return new self('Error: Kon rapport PDF niet vinden in reportStorage');
    }
}
