<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
