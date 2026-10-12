<?php

namespace App\Actions\Printers;

use App\Models\Printer;

class CreatePrinter
{
    /**
     * @param  array{name: string, adres: string, port?: int|string|null, type: int|string}  $data
     */
    public function handle(array $data): Printer
    {
        return Printer::create([...$data, 'port' => (int) ($data['port'] ?? 0)]);
    }
}
