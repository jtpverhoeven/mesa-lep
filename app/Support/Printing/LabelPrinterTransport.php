<?php

namespace App\Support\Printing;

use App\Models\Printer;

interface LabelPrinterTransport
{
    public function send(Printer $printer, string $zpl): void;
}
