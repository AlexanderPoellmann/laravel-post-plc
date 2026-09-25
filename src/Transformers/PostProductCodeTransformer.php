<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Transformers;

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use InvalidArgumentException;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

final class PostProductCodeTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): string
    {
        if (! $value instanceof PostProductCodes) {
            throw new InvalidArgumentException('Expected a PostProductCodes enum value.');
        }

        return $value->apiValue();
    }
}
