<?php

namespace App\Calculations;

use App\Calculations\Concerns\BuildsKveResult;
use App\Calculations\Contracts\DeterminesResultCompleteness;
use App\Calculations\Contracts\HandlesIncompleteResults;
use App\Calculations\Contracts\ResultCalculation;

class MazLegionella2019Calculation implements DeterminesResultCompleteness, HandlesIncompleteResults, ResultCalculation
{
    use BuildsKveResult {
        incompleteResult as kveIncompleteResult;
    }

    public function calculate(ResultCalculationContext $context): array
    {
        $calculation = $this->incompleteResult($context);

        if (! $this->resultsAreComplete($context)) {
            return $calculation;
        }

        $matrix = $this->matrixType($context);
        $counts = $this->stratifiedResults($context);
        $volume = $this->filterVolume($context);

        if (! in_array($matrix, ['A', 'B', 'C'], true) || $volume <= 0 || ! is_finite($volume)) {
            $calculation['output']['kve'] = 'Fout resultaat gevonden';
            $calculation['outputEn'] = [];

            return $calculation;
        }

        if ($matrix === 'A' && (int) max($counts[2]) > 0) {
            $calculation['output']['kve'] = 'Fout resultaat gevonden, BCYE - Positief';
            $calculation['outputEn'] = [];

            return $calculation;
        }

        $highest = match ($matrix) {
            'A' => max($counts[0], $counts[1], $counts[3], $counts[4], $counts[5], $counts[6])[0],
            'B' => max(array_sum($counts[0]), array_sum($counts[1]), array_sum($counts[2])),
            'C' => array_sum($counts[0]),
        };
        $ratio = $this->confirmationRatio($context);
        $colonies = ($ratio ?? 1) * (int) $highest;
        $rawResult = match ($matrix) {
            'A' => (($colonies * 5) / (0.2 * $volume)) * 1000,
            'B' => (($colonies * 5) / $volume) * 1000,
            'C' => ($colonies / $volume) * 1000,
        };
        $calculation['calculationReady'] = true;
        $calculation['confirmationTrigger'] = [
            'eligible' => $rawResult > 0,
            'numericValue' => $rawResult,
            'disposition' => $rawResult > 0 ? '+' : '-',
            'targetAnalysisId' => (int) $context->analysis->id,
        ];

        if ($rawResult <= 0) {
            $reported = $matrix === 'C' ? '<'.$this->significantFigures(1000 / $volume) : '<100';
            $calculation['output'] = ['kve' => $reported, 'aanvulling' => 'Geen Legionella spp. aangetoond'];
            $calculation['outputEn'] = ['kve' => $reported, 'aanvulling' => 'Legionella spp. not detected'];
            $calculation['hidden']['detected'] = false;
            $calculation['disposition']['kve'] = '-';
            $calculation['isReady'] = true;

            return $calculation;
        }

        $calculation['disposition']['kve'] = '+';
        $calculation['confirmationPendingResult'] = $ratio === null
            ? ['output' => ['kve' => 'Bevestiging wacht'], 'outputEn' => [], 'disposition' => ['kve' => '+']]
            : $this->notCompleted();

        if ($ratio === null) {
            $calculation['askForMetaConfirmation'] = true;

            return array_replace($calculation, $calculation['confirmationPendingResult']);
        }

        if ((int) $context->analysis->conf_requested === 1
            && ! ($context->confirmation?->isComplete() || $context->analysis->confirmationRecord?->isReady)) {
            return array_replace($calculation, $calculation['confirmationPendingResult']);
        }

        $addendum = $this->addendum($context);
        $reported = $this->significantFigures($rawResult);
        $calculation['output'] = ['kve' => $reported, 'aanvulling' => $addendum];
        $calculation['outputEn'] = $calculation['output'];
        $calculation['isReady'] = true;

        if ($addendum !== null) {
            $calculation['hidden']['detected'] = true;
        }

        return $calculation;
    }

    public function resultsAreComplete(ResultCalculationContext $context): bool
    {
        $groups = $this->stratifiedResults($context);
        $requiredGroups = match ($this->matrixType($context)) {
            'A' => 7,
            'B' => 3,
            default => 1,
        };

        if (count($groups) < $requiredGroups) {
            return false;
        }

        $hasPlates = false;

        foreach ($context->metaResults as $originalAssay => $dilutions) {
            if ((int) $originalAssay === (int) $context->assay->original_id) {
                continue;
            }

            foreach ($dilutions as $plates) {
                foreach ($plates as $plate) {
                    if (! is_array($plate) || ! array_key_exists('kve', $plate) || $plate['kve'] === '' || $plate['kve'] === null) {
                        return false;
                    }

                    $hasPlates = true;
                }
            }
        }

        return $hasPlates && ($this->matrixType($context) !== 'A' || $groups[2] !== []);
    }

    public function incompleteResult(ResultCalculationContext $context): array
    {
        return [...$this->kveIncompleteResult($context), 'askForMetaConfirmation' => false];
    }

    /** @return list<list<mixed>> */
    private function stratifiedResults(ResultCalculationContext $context): array
    {
        $groups = [];

        foreach ($context->metaResults as $originalAssay => $dilutions) {
            $position = count($groups);
            $groups[$position] = [];

            if ((int) $originalAssay === (int) $context->assay->original_id) {
                continue;
            }

            foreach ($dilutions as $plates) {
                foreach ($plates as $plate) {
                    $groups[$position][] = $plate['kve'] ?? null;
                }
            }
        }

        return $groups;
    }

    private function matrixType(ResultCalculationContext $context): string
    {
        return $context->scriptVariables()['matrixType'] ?? '';
    }

    private function filterVolume(ResultCalculationContext $context): float
    {
        $extra = json_decode($context->analysis->sampleRecord?->sample_extra ?? '{}', true);

        return is_array($extra) && array_key_exists('filter_volume', $extra)
            ? (float) str_replace(',', '.', (string) $extra['filter_volume'])
            : 250.0;
    }

    private function confirmationRatio(ResultCalculationContext $context): ?float
    {
        $ratio = $context->confirmation?->ratios['global'][0]
            ?? $context->analysis->confirmationRecord?->metadata['global'][0]['ratio']
            ?? null;

        return is_numeric($ratio) ? (float) $ratio : null;
    }

    private function addendum(ResultCalculationContext $context): ?string
    {
        $detected = 0;

        foreach ($context->analysis->confirmationRecord?->racetrack['global'][0] ?? [] as $track) {
            foreach ([2 => 1, 3 => 2, 4 => 4] as $step => $flag) {
                if (($track[$step] ?? null) === '+') {
                    $detected |= $flag;
                }
            }
        }

        return match ($detected) {
            1 => "Legionella pneumophila \u{2013} serotype 1",
            2 => "Legionella pneumophila \u{2013} serotype 2-14",
            3 => "Legionella pneumophila \u{2013} serotype 1 & serotype 2-14",
            4 => 'Legionella spp. (non-pneumophila)',
            5 => "Legionella pneumophila \u{2013} serotype 1 & Legionella spp. (non-pneumophila)",
            6 => "Legionella pneumophila \u{2013} serotype 2-14 & Legionella spp. (non-pneumophila)",
            7 => "Legionella pneumophila \u{2013} serotype 1, 2-14 & Legionella spp. (non-pneumophila)",
            default => null,
        };
    }
}
