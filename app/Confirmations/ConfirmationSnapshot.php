<?php

namespace App\Confirmations;

use App\Models\Assay;
use App\Models\Confirmation;
use App\Models\SampleAnalysis;

final readonly class ConfirmationSnapshot
{
    public function __construct(
        public string $status,
        public int $decision,
        public string $mode,
        public bool $ready,
        public array $ratios,
        public array $enabledMedia,
        public array $metadata,
        public ?int $targetAnalysisId,
    ) {}

    public static function from(
        SampleAnalysis $analysis,
        Assay $assay,
        ?Confirmation $confirmation,
        string $status,
        ?int $targetAnalysisId = null,
    ): self {
        $metadata = is_array($confirmation?->metadata) ? $confirmation->metadata : [];
        $ratios = [];

        foreach ($metadata as $df => $replicates) {
            foreach ((array) $replicates as $rep => $scope) {
                if (isset($scope['ratio']) && is_numeric($scope['ratio'])) {
                    $ratios[(string) $df][(string) $rep] = (float) $scope['ratio'];
                }
            }
        }

        $enabledMedia = [];
        foreach ((array) $confirmation?->in_use as $replicates) {
            foreach ((array) $replicates as $media) {
                foreach ((array) $media as $mediaId => $active) {
                    if ($active) {
                        $enabledMedia[] = (int) $mediaId;
                    }
                }
            }
        }

        return new self(
            $status,
            (int) $analysis->conf_requested,
            (int) $assay->confirmation_type === 0 ? 'global' : 'per_plate',
            (bool) ($confirmation?->isReady ?? false),
            $ratios,
            array_values(array_unique($enabledMedia)),
            $metadata,
            $targetAnalysisId ?? (int) $analysis->id,
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'decision' => $this->decision,
            'mode' => $this->mode,
            'ready' => $this->ready,
            'ratios' => $this->ratios,
            'enabled_media' => $this->enabledMedia,
            'metadata' => $this->metadata,
            'target_analysis_id' => $this->targetAnalysisId,
        ];
    }

    public function isComplete(): bool
    {
        return $this->status === 'enabled_complete' && $this->ready;
    }

    public function ratioFor(string|int|float $dilution, int|string $replicate = 0): float
    {
        $scopeDilution = $this->mode === 'global'
            ? 'global'
            : $this->dilutionKey($dilution);
        $scopeReplicate = $this->mode === 'global' ? '0' : (string) $replicate;

        foreach ($this->ratios as $availableDilution => $replicates) {
            if (! is_array($replicates) || $this->dilutionKey($availableDilution) !== $scopeDilution) {
                continue;
            }

            foreach ($replicates as $availableReplicate => $ratio) {
                if ((string) $availableReplicate !== $scopeReplicate) {
                    continue;
                }

                return is_numeric($ratio) ? (float) $ratio : 0.0;
            }
        }

        return 0.0;
    }

    private function dilutionKey(string|int|float $dilution): string
    {
        if (! is_numeric($dilution)) {
            return (string) $dilution;
        }

        return rtrim(rtrim(sprintf('%.10F', (float) $dilution), '0'), '.');
    }
}
