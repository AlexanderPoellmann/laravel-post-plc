<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Resolvers;

use AlexanderPoellmann\LaravelPostPlc\Contracts\CustomsRequirementResolver;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;

final class EuCustomsRequirementResolver implements CustomsRequirementResolver
{
    /** @var list<string> */
    private array $euCountryCodes;

    /** @param list<string>|null $euCountryCodes */
    public function __construct(?array $euCountryCodes = null)
    {
        $configured = $euCountryCodes ?? config('post-plc.customs.eu_country_codes', []);
        $configured = is_array($configured) ? $configured : [];
        $this->euCountryCodes = array_values(array_unique(array_map(
            static fn (mixed $country): string => strtoupper((string) $country),
            $configured,
        )));
    }

    public function requiresCustoms(?AddressRow $sender, AddressRow $recipient): bool
    {
        $origin = $sender?->countryCode() ?: 'AT';
        $destination = $recipient->countryCode();

        if ($origin === $destination) {
            return false;
        }

        return ! (in_array($origin, $this->euCountryCodes, true)
            && in_array($destination, $this->euCountryCodes, true));
    }
}
