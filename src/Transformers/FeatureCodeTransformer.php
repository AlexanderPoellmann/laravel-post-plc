<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Transformers;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use InvalidArgumentException;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

final class FeatureCodeTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): string
    {
        if (! $value instanceof FeatureCode && ! $value instanceof Features && ! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException('Expected a PLC feature code.');
        }

        return FeatureCode::from($value)->value;
    }
}
