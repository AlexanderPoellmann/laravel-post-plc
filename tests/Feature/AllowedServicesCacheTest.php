<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\PlcRequestException;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\FakePlcTransport;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use RicorocksDigitalAgency\Soap\Response\Response;

it('caches allowed services per account and country and supports a fresh lookup', function (): void {
    config()->set('post-plc.capabilities.cache.enabled', true);
    $transport = FakePlcTransport::responding([
        'CarrierServiceRow' => [
            'ThirdPartyID' => '45',
            'Name' => 'Paket Premium International',
            'Contract' => true,
            'OrderID' => 1,
        ],
    ]);
    $client = new LaravelPostPlc(
        new PlcConfiguration('test', '123', '456', 'guid', false, 'https://plc.invalid', 'https://sandbox.invalid'),
        $transport,
    );
    $resolver = new AllowedServicesResolver($client, new Repository(new ArrayStore));

    $resolver->forCountries('DE');
    $resolver->forCountries('DE');
    $resolver->forCountries('DE', fresh: true);

    expect($transport->calls)->toHaveCount(2);
});

it('shares normalized country lookups and supports explicit invalidation', function (): void {
    $transport = FakePlcTransport::responding(['CarrierServiceRow' => ['ThirdPartyID' => '45', 'Contract' => true]]);
    $resolver = new AllowedServicesResolver(new LaravelPostPlc(transport: $transport), new Repository(new ArrayStore));

    $resolver->forCountries([' de ', 'FR', 'DE']);
    $resolver->forCountries(['FR', 'DE']);
    expect($transport->calls)->toHaveCount(1);
    expect($resolver->forget(['DE', 'FR']))->toBeTrue();
    $resolver->forCountries(['FR', 'DE']);
    expect($transport->calls)->toHaveCount(2);
});

it('isolates cached capabilities by account and endpoint', function (): void {
    $cache = new Repository(new ArrayStore);
    $transport = FakePlcTransport::responding(['CarrierServiceRow' => ['ThirdPartyID' => '28', 'Contract' => false]]);
    foreach ([['one', false], ['two', false], ['one', true]] as [$guid, $sandbox]) {
        $client = new LaravelPostPlc(new PlcConfiguration('test', '1', '2', $guid, $sandbox, 'https://plc.invalid', 'https://sandbox.invalid'), $transport);
        (new AllowedServicesResolver($client, $cache))->forCountries('AT');
    }

    expect($transport->calls)->toHaveCount(3);
});

it('uses the configured cache through the container', function (): void {
    $transport = FakePlcTransport::responding(['CarrierServiceRow' => ['ThirdPartyID' => '28', 'Contract' => false]]);
    app()->instance(PlcTransport::class, $transport);
    app()->instance(Illuminate\Contracts\Cache\Repository::class, new Repository(new ArrayStore));
    $resolver = app(AllowedServicesResolver::class);

    $resolver->forCountries('AT');
    $resolver->forCountries('AT');

    expect($transport->calls)->toHaveCount(1);
});

it('refreshes the cached capabilities after an explicit fresh lookup', function (): void {
    $transport = Mockery::mock(PlcTransport::class);
    foreach (['10', '28'] as $code) {
        $transport->shouldReceive('call')->once()->ordered()->andReturn(Response::new([
            'CarrierServiceRow' => ['ThirdPartyID' => $code, 'Contract' => false],
        ]));
    }
    $resolver = new AllowedServicesResolver(new LaravelPostPlc(transport: $transport), new Repository(new ArrayStore));

    expect($resolver->forCountries('AT')->productCodes())->toBe(['10'])
        ->and($resolver->forCountries('AT', fresh: true)->productCodes())->toBe(['28'])
        ->and($resolver->forCountries('AT')->productCodes())->toBe(['28']);
});

it('does not cache PLC capability errors as an empty product catalog', function (): void {
    $transport = Mockery::mock(PlcTransport::class);
    $transport->shouldReceive('call')->once()->ordered()->andReturn(Response::new([
        'errorCode' => 'SN#10015', 'errorMessage' => 'Service unavailable',
    ]));
    $transport->shouldReceive('call')->once()->ordered()->andReturn(Response::new([
        'CarrierServiceRow' => ['ThirdPartyID' => '28', 'Contract' => false],
    ]));
    $resolver = new AllowedServicesResolver(new LaravelPostPlc(transport: $transport), new Repository(new ArrayStore));

    expect(fn () => $resolver->forCountries('AT'))->toThrow(PlcRequestException::class, 'SN#10015');
    expect($resolver->forCountries('AT')->productCodes())->toBe(['28']);
});
