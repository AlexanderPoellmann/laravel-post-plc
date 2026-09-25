<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;

it('covers the PLC API v2 product catalog while retaining legacy products', function (): void {
    $documented = [
        '89', '90', '49', '47', '56', '96', '16', '65', '70', '10', '45', '31', '30', '46', '01', '28', '63', '04', '66', '64', '97', '15',
    ];

    $known = array_map(static fn (PostProductCodes $product): string => $product->apiValue(), PostProductCodes::cases());

    expect(array_diff($documented, $known))->toBe([])
        ->and(PostProductCodes::RetourpaketInternationalAbgabeAusland->apiValue())->toBe('04')
        ->and(PostProductCodes::PremiumLight->isLegacy())->toBeTrue();
});

it('covers all 49 PLC API v2 feature codes', function (): void {
    expect(Features::cases())->toHaveCount(49)
        ->and(Features::Eco->value)->toBe('202')
        ->and(Features::SignatureRequired->value)->toBe('187');
});

it('exposes all current PLC API v2 service methods plus the legacy pickup method', function (): void {
    $currentMethods = [
        'ImportShipment',
        'ImportShipmentAndGenerateBarcode',
        'ImportShipmentReturnImage',
        'ImportShipmentForce',
        'ImportAddress',
        'PerformEndOfDay',
        'PerformEndOfDaySelect',
        'CancelShipments',
        'GetAllowedServicesForCountry',
        'GetAvailableTimeWindowsForPickupOrder',
        'ImportPickupOrderBusiness',
        'CancelPickupOrder',
        'BuildGroupageShipment',
        'CompleteGroupageShipment',
    ];

    $known = array_map(static fn (ServiceMethods $method): string => $method->value, ServiceMethods::cases());

    expect(array_diff($currentMethods, $known))->toBe([])
        ->and(ServiceMethods::ImportPickupOrderBusiness->value)->toBe('ImportPickupOrderBusiness');
});

it('accepts forward compatible raw product and feature codes', function (): void {
    expect(ProductCode::from('NEW-PRODUCT')->known())->toBeNull()
        ->and((string) ProductCode::from('NEW-PRODUCT'))->toBe('NEW-PRODUCT')
        ->and(FeatureCode::from('999')->known())->toBeNull()
        ->and((string) FeatureCode::from('999'))->toBe('999')
        ->and((string) ProductCode::from(1))->toBe('01')
        ->and((string) FeatureCode::from(4))->toBe('004');
});
