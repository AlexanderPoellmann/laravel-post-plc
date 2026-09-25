<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Casts;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final readonly class SoapCollectionCast implements Cast
{
    public function __construct(private string $itemName) {}

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        if ($value instanceof DataCollection) {
            return $value;
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        } elseif (is_object($value)) {
            $value = get_object_vars($value);
        }

        // SOAP represents ArrayOfX as an X member, which may itself be a single item.
        if (is_array($value) && array_key_exists($this->itemName, $value)) {
            $value = $value[$this->itemName];
        }

        $items = $value === null ? [] : (is_array($value) && array_is_list($value) ? $value : [$value]);

        return $property->type->dataClass === null
            ? $items
            : new DataCollection($property->type->dataClass, $items);
    }
}
