<?php

namespace App\Calculations\Exceptions;

use RuntimeException;

abstract class ResultCalculationException extends RuntimeException
{
    abstract public function userMessage(): string;
}
