<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Policies\ServicePolicy;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServices;

it('layers merchant enablement and feature restrictions over PLC capabilities', function (): void {
    $services = AllowedServices::fromResponse([
        'CarrierServiceRow' => [
            [
                'ThirdPartyID' => '10',
                'Name' => 'Paket Österreich',
                'Contract' => false,
                'OrderID' => 1,
                'FeatureList' => [
                    ['ThirdPartyID' => '004', 'Name' => 'Zerbrechlich'],
                    ['ThirdPartyID' => '006', 'Name' => 'Nachnahme'],
                ],
            ],
            [
                'ThirdPartyID' => '65',
                'Name' => 'Next Day',
                'Contract' => true,
                'OrderID' => 2,
            ],
        ],
    ]);

    $policy = new ServicePolicy(
        enabledProducts: ['10'],
        disabledFeatures: ['10' => ['006']],
        preferredProducts: ['AT' => '10'],
    );

    expect($policy->allowsProduct($services, '10'))->toBeTrue()
        ->and($policy->allowsProduct($services, '65'))->toBeFalse()
        ->and($policy->allowsFeature($services, '10', '004'))->toBeTrue()
        ->and($policy->allowsFeature($services, '10', '006'))->toBeFalse()
        ->and($policy->preferredProduct($services, 'AT')?->value)->toBe('10');
});

it('normalizes product and feature aliases in merchant restrictions', function (): void {
    $services = AllowedServices::fromResponse([
        'ThirdPartyID' => '01', 'Contract' => false,
        'FeatureList' => [['ThirdPartyID' => '006']],
    ]);
    $policy = new ServicePolicy(enabledProducts: ['1'], disabledFeatures: ['1' => ['6']]);

    expect($policy->allowsProduct($services, '01'))->toBeTrue()
        ->and($policy->allowsFeature($services, '01', '006'))->toBeFalse()
        ->and((new ServicePolicy(disabledProducts: ['1']))->allowsProduct($services, '01'))->toBeFalse();
});

it('uses the fallback when a preferred product is unavailable', function (): void {
    $services = AllowedServices::fromResponse(['ThirdPartyID' => '10', 'Contract' => false]);
    $policy = new ServicePolicy(preferredProducts: ['AT' => '01'], fallbackProduct: '10');

    expect($policy->preferredProduct($services, 'AT')?->value)->toBe('10');
});
