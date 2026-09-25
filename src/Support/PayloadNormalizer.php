<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Support;

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use UnitEnum;

final class PayloadNormalizer
{
    /**
     * @param  Data|array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function request(Data|array $payload): array
    {
        $value = $payload instanceof Data ? $payload->toArray() : $payload;

        /** @var array<string, mixed> $normalized */
        $normalized = self::normalize($value, removeNulls: true);

        return $normalized;
    }

    /** @return array<string, mixed> */
    public static function response(mixed $payload): array
    {
        $normalized = self::normalize($payload, removeNulls: false);

        if (is_array($normalized)) {
            return $normalized;
        }

        return ['value' => $normalized];
    }

    private static function normalize(mixed $value, bool $removeNulls): mixed
    {
        if ($value instanceof Data || $value instanceof DataCollection || $value instanceof Arrayable) {
            return self::normalize($value->toArray(), $removeNulls);
        }

        if ($value instanceof PostProductCodes) {
            return $value->apiValue();
        }

        if ($value instanceof ProductCode || $value instanceof FeatureCode) {
            return $value->value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_object($value)) {
            return self::normalize(get_object_vars($value), $removeNulls);
        }

        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if ($removeNulls && $item === null) {
                continue;
            }

            $normalized[$key] = self::normalize($item, $removeNulls);
        }

        return $removeNulls && array_is_list($value) ? array_values($normalized) : $normalized;
    }
}
