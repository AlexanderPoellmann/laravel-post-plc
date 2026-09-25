<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Casts;

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class ProductCodeCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): PostProductCodes|ProductCode
    {
        $code = ProductCode::from($value);

        return $code->known() ?? $code;
    }
}
