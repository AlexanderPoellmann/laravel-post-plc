<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnShipmentFactory;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\ShipmentFixtures;

it('builds a domestic return by reversing the customer and return recipient roles', function (): void {
    $client = new LaravelPostPlc(
        new PlcConfiguration('test', '1', '2', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid'),
        FakePlcTransport::responding(),
    );
    $customer = ShipmentFixtures::address('AT', true);
    $warehouse = ShipmentFixtures::address('AT', true);

    $return = (new ReturnShipmentFactory($client))->fromAddresses($customer, $warehouse);

    expect($return->knownProduct())->toBe(PostProductCodes::Retourpaket)
        ->and($return->OUShipperAddress?->Name1)->toBe($customer->Name1)
        ->and($return->OURecipientAddress->Name1)->toBe($warehouse->Name1)
        ->and($return->OrgUnitGuid)->toBe('guid');
});

it('requires an explicit product for international returns', function (): void {
    $client = new LaravelPostPlc(
        new PlcConfiguration('test', '1', '2', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid'),
        FakePlcTransport::responding(),
    );

    expect(fn () => (new ReturnShipmentFactory($client))->fromAddresses(
        ShipmentFixtures::address('DE', true),
        ShipmentFixtures::address('AT', true),
    ))->toThrow(InvalidArgumentException::class);
});
