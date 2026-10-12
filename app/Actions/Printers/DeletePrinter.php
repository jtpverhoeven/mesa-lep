<?php

namespace App\Actions\Printers;

use App\Models\Printer;

class DeletePrinter
{
    public function handle(Printer $printer): void
    {
        $printer->delete();
    }
}
