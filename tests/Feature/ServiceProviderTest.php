<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Contracts\CustomsRequirementResolver;
use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Facades\LaravelPostPlc as PlcFacade;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Policies\ServicePolicy;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnLabelService;
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnShipmentFactory;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Validation\PickupOrderValidator;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

it('shares the client within a scope and discards response state between scopes', function (): void {
    app()->instance(PlcTransport::class, FakePlcTransport::responding(['pdfData' => 'private-label']));
    $client = app(LaravelPostPlc::class);
    $resolver = app(AllowedServicesResolver::class);
    $policy = app(ServicePolicy::class);

    PlcFacade::request(ServiceMethods::ImportShipment, []);

    expect(PlcFacade::getResponse())->toBe($client->getResponse())
        ->and(app(LaravelPostPlc::class))->toBe($client);

    app()->forgetScopedInstances();

    expect(app(LaravelPostPlc::class))->not->toBe($client)
        ->and(app(AllowedServicesResolver::class))->not->toBe($resolver)
        ->and(app(ServicePolicy::class))->not->toBe($policy)
        ->and(PlcFacade::getResponse())->toBeNull()
        ->and(PlcFacade::lastMethod())->toBeNull();
});

it('loads package config and registers package services', function (): void {
    expect(config('post-plc.endpoints.production'))->toBeString()
        ->and(app(LaravelPostPlc::class))->toBeInstanceOf(LaravelPostPlc::class)
        ->and(app(PlcTransport::class))->toBeInstanceOf(PlcTransport::class)
        ->and(app(CustomsRequirementResolver::class))->toBeInstanceOf(CustomsRequirementResolver::class)
        ->and(app(ShipmentValidator::class))->toBeInstanceOf(ShipmentValidator::class)
        ->and(app(PickupOrderValidator::class))->toBeInstanceOf(PickupOrderValidator::class)
        ->and(app(AllowedServicesResolver::class))->toBeInstanceOf(AllowedServicesResolver::class)
        ->and(app(ServicePolicy::class))->toBeInstanceOf(ServicePolicy::class)
        ->and(app(ReturnShipmentFactory::class))->toBeInstanceOf(ReturnShipmentFactory::class)
        ->and(app(ReturnLabelService::class))->toBeInstanceOf(ReturnLabelService::class);
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
