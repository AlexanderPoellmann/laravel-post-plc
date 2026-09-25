<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServices;

it('returns product and feature codes as strings', function (): void {
    $allowed = AllowedServices::fromResponse([
        'CarrierServiceRow' => [
            ['ThirdPartyID' => '01', 'Contract' => true],
            [
                'ThirdPartyID' => '10',
                'Contract' => true,
                'FeatureList' => [
                    ['ThirdPartyID' => '004'],
                    ['ThirdPartyID' => '116'],
                ],
            ],
        ],
    ]);

    expect($allowed->productCodes())->toBe(['01', '10'])
        ->and($allowed->featureCodesFor(PostProductCodes::PaketOesterreich))->toBe(['004', '116']);
});

it('normalizes nested allowed service responses', function (): void {
    $allowed = AllowedServices::fromResponse([
        'GetAllowedServicesForCountryResult' => [
            'CarrierServiceRow' => [
                'ThirdPartyID' => '01',
                'Name' => 'Post Express Österreich',
                'Contract' => true,
                'OrderID' => 1,
                'FeatureList' => [
                    'AdditionalInformationResult' => [
                        ['ThirdPartyID' => '004', 'Name' => 'Fragile'],
                        ['ThirdPartyID' => '045', 'Name' => 'Personal delivery'],
                    ],
                ],
            ],
        ],
    ]);

    expect($allowed->allowsProduct(PostProductCodes::PostExpressOesterreich))->toBeTrue()
        ->and($allowed->allowsFeature(PostProductCodes::PostExpressOesterreich, Features::Fragile))->toBeTrue()
        ->and($allowed->allowsFeature(PostProductCodes::PostExpressOesterreich, Features::CashOnDelivery))->toBeFalse();
});
