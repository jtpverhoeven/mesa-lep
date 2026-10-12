<?php

namespace App\Actions\Printers;

use App\Models\Printer;

class UpdatePrinter
{
    /**
     * @param  array{name: string, adres: string, port?: int|string|null, type: int|string}  $data
     */
    public function handle(Printer $printer, array $data): Printer
    {
        $printer->update([...$data, 'port' => (int) ($data['port'] ?? 0)]);

        return $printer;
    }
}
