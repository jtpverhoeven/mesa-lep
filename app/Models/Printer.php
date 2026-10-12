<?php

namespace App\Models;

use Database\Factories\PrinterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'adres', 'port', 'type'])]
class Printer extends Model
{
    /** @use HasFactory<PrinterFactory> */
    use HasFactory;

    public const TYPES = [
        0 => 'Gedeelde printer',
        1 => 'Print server',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'type' => 'integer',
        ];
    }
}
