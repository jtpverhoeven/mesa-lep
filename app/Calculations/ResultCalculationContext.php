<?php

namespace App\Calculations;

use App\Confirmations\ConfirmationSnapshot;
use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;

final readonly class ResultCalculationContext
{
    public function __construct(
        public SampleAnalysis $analysis,
        public Assay $assay,
        public AssayProfile|RoamingAnalysis $settings,
        public ?ConfirmationSnapshot $confirmation = null,
    ) {}

    public function withConfirmation(?ConfirmationSnapshot $confirmation): self
    {
        return new self($this->analysis, $this->assay, $this->settings, $confirmation);
    }

    public function confirmationRatio(string|int|float $dilution, int|string $replicate = 0): ?float
    {
        if ($this->confirmation === null || ! $this->confirmation->isComplete()) {
            return null;
        }

        return $this->confirmation->ratioFor($dilution, $replicate);
    }

    public function applyConfirmationRatio(
        float $value,
        string|int|float $dilution,
        int|string $replicate = 0,
    ): float {
        $ratio = $this->confirmationRatio($dilution, $replicate);

        return $ratio === null ? $value : round($value * $ratio);
    }
}
