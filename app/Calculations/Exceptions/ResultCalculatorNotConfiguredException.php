<?php

namespace App\Calculations\Exceptions;

class ResultCalculatorNotConfiguredException extends ResultCalculationException
{
    public function __construct(int $assayId, string $scriptIdentifier)
    {
        parent::__construct(
            "No result calculator is configured for assay [{$assayId}] script [file:{$scriptIdentifier}].",
        );
    }

    public function userMessage(): string
    {
        return 'Het eindresultaat kan niet worden berekend, omdat er geen rekenmodule is ingesteld voor deze analyse.';
    }
}
