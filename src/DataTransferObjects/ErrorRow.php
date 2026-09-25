<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class ErrorRow extends Data
{
    public function __construct(
        public readonly string $Code,
        public readonly string $Reference,
        public readonly string $Message,
    ) {}
}
