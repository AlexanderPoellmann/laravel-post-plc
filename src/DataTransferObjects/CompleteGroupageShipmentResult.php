<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class CompleteGroupageShipmentResult extends Data
{
    public function __construct(
        public readonly ?ColloRow $CompleteGroupageShipmentResult,
        public readonly ?string $labelData,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
    ) {}
}
