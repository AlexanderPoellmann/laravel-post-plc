<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Validation;

final readonly class ValidationError
{
    public function __construct(
        public string $code,
        public string $path,
        public string $message,
    ) {}
}
