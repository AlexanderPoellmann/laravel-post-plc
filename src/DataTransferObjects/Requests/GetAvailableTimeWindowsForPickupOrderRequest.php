<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use Spatie\LaravelData\Data;

class GetAvailableTimeWindowsForPickupOrderRequest extends Data
{
    public function __construct(
        public readonly string $clientID,
        public readonly string $orgUnitID,
        public readonly string $orgUnitGuid,
        public readonly AddressRow $pickupAddressRow,
    ) {}
}
