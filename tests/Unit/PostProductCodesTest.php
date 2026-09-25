<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;

it('preserves the leading zero of Post Express Austria', function (): void {
    expect(PostProductCodes::PostExpressOesterreich->apiValue())->toBe('01')
        ->and(PostProductCodes::fromApiValue('01'))->toBe(PostProductCodes::PostExpressOesterreich)
        ->and(PostProductCodes::fromApiValue(1))->toBe(PostProductCodes::PostExpressOesterreich);
});

it('applies only deterministic destination scopes locally', function (): void {
    expect(PostProductCodes::PaketOesterreich->isAvailableForDestination('AT'))->toBeTrue()
        ->and(PostProductCodes::PaketOesterreich->isAvailableForDestination('DE'))->toBeFalse()
        ->and(PostProductCodes::PaketPremiumInternational->isAvailableForDestination('DE'))->toBeTrue()
        ->and(PostProductCodes::PaketPremiumInternational->isAvailableForDestination('AT'))->toBeFalse()
        ->and(PostProductCodes::NextDay->destinationScope())->toBeNull();
});
