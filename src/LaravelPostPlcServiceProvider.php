<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc;

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Contracts\CustomsRequirementResolver;
use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\EuCustomsRequirementResolver;
use AlexanderPoellmann\LaravelPostPlc\Transport\SoapPlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Validation\PickupOrderValidator;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelPostPlcServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-post-plc')
            ->hasConfigFile('post-plc');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(PlcTransport::class, SoapPlcTransport::class);
        $this->app->singleton(CustomsRequirementResolver::class, EuCustomsRequirementResolver::class);

        $this->app->singleton(LaravelPostPlc::class, fn ($app): LaravelPostPlc => new LaravelPostPlc(
            configuration: PlcConfiguration::fromConfig(),
            transport: $app->make(PlcTransport::class),
        ));

        $this->app->singleton(PickupOrderValidator::class);

        $this->app->singleton(ShipmentValidator::class, fn ($app): ShipmentValidator => new ShipmentValidator(
            $app->make(CustomsRequirementResolver::class),
        ));

        $this->app->singleton(AllowedServicesResolver::class, fn ($app): AllowedServicesResolver => new AllowedServicesResolver(
            $app->make(LaravelPostPlc::class),
        ));
    }
}
