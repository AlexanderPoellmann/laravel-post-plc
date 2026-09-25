<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentAndGenerateBarcodeResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentResult;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;
use RicorocksDigitalAgency\Soap\Response\Response;

it('hydrates the response DTO for a successful import', function (ServiceMethods $method, string $dataClass): void {
    $client = new LaravelPostPlc(transport: FakePlcTransport::responding(['pdfData' => 'label-data']));
    $client->request($method, []);

    $result = $client->toObject();

    expect($result)->toBeInstanceOf($dataClass)
        ->and($result->pdfData)->toBe('label-data');
})->with([
    'shipment' => [ServiceMethods::ImportShipment, ImportShipmentResult::class],
    'barcode' => [ServiceMethods::ImportShipmentAndGenerateBarcode, ImportShipmentAndGenerateBarcodeResult::class],
]);

it('clears the previous response when a subsequent SOAP call fails', function (): void {
    $transport = Mockery::mock(PlcTransport::class);
    $transport->shouldReceive('call')->once()->ordered()->andReturn(Response::new(['pdfData' => 'old-label']));
    $transport->shouldReceive('call')->once()->ordered()->andThrow(new SoapFault('Server', 'Unavailable'));
    $client = new LaravelPostPlc(transport: $transport);

    $client->request(ServiceMethods::ImportShipment, []);

    expect(fn () => $client->request(ServiceMethods::ImportShipmentAndGenerateBarcode, []))->toThrow(SoapFault::class);
    expect($client->getResponse())->toBeNull()
        ->and($client->toArray())->toBe([])
        ->and($client->lastMethod())->toBe(ServiceMethods::ImportShipmentAndGenerateBarcode)
        ->and(fn () => $client->toObject())->toThrow(LogicException::class);
});

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
