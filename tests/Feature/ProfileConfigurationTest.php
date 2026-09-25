<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;

it('creates independent clients from named PLC profiles', function (): void {
    config()->set('post-plc.profiles.store-b', [
        'client_id' => '200',
        'org_unit_id' => '201',
        'org_unit_guid' => 'profile-guid',
        'identifier' => 'store-b',
        'sandbox' => true,
    ]);

    $base = new LaravelPostPlc(
        new PlcConfiguration('base', '100', '101', 'base-guid', false, 'https://plc.invalid', 'https://sandbox.invalid'),
        FakePlcTransport::responding(),
    );
    $profile = $base->forProfile('store-b');

    expect($profile)->not->toBe($base)
        ->and($profile->getClientId())->toBe('200')
        ->and($profile->getOrgUnitGuid())->toBe('profile-guid')
        ->and($profile->getIdentifier())->toBe('store-b')
        ->and($profile->endpoint())->toBe(config('post-plc.endpoints.sandbox'));
});

it('uses the default named profile and rejects missing profiles', function (): void {
    config()->set('post-plc.default_profile', 'warehouse');
    config()->set('post-plc.profiles.warehouse', ['client_id' => 'profile-client', 'sandbox' => false]);

    $configuration = PlcConfiguration::fromConfig();

    expect($configuration->clientId)->toBe('profile-client')
        ->and($configuration->orgUnitId)->toBe('654321')
        ->and($configuration->sandbox)->toBeFalse()
        ->and(fn () => PlcConfiguration::fromConfig('missing'))->toThrow(InvalidArgumentException::class);
});
