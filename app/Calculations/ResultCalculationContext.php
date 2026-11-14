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
        public array $metaResults = [],
        public ?SampleAnalysis $metaParent = null,
    ) {}

    public function withConfirmation(?ConfirmationSnapshot $confirmation): self
    {
        return new self($this->analysis, $this->assay, $this->settings, $confirmation, $this->metaResults, $this->metaParent);
    }

    /**
     * The legacy `$name=value` declarations in the assay script.
     *
     * @return array<string, string>
     */
    public function scriptVariables(): array
    {
        preg_match_all('/\$(.*)/', (string) $this->assay->script, $matches);
        $variables = [];

        foreach ($matches[1] as $declaration) {
            $parts = explode('=', $declaration);

            if (count($parts) >= 2) {
                $variables[$parts[0]] = trim($parts[1]);
            }
        }

        return $variables;
    }

    /** The report label for a calculator field, overridden by the assay's `resultin` custom field. */
    public function reportLabel(string $field): string
    {
        $customFields = json_decode($this->assay->custom_fields ?? '{}', true);

        return ($customFields['resultin'] ?? '') ?: $field;
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
