<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * A project's inoculation span as shown by legacy samplesController::getProjectInnoculationDate.
 */
final readonly class ProjectInoculation
{
    public function __construct(public ?int $first, public ?int $last) {}

    /**
     * @param  iterable<mixed>  $values  Raw samples.sample_innoculated values; empty and non-positive values are ignored.
     */
    public static function fromValues(iterable $values): self
    {
        $timestamps = collect($values)
            ->filter(fn (mixed $value): bool => is_numeric($value) && (int) $value > 0)
            ->map(fn (mixed $value): int => (int) $value);

        return new self($timestamps->min(), $timestamps->max());
    }

    public function isSplit(): bool
    {
        return $this->first !== null && $this->last !== null
            && ! $this->firstDate()->isSameDay($this->lastDate());
    }

    public function date(): string
    {
        if ($this->isSplit()) {
            return $this->firstDate()->format('d-m-Y H:i')."\n".$this->lastDate()->format('d-m-Y H:i');
        }

        return $this->firstDate()?->format('d-m-Y') ?? 'Nog niet ingezet';
    }

    public function time(): string
    {
        return $this->isSplit() ? 'Tweeledig' : ($this->firstDate()?->format('H:i') ?? '');
    }

    private function firstDate(): ?Carbon
    {
        return $this->first !== null ? Carbon::createFromTimestamp($this->first, 'Europe/Amsterdam') : null;
    }

    private function lastDate(): ?Carbon
    {
        return $this->last !== null ? Carbon::createFromTimestamp($this->last, 'Europe/Amsterdam') : null;
    }
}
