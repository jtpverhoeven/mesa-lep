<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id', 'date', 'data', 'is_complete',
])]
class AssuranceForm extends Model
{
    protected $table = 'assuranceforms';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_complete' => 'boolean',
        ];
    }

    /** Incomplete forms whose cooldown (BORG_TIME_BEFORE_CHECK) has passed; the date column is a unix timestamp string. */
    public function scopeAwaitingData(Builder $query, int $dateBefore): void
    {
        $query->where('is_complete', false)->whereRaw('CAST("date" AS BIGINT) < ?', [$dateBefore]);
    }

    /** @return array<string|int, mixed> */
    public function decodedData(): array
    {
        $data = json_decode((string) $this->data, true);

        return is_array($data) ? $data : [];
    }

    /** @param array<string|int, mixed> $data */
    public function setDecodedData(array $data): void
    {
        $this->data = json_encode($data, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }
}
