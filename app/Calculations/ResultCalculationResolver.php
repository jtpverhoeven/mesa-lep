<?php

namespace App\Calculations;

use App\Calculations\Contracts\ResultCalculation;
use App\Calculations\DummyResultCalculation as DefaultResultCalculation;
use App\Calculations\Exceptions\ResultCalculatorNotConfiguredException;
use App\Models\Assay;
use LogicException;

class ResultCalculationResolver
{
    public function resolve(Assay $assay): ResultCalculation
    {
        $calculators = config('result-calculations.calculators', []);
        $identifier = $this->scriptIdentifier($assay->script);

        if (! is_array($calculators)) {
            throw new LogicException('Result calculator configuration must be an array.');
        }

        $calculators = array_change_key_case($calculators, CASE_LOWER);

        if ($identifier === null) {
            $calculator = config('result-calculations.default', DefaultResultCalculation::class);
            $identifier = 'default';
        } else {
            $calculator = $calculators[$identifier] ?? null;

            if ($calculator === null) {
                throw new ResultCalculatorNotConfiguredException(
                    (int) $assay->id,
                    $identifier,
                );
            }
        }

        if (! is_string($calculator) || ! is_subclass_of($calculator, ResultCalculation::class)) {
            throw new LogicException("Invalid result calculator configured for [{$identifier}].");
        }

        return app($calculator);
    }

    private function scriptIdentifier(?string $script): ?string
    {
        if ($script === null || trim($script) === '') {
            return null;
        }

        if (preg_match(
            '/(?:^|\R)\h*file:\h*([a-z0-9_-]+)(?:\.class)?(?:\.php)?\h*(?=\R|$)/i',
            $script,
            $matches,
        ) === 1) {
            return strtolower($matches[1]);
        }

        if (stripos($script, 'file:') !== false) {
            throw new LogicException('Malformed legacy result calculator file declaration.');
        }

        return null;
    }
}
