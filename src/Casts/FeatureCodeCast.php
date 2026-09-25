<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Casts;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class FeatureCodeCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Features|FeatureCode
    {
        $code = FeatureCode::from($value);

        return $code->known() ?? $code;
    }
}
