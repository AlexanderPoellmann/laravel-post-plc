<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Support\PayloadNormalizer;
use Spatie\LaravelData\DataCollection;

it('serializes data collections nested directly in array requests', function (): void {
    $features = new DataCollection(FeatureRow::class, [FeatureRow::fragile()]);

    expect(PayloadNormalizer::request(['FeatureList' => $features]))
        ->toBe(['FeatureList' => [['ThirdPartyID' => '004']]]);
});

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
