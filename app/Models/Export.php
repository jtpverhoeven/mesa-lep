<?php

namespace App\Models;

use Database\Factories\ExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
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

    public function scopeInTransaction(Builder $query, int $transaction): Builder
    {
        return $query
            ->where('transaction', $transaction)
            ->with(['projectRecord.client', 'clientRecord'])
            ->orderBy('id');
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

    public function pdfExists(): bool
    {
        return Storage::disk('local')->exists($this->storagePath());
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

    public function reportName(bool $withClient = false): string
    {
        $name = $this->projectRecord?->reference.'.'.$this->revision.'.'.$this->print_version;

        if ($this->naming_strategy === 'description') {
            $name .= ' - '.$this->naming_description;
        }

        if ($withClient) {
            $name = $this->clientRecord?->name.' - MAZ-L '.$name;
        }

        return str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '', Str::substr($name, 0, 200));
    }

    public function isEnglish(): bool
    {
        return str_ends_with((string) $this->template, 'En');
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
