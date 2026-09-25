<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Collo;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloArticleRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\Units;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServices;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\ShipmentFixtures;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

it('accepts a valid domestic shipment', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketOesterreich)
        ->from(ShipmentFixtures::address('AT'))
        ->to(ShipmentFixtures::address('AT'))
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->isValid())->toBeTrue();
});

it('rejects deterministic domestic products for foreign destinations', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketOesterreich)
        ->to(ShipmentFixtures::address('DE'))
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->has('10056'))->toBeTrue();
});

it('can validate product and feature availability against live allowed-services data', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketOesterreich)
        ->to(ShipmentFixtures::address('AT'))
        ->withFeatures([FeatureRow::fragile()])
        ->get();

    $allowed = AllowedServices::fromResponse([
        'CarrierServiceRow' => [
            'ThirdPartyID' => '10',
            'Contract' => true,
            'OrderID' => 1,
            'FeatureList' => [
                'AdditionalInformationResult' => [
                    ['ThirdPartyID' => '045', 'Name' => 'Personal delivery'],
                ],
            ],
        ],
    ]);

    expect(app(ShipmentValidator::class)->validate($shipment, $allowed)->has('10081'))->toBeTrue();
});

it('requires contact data for preferred pickup stations', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketOesterreich)
        ->to(ShipmentFixtures::address('AT'))
        ->withFeatures([FeatureRow::preferredPickupStation('123')])
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->has('10032'))->toBeTrue();
});

it('rejects incompatible station features', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketOesterreich)
        ->to(ShipmentFixtures::address('AT', true))
        ->withFeatures([
            FeatureRow::preferredPickupStation('123'),
            FeatureRow::personalDelivery(),
            FeatureRow::cashOnDelivery(12, 'EUR', 'AT001', 'BIC', 'Acme', 'Order'),
        ])
        ->get();

    $result = app(ShipmentValidator::class)->validate($shipment);

    expect($result->has('10051'))->toBeTrue()
        ->and($result->has('10052'))->toBeTrue();
});

it('applies the Germany branch-key exception', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::NextDay)
        ->to(ShipmentFixtures::address('DE', true))
        ->withFeatures([FeatureRow::preferredPickupBranch('123')])
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->has('feature.germany_branch_key'))->toBeTrue();
});

it('requires both phone and email for Next Day', function (): void {
    $recipient = (new Address)
        ->name('Recipient')
        ->street('Main Street 1')
        ->postCode('10115')
        ->city('Berlin')
        ->countryCode('DE')
        ->phone('+491234567')
        ->get();

    $shipment = (new Shipment)
        ->using(PostProductCodes::NextDay)
        ->to($recipient)
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->has('10070'))->toBeTrue();
});

it('requires positive weights for PLC products that mandate them', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketPremiumInternational)
        ->to(ShipmentFixtures::address('DE'))
        ->parcels([(new Collo)->get()])
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->has('shipment.weight_required'))->toBeTrue();
});

it('validates customs contacts, article completeness and HS tariff numbers', function (): void {
    $article = ColloArticleRow::goods(
        description: 'T-Shirt',
        quantity: 1,
        unit: Units::Stueck,
        hsTariffNumber: 'ABC',
        countryOfOrigin: 'AT',
        valuePerUnit: 20,
        currency: 'EUR',
        netWeight: 0.2,
    );

    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketPremiumInternational)
        ->from(ShipmentFixtures::address('AT'))
        ->to(ShipmentFixtures::address('CH'))
        ->parcels([(new Collo)->weight(0.3)->articles([$article])->get()])
        ->get();

    $result = app(ShipmentValidator::class)->validate($shipment);

    expect($result->has('10074'))->toBeTrue()
        ->and($result->has('10075'))->toBeTrue()
        ->and($result->has('10079'))->toBeTrue();
});

it('accepts document-only customs articles without goods-only fields', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketPremiumInternational)
        ->from(ShipmentFixtures::address('AT', true))
        ->to(ShipmentFixtures::address('CH', true))
        ->parcels([
            (new Collo)->weight(0.2)->articles([
                ColloArticleRow::documents('Contract documents'),
            ])->get(),
        ])
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->isValid())->toBeTrue();
});

it('requires a single customs currency across all goods articles', function (): void {
    $shipment = (new Shipment)
        ->using(PostProductCodes::PaketPremiumInternational)
        ->from(ShipmentFixtures::address('AT', true))
        ->to(ShipmentFixtures::address('CH', true))
        ->parcels([
            (new Collo)->weight(1)->articles([
                ColloArticleRow::goods('One', 1, Units::Stueck, '610910', 'AT', 20, 'EUR', 0.2),
                ColloArticleRow::goods('Two', 1, Units::Stueck, '610910', 'AT', 20, 'CHF', 0.2),
            ])->get(),
        ])
        ->get();

    expect(app(ShipmentValidator::class)->validate($shipment)->has('10077'))->toBeTrue();
});
