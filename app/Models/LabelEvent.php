<?php

namespace App\Models;

use Database\Factories\LabelEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event', 'event_group', 'event_data', 'print_label', 'print_to', 'copies'])]
class LabelEvent extends Model
{
    /** @use HasFactory<LabelEventFactory> */
    use HasFactory;

    public const MAX_COPIES = 9999;

    public const GROUPS = [
        1 => 'Monsterregistratie',
        2 => 'Analyses toevoegen',
        3 => 'THT-goedkeuring',
    ];

    public const CONDITIONS = [
        1 => [
            'sField' => 'Monsterveld',
            'sClient' => 'Opdrachtgever',
            'mMethod' => 'Monsternameprocedure',
            'mType' => 'Monstertype',
        ],
        2 => [
            'assayIs' => 'Analyse',
            'assayTypeIs' => 'Analysetype',
        ],
        3 => [],
    ];

    protected $table = 'labelevents';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'event_group' => 'integer',
            'print_label' => 'integer',
            'print_to' => 'integer',
            'copies' => 'integer',
        ];
    }

    public function labelDesign(): BelongsTo
    {
        return $this->belongsTo(LabelDesign::class, 'print_label');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'print_to');
    }
}
