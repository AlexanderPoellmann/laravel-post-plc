<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\ValueObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use InvalidArgumentException;
use Stringable;

final readonly class ProductCode implements Stringable
{
    public function __construct(public string $value)
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 50) {
            throw new InvalidArgumentException('A PLC product code must contain between 1 and 50 characters.');
        }

        if ($value !== $this->value) {
            throw new InvalidArgumentException('A PLC product code may not contain surrounding whitespace.');
        }
    }

    public static function from(self|PostProductCodes|string|int $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof PostProductCodes) {
            return new self($value->apiValue());
        }

        $known = PostProductCodes::fromApiValue($value);

        return new self($known?->apiValue() ?? (string) $value);
    }

    public function known(): ?PostProductCodes
    {
        return PostProductCodes::fromApiValue($this->value);
    }

    public function equals(self|PostProductCodes|string|int $other): bool
    {
        return $this->value === self::from($other)->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
