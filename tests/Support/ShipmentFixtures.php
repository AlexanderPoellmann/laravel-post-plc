<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Tests\Support;

use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;

final class ShipmentFixtures
{
    public static function address(string $country = 'AT', bool $withContact = false): AddressRow
    {
        $address = (new Address)
            ->name('Example GmbH')
            ->street('Main Street 1')
            ->postCode('1010')
            ->city('Example City')
            ->countryCode($country);

        if ($withContact) {
            $address->phone('+431234567')->email('shipping@example.com');
        }

        return $address->get();
    }
}
