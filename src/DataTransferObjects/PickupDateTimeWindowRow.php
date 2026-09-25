<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class PickupDateTimeWindowRow extends Data
{
    public function __construct(
        public readonly string $Date,
        public readonly string $TimeFrom,
        public readonly string $TimeTo,
    ) {}
}
