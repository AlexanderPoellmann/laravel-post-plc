<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CarrierServiceRow extends Data
{
    public function __construct(
        public readonly string $ThirdPartyID,
        public readonly string $Name,
        public readonly bool $Contract,
        public readonly int $OrderID,
        #[DataCollectionOf(AdditionalInformationResult::class)]
        public readonly ?DataCollection $FeatureList,
    ) {}
}
