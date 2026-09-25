<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests;

use Spatie\LaravelData\Data;

class GetAllowedServicesForCountryRequest extends Data
{
    /** @param list<string> $countryList */
    public function __construct(
        public readonly string $clientID,
        public readonly string $orgUnitID,
        public readonly string $orgUnitGuid,
        public readonly array $countryList,
    ) {}
}
