<?php

namespace App\Models;

use Database\Factories\LabelDesignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'zpl', 'default_sample', 'default_analysis', 'default_printer'])]
class LabelDesign extends Model
{
    /** @use HasFactory<LabelDesignFactory> */
    use HasFactory;

    protected $table = 'labeldesigns';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'default_sample' => 'boolean',
            'default_analysis' => 'boolean',
            'default_printer' => 'integer',
        ];
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'default_printer');
    }
}
