<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Exceptions;

use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use RuntimeException;

final class PlcRequestException extends RuntimeException
{
    public function __construct(
        public readonly ServiceMethods $method,
        public readonly string $errorCode,
        public readonly ?string $errorMessage,
    ) {
        parent::__construct(sprintf('%s failed [%s]: %s', $method->value, $errorCode, $errorMessage ?? 'Unknown PLC error'));
    }
}
