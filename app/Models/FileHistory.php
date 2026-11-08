<?php

namespace App\Models;

use Database\Factories\FileHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sample_id', 'samplefile_id', 'hash_name', 'sent_by', 'sent_to', 'sent_method', 'transaction_id',
])]
class FileHistory extends Model
{
    /** @use HasFactory<FileHistoryFactory> */
    use HasFactory;

    protected $table = 'filehistories';

    public $timestamps = false;

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample_id');
    }

    public function sampleFile(): BelongsTo
    {
        return $this->belongsTo(SampleFile::class, 'samplefile_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    protected function casts(): array
    {
        return [
            'sent_on' => 'datetime',
            'sent_to' => 'array',
        ];
    }
}
