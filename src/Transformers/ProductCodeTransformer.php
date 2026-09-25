<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Transformers;

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use InvalidArgumentException;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

final class ProductCodeTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): string
    {
        if (! $value instanceof ProductCode && ! $value instanceof PostProductCodes && ! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException('Expected a PLC product code.');
        }

        return ProductCode::from($value)->value;
    }
}
