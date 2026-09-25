<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class CancelShipmentResult extends Data
{
    public function __construct(
        public readonly bool $CancelSuccessful,
        public readonly ?string $ErrorCode,
        public readonly ?string $ErrorMessage,
        public readonly ?string $Number,
    ) {}
}
