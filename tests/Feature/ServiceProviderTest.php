<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Contracts\CustomsRequirementResolver;
use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;
use AlexanderPoellmann\LaravelPostPlc\Validation\PickupOrderValidator;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

it('loads package config and registers package services', function (): void {
    expect(config('post-plc.endpoints.production'))->toBeString()
        ->and(app(LaravelPostPlc::class))->toBeInstanceOf(LaravelPostPlc::class)
        ->and(app(PlcTransport::class))->toBeInstanceOf(PlcTransport::class)
        ->and(app(CustomsRequirementResolver::class))->toBeInstanceOf(CustomsRequirementResolver::class)
        ->and(app(ShipmentValidator::class))->toBeInstanceOf(ShipmentValidator::class)
        ->and(app(PickupOrderValidator::class))->toBeInstanceOf(PickupOrderValidator::class)
        ->and(app(AllowedServicesResolver::class))->toBeInstanceOf(AllowedServicesResolver::class);
});

it('keeps the legacy services configuration as a fallback', function (): void {
    config()->set('post-plc.client_id', null);
    config()->set('post-plc.org_unit_id', null);
    config()->set('post-plc.org_unit_guid', null);
    config()->set('post-plc.identifier', null);
    config()->set('post-plc.sandbox', null);
    config()->set('services.post-plc.client-id', 'legacy-client');
    config()->set('services.post-plc.org-unit-id', 'legacy-unit');
    config()->set('services.post-plc.org-unit-guid', 'legacy-guid');
    config()->set('services.post-plc.identifier', 'legacy-app');
    config()->set('services.post-plc.sandbox', true);

    $configuration = PlcConfiguration::fromConfig();

    expect($configuration->clientId)->toBe('legacy-client')
        ->and($configuration->orgUnitId)->toBe('legacy-unit')
        ->and($configuration->orgUnitGuid)->toBe('legacy-guid')
        ->and($configuration->identifier)->toBe('legacy-app')
        ->and($configuration->sandbox)->toBeTrue();
});
