<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Resolvers;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests\GetAllowedServicesForCountryRequest;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use InvalidArgumentException;

final readonly class AllowedServicesResolver
{
    public function __construct(private LaravelPostPlc $client) {}

    /** @param string|list<string> $countries */
    public function forCountries(string|array $countries): AllowedServices
    {
        $this->client->assertConfigured();

        $countryList = array_values(array_unique(array_map(
            static fn (string $country): string => strtoupper(trim($country)),
            is_array($countries) ? $countries : [$countries],
        )));

        if ($countryList === []) {
            throw new InvalidArgumentException('At least one destination country is required.');
        }

        if (in_array('AT', $countryList, true) && count($countryList) > 1) {
            throw new InvalidArgumentException('PLC does not allow Austria to be combined with other countries in product discovery.');
        }

        $request = new GetAllowedServicesForCountryRequest(
            clientID: $this->client->getClientId(),
            orgUnitID: $this->client->getOrgUnitId(),
            orgUnitGuid: $this->client->getOrgUnitGuid(),
            countryList: $countryList,
        );

        $this->client->request(ServiceMethods::GetAllowedServicesForCountry, $request);

        return AllowedServices::fromResponse($this->client->toArray());
    }

    public function forShipment(ShipmentRow $shipment): AllowedServices
    {
        return $this->forCountries($shipment->OURecipientAddress->countryCode());
    }
}
