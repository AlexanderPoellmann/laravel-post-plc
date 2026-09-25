<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;

it('rejects malformed country codes before making a request', function (string $country): void {
    $transport = FakePlcTransport::responding();
    $client = new LaravelPostPlc(transport: $transport);

    expect(fn () => (new AllowedServicesResolver($client))->forCountries($country))
        ->toThrow(InvalidArgumentException::class);
    expect($transport->calls)->toBe([]);
})->with(['', ' ', 'AUT', 'A1', 'A']);

it('calls PLC product discovery with typed credentials and normalized countries', function (): void {
    $transport = FakePlcTransport::responding([
        'CarrierServiceRow' => [
            'ThirdPartyID' => '45',
            'Contract' => true,
            'OrderID' => 1,
        ],
    ]);
    $client = new LaravelPostPlc(new PlcConfiguration(
        identifier: 'test',
        clientId: '123',
        orgUnitId: '456',
        orgUnitGuid: 'guid',
        sandbox: false,
        productionEndpoint: 'https://plc.invalid',
        sandboxEndpoint: 'https://sandbox.invalid',
    ), $transport);

    $allowed = (new AllowedServicesResolver($client))->forCountries(['de', 'DE']);

    expect($allowed->allowsProduct(PostProductCodes::PaketPremiumInternational))->toBeTrue()
        ->and($transport->calls[0]['method'])->toBe(ServiceMethods::GetAllowedServicesForCountry)
        ->and($transport->calls[0]['payload'])->toMatchArray([
            'clientID' => '123',
            'orgUnitID' => '456',
            'orgUnitGuid' => 'guid',
            'countryList' => ['DE'],
        ]);
});

it('rejects Austria mixed with other countries because PLC does not allow that discovery request', function (): void {
    $transport = FakePlcTransport::responding();
    $client = new LaravelPostPlc(new PlcConfiguration('test', '1', '2', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid'), $transport);

    expect(fn () => (new AllowedServicesResolver($client))->forCountries(['AT', 'DE']))
        ->toThrow(InvalidArgumentException::class);
});
