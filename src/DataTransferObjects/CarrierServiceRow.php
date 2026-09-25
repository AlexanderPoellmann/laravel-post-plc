<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Casts\SoapCollectionCast;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CarrierServiceRow extends Data
{
    public function __construct(
        public readonly string $ThirdPartyID,
        public readonly string $Name,
        public readonly bool $Contract,
        public readonly int $OrderID,
        #[WithCast(SoapCollectionCast::class, 'AdditionalInformationResult')]
        #[DataCollectionOf(AdditionalInformationResult::class)]
        public readonly ?DataCollection $FeatureList,
    ) {}
}
