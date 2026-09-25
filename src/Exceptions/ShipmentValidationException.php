<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Exceptions;

use AlexanderPoellmann\LaravelPostPlc\Validation\ValidationResult;
use InvalidArgumentException;

final class ShipmentValidationException extends InvalidArgumentException
{
    public function __construct(public readonly ValidationResult $result)
    {
        parent::__construct('The shipment is not compatible with the selected PLC options: '.implode(' ', $result->messages()));
    }
}
