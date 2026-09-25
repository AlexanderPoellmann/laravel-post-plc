<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;

it('serializes Post Express Austria with code 01 and PLC printer defaults', function (): void {
    $recipient = (new Address)
        ->name('Recipient')
        ->street('Rochusmarkt 1')
        ->postCode('1030')
        ->city('Wien')
        ->countryCode('AT')
        ->get();

    $shipment = (new Shipment)
        ->withPrinter()
        ->using(PostProductCodes::PostExpressOesterreich)
        ->to($recipient)
        ->get();

    $array = $shipment->toArray();

    expect($array['DeliveryServiceThirdPartyID'])->toBe('01')
        ->and($shipment->PrinterObject?->LabelFormatID)->toBe(LabelSizes::LONG)
        ->and($shipment->PrinterObject?->PaperLayoutID)->toBe(PaperLayouts::A5T);
});
