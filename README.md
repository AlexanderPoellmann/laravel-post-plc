# A laravel integration for the Austrian Post Label Center (Österreichische Post).

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alexanderpoellmann/laravel-post-plc.svg?style=flat-square)](https://packagist.org/packages/alexanderpoellmann/laravel-post-plc)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alexanderpoellmann/laravel-post-plc/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/alexanderpoellmann/laravel-post-plc/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/alexanderpoellmann/laravel-post-plc/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/alexanderpoellmann/laravel-post-plc/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alexanderpoellmann/laravel-post-plc.svg?style=flat-square)](https://packagist.org/packages/alexanderpoellmann/laravel-post-plc)

## Installation

You can install the package via composer:

```bash
composer require alexanderpoellmann/laravel-post-plc
```

Add the following entry to your `config/services.php` file:

```php
    'post-plc' => [
        'client-id'     => env('PLC_CLIENT_ID'),
        'org-unit-id'   => env('PLC_ORG_UNIT_ID'),
        'org-unit-guid' => env('PLC_ORG_UNIT_GUID'),
        'sandbox'       => env('PLC_SANDBOX', false),
    ],
```

## Usage

```php
use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Collo;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\Facades\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;

$from = (new Address())
    ->id(sprintf('%05d', mt_rand(1, 10000)))
    ->name('Absender GmbH')
    ->route('Rochusmarkt')
    ->street_number('5')
    ->post_code('1030')
    ->city('Wien')
    ->country_code('AT')
    ->get();

$to = (new Address())
    ->id(sprintf('%05d', mt_rand(1, 10000)))
    ->name('Musterfirma GmbH', 'c/o Frau Maria Muster')
    ->route('Landesgerichtsstraße')
    ->street_number('1')
    ->post_code('1010')
    ->city('Wien')
    ->country_code('AT')
    ->get();

$shipment = (new Shipment())
    ->withPrinter()
    ->withNumber(sprintf('%05d', mt_rand(1, 10000)))
    ->using(PostProductCodes::PaketPremiumOesterreichB2B)
    ->from($from)
    ->to($to)
    ->withFeatures([
        FeatureRow::from([
            'ThirdPartyID' => Features::CashOnDelivery,
            'Value1' => '199.99', // Amount (decimal)
            'Value2' => 'EUR',    // Currency (ISO code)
            'Value3' => 'AT99 9999 9999 9999 9999|BICCODE|Muster GmbH', // IBAN|BIC|Account holder
            'Value4' => 'Order #12345', // Payment reference
        ]),
    ])
    ->parcels([
        (new Collo)->weight(0.4)->get(),
        (new Collo)->weight(5.2)->get(),
    ])->get();

LaravelPostPlc::call(ServiceMethods::ImportShipment, $shipment, true);

$object = LaravelPostPlc::toCollection();

ray($object);
```

The `PostProductCodes` enum has a few helper methods to make it easier to decide which options you might show to your users, when creating shipments:

```php
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;

// check whether the selected product is available for Austrian addresses only
$isDomestic = PostProductCodes::PaketOesterreich->isDomestic();

// check whether the selected product requires you to specify its weight
$requiresWeight = PostProductCodes::PaketPremiumInternational->requiresWeight();

// check whether the selected product is available only for business-to-business shipments
$forBusinessOnly = PostProductCodes::PaketPremiumOesterreichB2B->forBusinessOnly();
```

The `FeatureRow` enum has a few helper methods as well, here are some examples:

```php
FeatureRow::cashOnDelivery(
    amount: '199.99',
    currency: 'EUR',
    iban: 'AT99 9999 9999 9999 9999',
    bic: 'ABCDEFFXXX',
    accountHolder: 'Muster GmbH',
    paymentReference: 'Order #12345',
]);

FeatureRow::fragile()

FeatureRow::personalDelivery()
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alexander Manfred Pöllmann](https://github.com/AlexanderPoellmann)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
