<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterEncoding;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\PlcRequestException;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\ShipmentValidationException;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Shipping\PostPlcShippingAdapter;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use AlexanderPoellmann\Shipping\Contracts\CancelsShipments;
use AlexanderPoellmann\Shipping\Contracts\Carrier;
use AlexanderPoellmann\Shipping\Contracts\CreatesShipments;
use AlexanderPoellmann\Shipping\Contracts\DownloadsLabels;
use AlexanderPoellmann\Shipping\Data\Address;
use AlexanderPoellmann\Shipping\Data\Parcel;
use AlexanderPoellmann\Shipping\Data\Shipment;

beforeEach(function (): void {
    $this->shipment = new Shipment(
        sender: new Address('Sender', 'Street', '1010', 'Wien', 'at'),
        recipient: new Address('Recipient', 'Street', '4020', 'Linz', 'at'),
        parcels: [new Parcel(1200)],
    );
    $this->transport = FakePlcTransport::responding([
        'ImportShipmentResult' => ['ColloRow' => [
            'ColloCodeList' => ['ColloCode' => ['Code' => '00123', 'NumberTypeID' => 1]],
        ]],
    ]);
    app()->instance(PlcTransport::class, $this->transport);
});

it('implements only the shared capabilities exposed cleanly by PLC', function (): void {
    $adapter = app(PostPlcShippingAdapter::class);

    expect($adapter)->toBeInstanceOf(Carrier::class)
        ->toBeInstanceOf(CreatesShipments::class)
        ->not->toBeInstanceOf(DownloadsLabels::class)
        ->not->toBeInstanceOf(CancelsShipments::class)
        ->and($adapter->carrier())->toBe('post-plc');
});

it('translates a neutral shipment into PLC DTOs and normalizes tracking and PDF label data', function (): void {
    $transport = FakePlcTransport::responding([
        'ImportShipmentResult' => [
            'ColloRow' => [
                'Weight' => 1.2,
                'Length' => 30,
                'Width' => 20,
                'Height' => 10,
                'ColloCodeList' => ['ColloCode' => ['Code' => '003400000000001', 'NumberTypeID' => 1]],
            ],
        ],
        'pdfData' => base64_encode('%PDF-test'),
        'errorCode' => null,
        'errorMessage' => null,
    ]);
    app()->instance(PlcTransport::class, $transport);
    app()->forgetScopedInstances();

    $shipment = new Shipment(
        sender: new Address('Sender GmbH', 'Senderstrasse', '1010', 'Wien', 'AT', houseNumber: '1', email: 'sender@example.com'),
        recipient: new Address('Receiver GmbH', 'Receiverstrasse', '4020', 'Linz', 'AT', houseNumber: '2', phone: '+4312345'),
        parcels: [new Parcel(1200, 300, 200, 100)],
        reference: 'ORDER-42',
        shippingDate: new DateTimeImmutable('2026-09-25 10:30:00'),
    );

    $result = app(PostPlcShippingAdapter::class)
        ->forProduct(PostProductCodes::PaketOesterreich)
        ->createShipment($shipment);

    expect($result->trackingNumbers)->toHaveCount(1)
        ->and((string) $result->trackingNumbers[0])->toBe('003400000000001')
        ->and($result->labels)->toHaveCount(1)
        ->and($result->labels[0]->contents)->toBe('%PDF-test')
        ->and($result->labels[0]->trackingNumber)->toBe($result->trackingNumbers[0])
        ->and($result->labels[0]->mimeType)->toBe('application/pdf');

    expect($transport->calls)->toHaveCount(1)
        ->and($transport->calls[0]['method'])->toBe(ServiceMethods::ImportShipment)
        ->and($transport->calls[0]['payload']['row']['DeliveryServiceThirdPartyID'])->toBe('10')
        ->and($transport->calls[0]['payload']['row']['Number'])->toBe('ORDER-42')
        ->and($transport->calls[0]['payload']['row']['OUShipperReference1'])->toBe('ORDER-42')
        ->and($transport->calls[0]['payload']['row']['ShippingDateTimeFrom'])->toBe('2026-09-25 10:30:00')
        ->and($transport->calls[0]['payload']['row']['OURecipientAddress']['Name1'])->toBe('Receiver GmbH')
        ->and($transport->calls[0]['payload']['row']['ColloList'][0]['Weight'])->toBe(1.2)
        ->and($transport->calls[0]['payload']['row']['ColloList'][0]['Length'])->toBe(30)
        ->and($transport->calls[0]['payload']['row']['ColloList'][0]['Width'])->toBe(20)
        ->and($transport->calls[0]['payload']['row']['ColloList'][0]['Height'])->toBe(10);
});

it('registers the PLC adapter as a tagged concrete service without a global capability binding', function (): void {
    $otherCarrier = new stdClass;
    app()->instance('other.shipping.adapter', $otherCarrier);
    app()->tag(['other.shipping.adapter'], 'shipping.adapters');
    $tagged = iterator_to_array(app()->tagged('shipping.adapters'));

    expect($tagged)->toContain(app(PostPlcShippingAdapter::class))
        ->toContain($otherCarrier)
        ->and(app()->bound(Carrier::class))->toBeFalse()
        ->and(app()->bound(CreatesShipments::class))->toBeFalse()
        ->and(app()->bound(DownloadsLabels::class))->toBeFalse()
        ->and(app()->bound(CancelsShipments::class))->toBeFalse();
});

it('requires an explicit product before contacting PLC', function (): void {
    expect(fn () => app(PostPlcShippingAdapter::class)->createShipment($this->shipment))
        ->toThrow(LogicException::class, 'forProduct()');
    expect($this->transport->calls)->toBeEmpty();
});

it('validates PLC requirements before contacting the transport', function (string $invalid): void {
    $shipment = match ($invalid) {
        'route' => new Shipment($this->shipment->sender, new Address('Recipient', 'Street', '10115', 'Berlin', 'DE'), [new Parcel(1000)]),
        'address' => new Shipment($this->shipment->sender, new Address(str_repeat('x', 101), 'Street', '4020', 'Linz', 'AT'), [new Parcel(1000)]),
        'reference' => new Shipment($this->shipment->sender, $this->shipment->recipient, [new Parcel(1000)], str_repeat('x', 51)),
        default => $this->shipment,
    };
    if ($invalid === 'credentials') {
        config()->set('post-plc.client_id', '');
    }

    expect(fn () => app(PostPlcShippingAdapter::class)->forProduct('10')->createShipment($shipment))
        ->toThrow(ShipmentValidationException::class);
    expect($this->transport->calls)->toBeEmpty();
})->with(['route', 'address', 'reference', 'credentials']);

it('preserves native product codes', function (PostProductCodes|ProductCode|string $product, string $expected): void {
    app(PostPlcShippingAdapter::class)->forProduct($product)->createShipment($this->shipment);

    expect($this->transport->calls[0]['payload']['row']['DeliveryServiceThirdPartyID'])->toBe($expected);
})->with([
    [PostProductCodes::PaketOesterreich, '10'],
    [ProductCode::from('CUSTOM-42'), 'CUSTOM-42'],
    ['01', '01'],
]);

it('rejects invalid native product codes', function (string $product): void {
    expect(fn () => app(PostPlcShippingAdapter::class)->forProduct($product))->toThrow(InvalidArgumentException::class);
    expect($this->transport->calls)->toBeEmpty();
})->with(['', ' CUSTOM ', str_repeat('x', 51)]);

it('copies all shared address fields and rounds dimensions up to whole centimeters', function (): void {
    $address = new Address('Company', 'Street', '1010', 'Wien', 'at', 'Department', '12a', 'Floor 2', 'Jane Doe', '+431234', 'jane@example.com');
    $shipment = new Shipment($address, $address, [new Parcel(1, 301, 202, 103), new Parcel]);

    app(PostPlcShippingAdapter::class)->forProduct('10')->createShipment($shipment);

    $row = $this->transport->calls[0]['payload']['row'];
    foreach (['OUShipperAddress', 'OURecipientAddress'] as $field) {
        expect($row[$field])->toMatchArray([
            'Name1' => 'Company', 'Name2' => 'Department', 'Name3' => 'Jane Doe',
            'AddressLine1' => 'Street', 'HouseNumber' => '12a', 'AddressLine2' => 'Floor 2',
            'PostalCode' => '1010', 'City' => 'Wien', 'CountryID' => 'AT',
            'Tel1' => '+431234', 'Email' => 'jane@example.com',
        ]);
    }
    expect($row['ColloList'][0])->toMatchArray(['Weight' => 0.001, 'Length' => 31, 'Width' => 21, 'Height' => 11])
        ->and($row['ColloList'][1])->not->toHaveKeys(['Weight', 'Length', 'Width', 'Height'])
        ->and($row)->not->toHaveKeys(['Number', 'OUShipperReference1', 'ShippingDateTimeFrom']);
});

it('keeps product and printer configuration immutable', function (): void {
    $base = app(PostPlcShippingAdapter::class);
    $product = $base->forProduct('10');
    $printer = $product->withPrinter(PrinterLanguages::ZPL2, LabelSizes::SHORT, PaperLayouts::SHORT, PrinterEncoding::WINDOWS);
    $printer->forProduct('CUSTOM')->createShipment($this->shipment);
    $printer->createShipment($this->shipment);
    $product->createShipment($this->shipment);

    $products = array_map(fn (array $call): string => $call['payload']['row']['DeliveryServiceThirdPartyID'], $this->transport->calls);
    expect($products)->toBe(['CUSTOM', '10', '10'])
        ->and($this->transport->calls[0]['payload']['row']['PrinterObject'])->toBe([
            'LanguageID' => 'ZPL2', 'LabelFormatID' => '100x150', 'PaperLayoutID' => '100x150', 'Encoding' => 'WINDOWS-1252',
        ])
        ->and($this->transport->calls[2]['payload']['row']['PrinterObject']['LanguageID'])->toBe('PDF');
    expect(fn () => $base->createShipment($this->shipment))->toThrow(LogicException::class);
});

it('rejects image printers which require the native image operation', function (PrinterLanguages $language): void {
    expect(fn () => app(PostPlcShippingAdapter::class)->withPrinter($language))->toThrow(InvalidArgumentException::class, 'ImportShipmentReturnImage');
    expect(fn () => new PostPlcShippingAdapter(app(LaravelPostPlc::class), printerLanguage: $language))->toThrow(InvalidArgumentException::class);
    expect($this->transport->calls)->toBeEmpty();
})->with([PrinterLanguages::JPEG, PrinterLanguages::GIF, PrinterLanguages::PNG]);

it('normalizes combined PDF and ZPL documents without linking a batch to one parcel', function (): void {
    $transport = FakePlcTransport::responding([
        'ImportShipmentResult' => ['ColloRow' => [
            ['ColloCodeList' => ['ColloCode' => [
                ['Code' => ' ', 'NumberTypeID' => 1],
                ['Code' => '00123', 'NumberTypeID' => 1],
                ['Code' => 'ALIAS', 'NumberTypeID' => 2],
            ]]],
            ['ColloCodeList' => null],
        ]],
        'pdfData' => base64_encode('%PDF-batch'),
        'zplLabelData' => '^XA^XZ',
    ]);
    $adapter = new PostPlcShippingAdapter(new LaravelPostPlc(transport: $transport), '10', PrinterLanguages::PDFZPL2);
    $result = $adapter->createShipment(new Shipment($this->shipment->sender, $this->shipment->recipient, [new Parcel(1000), new Parcel(2000)]));

    expect(array_map(strval(...), $result->trackingNumbers))->toBe(['00123'])
        ->and($result->labels)->toHaveCount(2)
        ->and($result->labels[0]->trackingNumber)->toBeNull()
        ->and($result->labels[0]->contents)->toBe('%PDF-batch')
        ->and($result->labels[0]->format)->toBe('pdf')
        ->and($result->labels[1]->trackingNumber)->toBeNull()
        ->and($result->labels[1]->contents)->toBe('^XA^XZ')
        ->and($result->labels[1]->format)->toBe('zpl')
        ->and($result->labels[1]->mimeType)->toBe('application/zpl');
});

it('returns the first usable tracking code per parcel in response order', function (): void {
    $transport = FakePlcTransport::responding([
        'ImportShipmentResult' => ['ColloRow' => [
            ['ColloCodeList' => ['ColloCode' => ['Code' => '0001', 'NumberTypeID' => 1]]],
            ['ColloCodeList' => []],
            ['ColloCodeList' => ['ColloCode' => ['Code' => '0002', 'NumberTypeID' => 1]]],
        ]],
    ]);
    $result = (new PostPlcShippingAdapter(new LaravelPostPlc(transport: $transport), '10'))->createShipment($this->shipment);

    expect(array_map(strval(...), $result->trackingNumbers))->toBe(['0001', '0002'])
        ->and($result->labels)->toBeEmpty();
});

it('allows a printer configuration without labels', function (): void {
    $result = app(PostPlcShippingAdapter::class)->forProduct('10')->withPrinter(PrinterLanguages::None)->createShipment($this->shipment);
    expect($result->labels)->toBeEmpty()
        ->and($result->trackingNumbers)->toHaveCount(1)
        ->and($this->transport->calls[0]['payload']['row']['PrinterObject']['LanguageID'])->toBe('None');
});

it('reports PLC errors before attempting label decoding', function (): void {
    $transport = FakePlcTransport::responding(['errorCode' => '10055', 'errorMessage' => 'Product unavailable', 'pdfData' => '!invalid!']);
    $adapter = new PostPlcShippingAdapter(new LaravelPostPlc(transport: $transport), '10');

    try {
        $adapter->createShipment($this->shipment);
        test()->fail('Expected a PLC exception.');
    } catch (PlcRequestException $exception) {
        expect($exception->method)->toBe(ServiceMethods::ImportShipment)
            ->and($exception->errorCode)->toBe('10055')
            ->and($exception->errorMessage)->toBe('Product unavailable');
    }
    expect($transport->calls)->toHaveCount(1);
});

it('rejects invalid or empty decoded PDF data as a PLC error', function (string $data): void {
    $transport = FakePlcTransport::responding(['pdfData' => $data]);
    $adapter = new PostPlcShippingAdapter(new LaravelPostPlc(transport: $transport), '10');
    expect(fn () => $adapter->createShipment($this->shipment))->toThrow(PlcRequestException::class, 'invalid_label_data');
    expect($transport->calls)->toHaveCount(1);
})->with(['!not-base64!', " \r\n\t"]);

it('recreates the adapter and client when the container scope changes', function (): void {
    $first = app(PostPlcShippingAdapter::class);
    $first->forProduct('10')->createShipment($this->shipment);
    app()->forgetScopedInstances();
    config()->set('post-plc.client_id', 'new-account');
    $second = app(PostPlcShippingAdapter::class);
    $second->forProduct('10')->createShipment($this->shipment);

    expect($second)->not->toBe($first)
        ->and(iterator_to_array(app()->tagged('shipping.adapters')))->toContain($second)
        ->and($this->transport->calls[0]['payload']['row']['ClientID'])->toBe('123456')
        ->and($this->transport->calls[1]['payload']['row']['ClientID'])->toBe('new-account');
});

it('uses the supplied profile for credentials and endpoint without changing the default account', function (): void {
    config()->set('post-plc.profiles.warehouse', [
        'client_id' => 'warehouse-client', 'org_unit_id' => 'warehouse-unit', 'org_unit_guid' => 'warehouse-guid',
        'identifier' => 'Warehouse', 'sandbox' => false,
        'endpoints' => ['production' => 'https://warehouse.example.test/plc'],
    ]);
    $client = app(LaravelPostPlc::class);
    $adapter = new PostPlcShippingAdapter($client->forProfile('warehouse'));
    $adapter->withPrinter(PrinterLanguages::ZPL2)->forProduct('10')->createShipment($this->shipment);
    app(PostPlcShippingAdapter::class)->forProduct('10')->createShipment($this->shipment);

    expect($this->transport->calls[0]['endpoint'])->toBe('https://warehouse.example.test/plc')
        ->and($this->transport->calls[0]['payload']['row'])->toMatchArray([
            'ClientID' => 'warehouse-client', 'OrgUnitID' => 'warehouse-unit', 'OrgUnitGUID' => 'warehouse-guid', 'CustomerProduct' => 'Warehouse',
        ])
        ->and($this->transport->calls[1]['payload']['row']['ClientID'])->toBe('123456');
});

it('rejects customs shipments that need additional native article data', function (): void {
    $shipment = new Shipment(
        new Address('Sender', 'Street', '1010', 'Wien', 'AT', email: 'sender@example.com'),
        new Address('Recipient', 'Street', '8001', 'Zurich', 'CH', email: 'recipient@example.com'),
        [new Parcel(1000)],
    );

    expect(fn () => app(PostPlcShippingAdapter::class)->forProduct('CUSTOM')->createShipment($shipment))
        ->toThrow(ShipmentValidationException::class);
    expect($this->transport->calls)->toBeEmpty();
});

it('propagates SOAP faults without retrying shipment creation', function (): void {
    $fault = new SoapFault('Server', 'PLC unavailable');
    $transport = Mockery::mock(PlcTransport::class);
    $transport->shouldReceive('call')->once()->andThrow($fault);
    $adapter = new PostPlcShippingAdapter(new LaravelPostPlc(transport: $transport), '10');

    expect(fn () => $adapter->createShipment($this->shipment))->toThrow($fault);
});
