<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\PickupDateTimeWindowRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\PickupOrderRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests\BuildGroupageShipmentRequest;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentGroupageBuildingRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PickupLocationTypes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;
use AlexanderPoellmann\LaravelPostPlc\Enums\SecurePickupLocationTypes;
use AlexanderPoellmann\LaravelPostPlc\Support\PayloadNormalizer;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\ShipmentFixtures;

it('serializes v2 shipment fields with exact PLC wire names', function (): void {
    $configuration = new PlcConfiguration('test', '1', '2', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid');
    $recipient = (new Address)
        ->name('Recipient')
        ->street('Rochusmarkt 1')
        ->postCode('1030')
        ->city('Wien')
        ->countryCode('AT')
        ->get();
    $importer = (new Address)
        ->name('Importer')
        ->street('Importweg 2')
        ->postCode('1010')
        ->city('Wien')
        ->countryCode('AT')
        ->customsDutyAccountNumber('DUTY-1')
        ->customsTaxAccountNumber('TAX-1')
        ->provinceCode('AT-9')
        ->get();

    $shipment = (new Shipment($configuration))
        ->using(PostProductCodes::PaketOesterreich)
        ->to($recipient)
        ->importer($importer)
        ->referenceBarcodeType('C')
        ->withFeatures([FeatureRow::make('202')])
        ->get();

    $payload = PayloadNormalizer::request($shipment);

    expect($payload)->toHaveKey('OrgUnitGUID', 'guid')
        ->and($payload)->not->toHaveKey('OrgUnitGuid')
        ->and($payload['OUImporterAddress']['CustomsDutyAccountNumber'])->toBe('DUTY-1')
        ->and($payload['RefBarcodeType'])->toBe('C')
        ->and($payload['FeatureList'][0]['ThirdPartyID'])->toBe('202');
});

it('serializes unknown shipment and feature codes without enum coercion', function (): void {
    $shipment = (new Shipment)->using('NEW-PRODUCT')
        ->to(ShipmentFixtures::address())
        ->withFeatures([['ThirdPartyID' => '999', 'Value1' => 'new-value']])->get();

    $payload = PayloadNormalizer::request($shipment);

    expect($payload['DeliveryServiceThirdPartyID'])->toBe('NEW-PRODUCT')
        ->and($payload['FeatureList'][0]['ThirdPartyID'])->toBe('999')
        ->and($shipment->knownProduct())->toBeNull();
});

it('serializes groupage requests with canonical product codes and row envelopes', function (): void {
    $request = new BuildGroupageShipmentRequest(
        '1', '2', 'guid',
        new ShipmentGroupageBuildingRow('DE', '4', ['TRACK-1', 'TRACK-2']),
    );

    expect(PayloadNormalizer::request($request))->toBe([
        'clientID' => '1', 'orgUnitID' => '2', 'orgUnitGuid' => 'guid',
        'row' => ['DestinationCountryID' => 'DE', 'DeliveryServiceThirdPartyID' => '04', 'ColloIdentCodeList' => ['TRACK-1', 'TRACK-2']],
    ]);
});

it('serializes all documented printer formats', function (string $language): void {
    $shipment = (new Shipment)->using(PostProductCodes::PaketOesterreich)
        ->to(ShipmentFixtures::address())
        ->withPrinter(
            language: PrinterLanguages::from($language),
            paperLayout: PaperLayouts::SHORT,
        )->get();

    expect(PayloadNormalizer::request($shipment)['PrinterObject'])
        ->toHaveKey('LanguageID', $language)->toHaveKey('PaperLayoutID', '100x150');
})->with(['ZPL2', 'PDF', 'JPEG', 'GIF', 'PNG', 'PDFZPL2', 'None']);

it('serializes the secure pickup location under the v2 ID field name', function (): void {
    $address = (new Address)
        ->name('Pickup')
        ->street('Main 1')
        ->postCode('1010')
        ->city('Wien')
        ->countryCode('AT')
        ->get();

    $row = new PickupOrderRow(
        PickupAddress: $address,
        PickupDateTimeWindow: new PickupDateTimeWindowRow('2026-09-26T00:00:00', '08:00', '12:00'),
        NumberOfPackages: 1,
        ShipmentNumberList: null,
        PickupLocationType: PickupLocationTypes::Secure,
        SecurePickupLocationType: SecurePickupLocationTypes::Garage,
        OtherSecurePickupLocation: null,
        ContactPersonName: 'Max Mustermann',
        ContactPersonTel: null,
        ContactPersonEmail: null,
        Reference1: null,
        Reference2: null,
        AcceptTermsAndConditions: true,
    );

    expect(PayloadNormalizer::request($row))
        ->toHaveKey('SecurePickupLocationTypeID', SecurePickupLocationTypes::Garage->value)
        ->not->toHaveKey('SecurePickupLocationType');
});
