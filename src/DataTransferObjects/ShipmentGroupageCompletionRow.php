<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Casts\ProductCodeCast;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Transformers\ProductCodeTransformer;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;

class ShipmentGroupageCompletionRow extends Data
{
    public function __construct(
        public readonly string $DestinationCountryID,
        #[WithTransformer(ProductCodeTransformer::class)]
        #[WithCast(ProductCodeCast::class)]
        public readonly PostProductCodes|ProductCode|string $DeliveryServiceThirdPartyID,
        public readonly PrinterRow $PrinterObject,
    ) {}
}
