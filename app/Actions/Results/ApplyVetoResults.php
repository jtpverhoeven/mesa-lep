<?php

namespace App\Actions\Results;

use App\Models\SampleAnalysis;

class ApplyVetoResults
{
    public function handle(SampleAnalysis $analysis, array $calculation): array
    {
        $vetoes = $analysis->vetoResults->unique('parameter')->keyBy('parameter');

        foreach ($calculation['output'] ?? [] as $parameter => $value) {
            $calculation['vetoReasons'][$analysis->id][$parameter] = '';
            $veto = $vetoes->get($parameter);

            if ($veto === null || $veto->result === '') {
                continue;
            }

            if (! array_key_exists($parameter, $calculation['vetoOverWrite'] ?? [])) {
                $calculation['vetoOverWrite'][$parameter] = $value;
            }

            $calculation['output'][$parameter] = $veto->result;
            $calculation['outputEn'][$parameter] = $veto->result;
            $calculation['vetoReasons'][$analysis->id][$parameter] = $veto->reason;

            if ($veto->disposition !== null) {
                $calculation['disposition'][$parameter] = $veto->disposition;
            } elseif (preg_match('/[a-z]/i', $veto->result) === 1) {
                $calculation['disposition'][$parameter] = '-';
            }
        }

        return $calculation;
    }
}
