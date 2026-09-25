<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;

it('uses the selected endpoint and preserves false and zero in SOAP payloads', function (bool $sandbox, string $endpoint): void {
    $transport = FakePlcTransport::responding(['ok' => true]);
    $client = new LaravelPostPlc(new PlcConfiguration(
        identifier: 'test',
        clientId: '1',
        orgUnitId: '2',
        orgUnitGuid: 'guid',
        sandbox: $sandbox,
        productionEndpoint: 'https://production.invalid',
        sandboxEndpoint: 'https://sandbox.invalid',
    ), $transport);

    $response = $client->request(ServiceMethods::ImportShipment, [
        'CustomDataBit1' => false,
        'ReturnDays' => 0,
        'OmitMe' => null,
    ], asRow: true);

    expect($response)->toBe($client->getResponse())
        ->and($client->toArray())->toBe(['ok' => true])
        ->and($transport->calls)->toHaveCount(1)
        ->and($transport->calls[0]['endpoint'])->toBe($endpoint)
        ->and($transport->calls[0]['method'])->toBe(ServiceMethods::ImportShipment)
        ->and($transport->calls[0]['payload'])->toBe([
            'row' => [
                'CustomDataBit1' => false,
                'ReturnDays' => 0,
            ],
        ]);
})->with([
    'sandbox' => [true, 'https://sandbox.invalid'],
    'production' => [false, 'https://production.invalid'],
]);
