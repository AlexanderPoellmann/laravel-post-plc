<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentResult;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;

it('hydrates wrapped SOAP parcels and tracking codes', function (string $method, bool $multiple): void {
    $parcel = (object) [
        'Weight' => 1.5,
        'ColloCodeList' => (object) ['ColloCode' => (object) ['Code' => 'TRACK-1', 'NumberTypeID' => 1]],
    ];
    $client = new LaravelPostPlc(transport: FakePlcTransport::responding([
        $method.'Result' => (object) ['ColloRow' => $multiple ? [$parcel, $parcel] : $parcel],
        'imageData' => (object) ['string' => 'base64-image'],
    ]));
    $client->request(ServiceMethods::from($method), []);

    $result = $client->toObject();
    $parcels = $result->{$method.'Result'};

    expect($parcels)->toHaveCount($multiple ? 2 : 1)
        ->and($parcels[0]->Weight)->toBe(1.5)
        ->and($parcels[0]->ColloCodeList[0]->Code)->toBe('TRACK-1');

    if ($method === 'ImportShipmentReturnImage') {
        expect($result->imageData)->toBe(['base64-image']);
    }
})->with(['ImportShipment', 'ImportShipmentAndGenerateBarcode', 'ImportShipmentForce', 'ImportShipmentReturnImage'])
    ->with([false, true]);

it('hydrates wrapped parcels when using a response DTO directly', function (): void {
    $result = ImportShipmentResult::from([
        'ImportShipmentResult' => ['ColloRow' => ['Weight' => 2]],
    ]);

    expect($result->ImportShipmentResult[0]->Weight)->toBe(2.0);
});

it('preserves null, empty and already flat SOAP collections', function (mixed $payload, ?int $count): void {
    $result = ImportShipmentResult::from(['ImportShipmentResult' => $payload]);

    if ($count === null) {
        expect($result->ImportShipmentResult)->toBeNull();
    } else {
        expect($result->ImportShipmentResult)->toHaveCount($count);
    }
})->with([
    'null' => [null, null],
    'empty' => [[], 0],
    'empty SOAP object' => [new stdClass, 0],
    'flat list' => [[['Weight' => 1], ['Weight' => 2]], 2],
]);

it('hydrates the single groupage result with nested tracking codes', function (): void {
    $client = new LaravelPostPlc(transport: FakePlcTransport::responding([
        'CompleteGroupageShipmentResult' => (object) [
            'ColloCodeList' => (object) ['ColloCode' => (object) ['Code' => 'PALLET-1', 'NumberTypeID' => 1]],
        ],
        'labelData' => 'base64-label',
    ]));
    $client->request(ServiceMethods::CompleteGroupageShipment, []);

    expect($client->toObject()->CompleteGroupageShipmentResult->ColloCodeList[0]->Code)->toBe('PALLET-1');
});
