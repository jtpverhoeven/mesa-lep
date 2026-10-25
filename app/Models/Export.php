<?php

namespace App\Models;

use Database\Factories\ExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use UnexpectedValueException;

#[Fillable([
    'transaction', 'client', 'revision', 'project', 'print_version', 'type',
    'naming_strategy', 'naming_description', 'template', 'hash', 'date',
    'report_reference', 'true_reference', 'temporary', 'was_authorized', 'sample_id', 'sample_id_scope',
])]
class Export extends Model
{
    /** @use HasFactory<ExportFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['sample_id_scope' => 'array'];
    }

    public function projectRecord(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project');
    }

    public function clientRecord(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client');
    }

    public function sampleRecord(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample_id');
    }

    public function storagePath(): string
    {
        if (preg_match('/\A[0-9a-f]{64}\z/', $this->hash) !== 1) {
            throw new UnexpectedValueException("Report [{$this->id}] has an invalid storage hash.");
        }

        return 'reportStorage/'.$this->hash.'.pdf';
    }

    public function filename(): string
    {
        $reference = $this->true_reference ?? $this->projectRecord?->reference;
        $name = $reference.'.'.$this->revision.'.'.$this->print_version;

        if ($this->naming_strategy === 'description') {
            $name .= ' - '.$this->naming_description;
        }

        return self::safeFilename($this->clientRecord?->name.' - MAZ-L '.$name).'.pdf';
    }

    public static function safeFilename(string $name): string
    {
        return Str::substr(str_replace(
            ['<', '>', ':', '"', '/', '\\', '|', '?', '*', "\r", "\n", "\0"],
            '',
            $name,
        ), 0, 200);
    }
}
