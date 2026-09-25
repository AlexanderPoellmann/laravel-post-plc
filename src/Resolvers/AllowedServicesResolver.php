<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Resolvers;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests\GetAllowedServicesForCountryRequest;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\PlcRequestException;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;

final readonly class AllowedServicesResolver
{
    public function __construct(
        private LaravelPostPlc $client,
        private ?CacheRepository $cache = null,
    ) {}

    /** @param string|list<string> $countries */
    public function forCountries(string|array $countries, bool $fresh = false): AllowedServices
    {
        $this->client->assertConfigured();
        $countryList = $this->normalizeCountries($countries);

        if (! $this->cacheEnabled()) {
            return $this->resolve($countryList);
        }

        if ($fresh) {
            $services = $this->resolve($countryList);
            $this->cache?->put(
                $this->cacheKey($countryList),
                $services->toArray(),
                (int) config('post-plc.capabilities.cache.ttl', 21600),
            );

            return $services;
        }

        $cached = $this->cache?->remember(
            $this->cacheKey($countryList),
            (int) config('post-plc.capabilities.cache.ttl', 21600),
            fn (): array => $this->resolve($countryList)->toArray(),
        );

        return is_array($cached) ? AllowedServices::fromArray($cached) : $this->resolve($countryList);
    }

    public function forShipment(ShipmentRow $shipment, bool $fresh = false): AllowedServices
    {
        return $this->forCountries($shipment->OURecipientAddress->countryCode(), $fresh);
    }

    /** @param string|list<string> $countries */
    public function forget(string|array $countries): bool
    {
        if ($this->cache === null) {
            return false;
        }

        return $this->cache->forget($this->cacheKey($this->normalizeCountries($countries)));
    }

    /** @param list<string> $countryList */
    private function resolve(array $countryList): AllowedServices
    {
        $request = new GetAllowedServicesForCountryRequest(
            clientID: $this->client->getClientId(),
            orgUnitID: $this->client->getOrgUnitId(),
            orgUnitGuid: $this->client->getOrgUnitGuid(),
            countryList: $countryList,
        );

        $this->client->request(ServiceMethods::GetAllowedServicesForCountry, $request);
        $response = $this->client->toArray();

        if (isset($response['errorCode']) && trim((string) $response['errorCode']) !== '') {
            throw new PlcRequestException(
                ServiceMethods::GetAllowedServicesForCountry,
                (string) $response['errorCode'],
                isset($response['errorMessage']) ? (string) $response['errorMessage'] : null,
            );
        }

        return AllowedServices::fromResponse($response);
    }

    /** @param string|list<string> $countries
     * @return list<string>
     */
    private function normalizeCountries(string|array $countries): array
    {
        $countryList = array_values(array_unique(array_map(
            static fn (string $country): string => strtoupper(trim($country)),
            is_array($countries) ? $countries : [$countries],
        )));

        if ($countryList === []) {
            throw new InvalidArgumentException('At least one destination country is required.');
        }

        foreach ($countryList as $country) {
            if (preg_match('/^[A-Z]{2}$/D', $country) !== 1) {
                throw new InvalidArgumentException('Destination countries must be two-letter ISO country codes.');
            }
        }

        if (in_array('AT', $countryList, true) && count($countryList) > 1) {
            throw new InvalidArgumentException('PLC does not allow Austria to be combined with other countries in product discovery.');
        }

        return $countryList;
    }

    private function cacheEnabled(): bool
    {
        return $this->cache !== null && (bool) config('post-plc.capabilities.cache.enabled', true);
    }

    /** @param list<string> $countries */
    private function cacheKey(array $countries): string
    {
        sort($countries);

        return 'laravel-post-plc:capabilities:'.hash('sha256', implode('|', [
            $this->client->configuration()->cacheIdentity(),
            implode(',', $countries),
        ]));
    }
}
