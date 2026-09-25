<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Classes\Collo;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\ShipmentValidationException;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnLabelService;
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnShipmentFactory;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\ShipmentFixtures;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

it('creates a QR return through ImportShipmentAndGenerateBarcode', function (): void {
    $transport = FakePlcTransport::responding([
        'ImportShipmentAndGenerateBarcodeResult' => [],
        'qrCode' => 'base64-qr',
        'code128' => 'base64-code128',
    ]);
    $client = new LaravelPostPlc(
        new PlcConfiguration('test', '1', '2', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid'),
        $transport,
    );
    $shipment = (new ReturnShipmentFactory($client))->fromAddresses(
        ShipmentFixtures::address('AT', true),
        ShipmentFixtures::address('AT', true),
    );

    $result = (new ReturnLabelService($client, app(ShipmentValidator::class)))->createQr($shipment);

    expect($result->qrCode)->toBe('base64-qr')
        ->and($transport->calls[0]['method'])->toBe(ServiceMethods::ImportShipmentAndGenerateBarcode)
        ->and($transport->calls[0]['payload']['row']['DeliveryServiceThirdPartyID'])->toBe('28');
});

it('rejects QR generation for a known international return product', function (): void {
    $transport = FakePlcTransport::responding();
    $client = new LaravelPostPlc(
        new PlcConfiguration('test', '1', '2', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid'),
        $transport,
    );
    $shipment = (new ReturnShipmentFactory($client))->fromAddresses(
        ShipmentFixtures::address('DE', true),
        ShipmentFixtures::address('AT', true),
        PostProductCodes::RetourpaketInternational,
    );

    expect(fn () => (new ReturnLabelService($client, app(ShipmentValidator::class)))->createQr($shipment))
        ->toThrow(InvalidArgumentException::class, 'QR-code generation');
    expect($transport->calls)->toBe([]);
});

it('creates a printable international return with explicit parcel weights', function (): void {
    $transport = FakePlcTransport::responding(['pdfData' => 'base64-pdf']);
    $client = new LaravelPostPlc(transport: $transport);
    $outbound = (new Shipment)->using(PostProductCodes::PaketPremiumInternational)
        ->to(ShipmentFixtures::address('DE', true))->get();
    $shipment = (new ReturnShipmentFactory($client))->fromOutbound(
        $outbound,
        ShipmentFixtures::address('AT', true),
        PostProductCodes::RetourpaketInternational,
        parcels: [(new Collo)->weight(1)->get()],
    );

    $result = (new ReturnLabelService($client, app(ShipmentValidator::class)))->createLabel($shipment);
    $payload = $transport->calls[0]['payload']['row'];

    expect($result->pdfData)->toBe('base64-pdf')
        ->and($transport->calls[0]['method'])->toBe(ServiceMethods::ImportShipment)
        ->and($payload['PrinterObject']['LanguageID'])->toBe('PDF')
        ->and($payload['OUShipperAddress']['CountryID'])->toBe('DE')
        ->and($payload['OURecipientAddress']['CountryID'])->toBe('AT')
        ->and($payload['ColloList'][0]['Weight'])->toBe(1.0);
});

it('supports the documented Business Paketmarke QR workflow', function (): void {
    $transport = FakePlcTransport::responding(['qrCode' => 'base64-qr']);
    $shipment = (new Shipment)->using(PostProductCodes::PaketOesterreich)
        ->withPrinter()->to(ShipmentFixtures::address())
        ->withFeatures([FeatureRow::businessParcelStamp()])->get();
    $service = new ReturnLabelService(new LaravelPostPlc(transport: $transport), app(ShipmentValidator::class));

    expect($service->createQr($shipment)->qrCode)->toBe('base64-qr');
});

it('rejects invalid return shipments before calling PLC', function (): void {
    $transport = FakePlcTransport::responding();
    $shipment = (new Shipment)->using(PostProductCodes::Retourpaket)->to(ShipmentFixtures::address('DE'))->get();
    $service = new ReturnLabelService(new LaravelPostPlc(transport: $transport), app(ShipmentValidator::class));

    expect(fn () => $service->createLabel($shipment))->toThrow(ShipmentValidationException::class);
    expect($transport->calls)->toBe([]);
});
