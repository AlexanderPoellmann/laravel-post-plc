<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\ValueObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use InvalidArgumentException;
use Stringable;

final readonly class FeatureCode implements Stringable
{
    public function __construct(public string $value)
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 3) {
            throw new InvalidArgumentException('A PLC feature code must contain between 1 and 3 characters.');
        }

        if ($value !== $this->value) {
            throw new InvalidArgumentException('A PLC feature code may not contain surrounding whitespace.');
        }
    }

    public static function from(self|Features|string|int $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof Features) {
            return new self($value->value);
        }

        $candidate = (string) $value;
        $known = Features::tryFrom($candidate);

        if ($known === null && ctype_digit($candidate)) {
            $known = Features::tryFrom(str_pad($candidate, 3, '0', STR_PAD_LEFT));
        }

        if ($known !== null) {
            return new self($known->value);
        }

        return new self($candidate);
    }

    public function known(): ?Features
    {
        return Features::tryFrom($this->value);
    }

    public function equals(self|Features|string|int $other): bool
    {
        return $this->value === self::from($other)->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
