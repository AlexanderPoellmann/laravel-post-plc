<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\PickupLocationTypes;
use AlexanderPoellmann\LaravelPostPlc\Enums\SecurePickupLocationTypes;
use Spatie\LaravelData\Data;

class PickupOrderRow extends Data
{
    /** @param list<string>|null $ShipmentNumberList */
    public function __construct(
        public readonly AddressRow $PickupAddress,
        public readonly PickupDateTimeWindowRow $PickupDateTimeWindow,
        public readonly ?int $NumberOfPackages,
        public readonly ?array $ShipmentNumberList,
        public readonly PickupLocationTypes $PickupLocationType,
        public readonly ?SecurePickupLocationTypes $SecurePickupLocationType,
        public readonly ?string $OtherSecurePickupLocation,
        public readonly string $ContactPersonName,
        public readonly ?string $ContactPersonTel,
        public readonly ?string $ContactPersonEmail,
        public readonly ?string $Reference1,
        public readonly ?string $Reference2,
        public readonly bool $AcceptTermsAndConditions,
    ) {}
}
