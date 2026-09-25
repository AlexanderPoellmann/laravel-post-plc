<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\Features;

it('builds a PO box feature with the correct PLC feature code', function (): void {
    $feature = FeatureRow::postBox('00123', '42');

    expect($feature->ThirdPartyID)->toBe(Features::POBox)
        ->and($feature->Value1)->toBe('00123')
        ->and($feature->Value2)->toBe('42');
});

it('normalizes cash on delivery helper values', function (): void {
    $feature = FeatureRow::cashOnDelivery(19.9, 'eur', 'AT001', 'BIC', 'Acme GmbH', 'Order 42');

    expect($feature->ThirdPartyID)->toBe(Features::CashOnDelivery)
        ->and($feature->Value1)->toBe('19.9')
        ->and($feature->Value2)->toBe('EUR')
        ->and($feature->Value3)->toBe('AT001|BIC|Acme GmbH')
        ->and($feature->Value4)->toBe('Order 42');
});
