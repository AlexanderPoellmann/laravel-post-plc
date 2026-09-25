<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Validation;

use AlexanderPoellmann\LaravelPostPlc\Exceptions\ShipmentValidationException;

final class ValidationResult
{
    /** @var list<ValidationError> */
    private array $errors = [];

    public function add(string $code, string $path, string $message): self
    {
        $this->errors[] = new ValidationError($code, $path, $message);

        return $this;
    }

    public function merge(self $other): self
    {
        foreach ($other->errors() as $error) {
            $this->errors[] = $error;
        }

        return $this;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function isInvalid(): bool
    {
        return ! $this->isValid();
    }

    /** @return list<ValidationError> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return list<string> */
    public function messages(): array
    {
        return array_map(static fn (ValidationError $error): string => $error->message, $this->errors);
    }

    public function has(string $code): bool
    {
        foreach ($this->errors as $error) {
            if ($error->code === $code) {
                return true;
            }
        }

        return false;
    }

    public function throwIfInvalid(): void
    {
        if ($this->isInvalid()) {
            throw new ShipmentValidationException($this);
        }
    }
}
