# Laravel Post PLC

A Laravel integration for the Austrian Post Label Center (Post Label Center / PLC), built on Spatie's Laravel package conventions.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alexanderpoellmann/laravel-post-plc.svg?style=flat-square)](https://packagist.org/packages/alexanderpoellmann/laravel-post-plc)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alexanderpoellmann/laravel-post-plc/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/alexanderpoellmann/laravel-post-plc/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/alexanderpoellmann/laravel-post-plc/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/alexanderpoellmann/laravel-post-plc/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alexanderpoellmann/laravel-post-plc.svg?style=flat-square)](https://packagist.org/packages/alexanderpoellmann/laravel-post-plc)

## PLC documentation

The official API description and example requests, responses, and labels can be downloaded from Austrian Post's [Post Label Center documentation downloads](https://www.post.at/g/c/post-labelcenter-dokumente).

## Installation

```bash
composer require alexanderpoellmann/laravel-post-plc
```

Publish the configuration when you want to customize endpoints or customs-country handling:

```bash
php artisan vendor:publish --tag="laravel-post-plc-config"
```

Or alternatively, add the following entry to your `config/services.php` file:

```php
    'post-plc' => [
        'client-id'     => env('PLC_CLIENT_ID'),
        'org-unit-id'   => env('PLC_ORG_UNIT_ID'),
        'org-unit-guid' => env('PLC_ORG_UNIT_GUID'),
        'sandbox'       => env('PLC_SANDBOX', false),
    ],
```

Configure credentials in `.env`:

```dotenv
PLC_CLIENT_ID=
PLC_ORG_UNIT_ID=
PLC_ORG_UNIT_GUID=
PLC_IDENTIFIER="My Application"
PLC_SANDBOX=true
```

## Build and import a shipment

```php
use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Collo;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Facades\LaravelPostPlc;

$from = (new Address())
    ->id('SHIPPER-1')
    ->name('Absender GmbH')
    ->street('Rochusmarkt 5')
    ->postCode('1030')
    ->city('Wien')
    ->countryCode('AT')
    ->phone('+431234567')
    ->email('shipping@example.com')
    ->get();

$to = (new Address())
    ->id('RECIPIENT-1')
    ->name('Musterfirma GmbH', 'c/o Maria Muster')
    ->street('Landesgerichtsstraße 1')
    ->postCode('1010')
    ->city('Wien')
    ->countryCode('AT')
    ->email('recipient@example.com')
    ->get();

$shipment = (new Shipment())
    ->withPrinter()
    ->withNumber('ORDER-12345')
    ->using(PostProductCodes::PaketPremiumOesterreichB2B)
    ->from($from)
    ->to($to)
    ->withFeatures([
        FeatureRow::cashOnDelivery(
            amount: 199.99,
            currency: 'EUR',
            iban: 'AT000000000000000000',
            bic: 'BICCODE',
            accountHolder: 'Muster GmbH',
            paymentReference: 'ORDER-12345',
        ),
    ])
    ->parcels([
        (new Collo())->weight(0.4)->get(),
    ])
    ->get();

LaravelPostPlc::call(ServiceMethods::ImportShipment, $shipment, as_row: true);

$result = LaravelPostPlc::toCollection();
```

`withPrinter()` uses the PLC specification defaults (`100x200`, `2xA5inA4`, PDF/UTF) unless you override them.

## Validate before sending

PLC has a number of cross-field rules that are difficult to express in DTO types alone. `ShipmentValidator` performs deterministic preflight validation and returns all detected incompatibilities at once.

```php
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

$validation = app(ShipmentValidator::class)->validate($shipment);

$validation->throwIfInvalid();
```

Examples covered by local validation include:

- domestic/international product scope where PLC defines it unambiguously;
- sender/recipient address shape and documented field lengths;
- preferred branch/station contact requirements;
- incompatible personal-delivery / COD / pickup-station combinations;
- Next Day phone + email requirements;
- product weight requirements;
- return-day consistency;
- COD/insured-value amounts and currency fields;
- German branch/station/poste-restante special handling;
- customs contacts, article completeness, HS tariff format and single-currency rules;
- sender/recipient Austria relationship rules documented by PLC.

Each error has a machine-friendly `code`, a `path`, and a human-readable `message`. Where a PLC error code maps cleanly to the preflight rule, that PLC code is used.

`AddressValidator` contains the shared address rules. You can use it directly with `app(AddressValidator::class)->validate($address)`; both `ShipmentValidator` and `PickupOrderValidator` also apply these rules to their addresses.

## Resolve customer-specific allowed products and features

Some compatibility rules depend on the PLC contract/customer configuration and cannot be reliably hard-coded. Query PLC first and combine that result with the local validator:

```php
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

$allowed = app(AllowedServicesResolver::class)->forShipment($shipment);

$validation = app(ShipmentValidator::class)->validate($shipment, $allowed);
$validation->throwIfInvalid();
```

`AllowedServicesResolver` uses PLC's `GetAllowedServicesForCountry` service. The actual shipment import remains authoritative for postcode-level, contract-level, and other server-side rules that are not exposed by product discovery.

Omit the allowed-services argument (or pass `null`) to run only local checks. An explicitly supplied empty discovery result allows no products and produces validation error `10055`. Discovery rejects malformed country codes before sending a request.

## Customs articles

Use the customs factories to make the distinction between document-only and goods declarations explicit:

```php
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloArticleRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\Units;

$documents = ColloArticleRow::documents('Contract documents');

$goods = ColloArticleRow::goods(
    description: 'T-Shirt',
    quantity: 2,
    unit: Units::Stueck,
    hsTariffNumber: '610910',
    countryOfOrigin: 'AT',
    valuePerUnit: 24.90,
    currency: 'EUR',
    netWeight: 0.2,
);
```

The default customs resolver uses country-level EU membership. Customs territories can differ from ISO-country boundaries, so applications with territory-specific routing can replace the `CustomsRequirementResolver` binding.

## Other PLC operations

Typed request DTOs are included for the remaining documented PLC methods, including address import, end-of-day operations, shipment cancellation, allowed-service discovery and pickup-order operations.

```php
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests\CancelPickupOrderRequest;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;

$request = new CancelPickupOrderRequest(
    clientID: LaravelPostPlc::getClientId(),
    orgUnitID: LaravelPostPlc::getOrgUnitId(),
    orgUnitGuid: LaravelPostPlc::getOrgUnitGuid(),
    pickupOrderNumber: 'PO-123',
);

$response = LaravelPostPlc::request(ServiceMethods::CancelPickupOrder, $request);
$data = LaravelPostPlc::toArray();
```

For custom response DTOs, use `LaravelPostPlc::toData(YourData::class)`. `toObject()` remains available for the shipment import methods with built-in response DTOs.

The client retains only the most recent call's response within the current Laravel request or queue job. Starting another call clears the previous response, including when serialization or transport fails. After a failed call, `getResponse()` returns `null`, `toArray()` returns an empty array, and `toObject()` throws a `LogicException`. Transport exceptions propagate to the caller.

Array requests may contain nested Laravel collections and Spatie data collections. Normalization preserves their contents, removes null request values, and keeps list indexes consecutive. Response normalization retains null values.

## Product and feature helpers

`PostProductCodes::apiValue()` returns the exact PLC identifier. This matters for Post Express Österreich, whose PLC code is `01` rather than integer `1`.

```php
PostProductCodes::PostExpressOesterreich->apiValue(); // "01"
PostProductCodes::PaketPremiumInternational->requiresWeight();
PostProductCodes::PaketPremiumOesterreichB2B->forBusinessOnly();
```

Feature helpers cover the documented additional services:

```php
FeatureRow::fragile();
FeatureRow::personalDelivery();
FeatureRow::preferredPickupStation('12345');
FeatureRow::postBox('12345', '42');
FeatureRow::preferredNeighbor('Maria Muster', 'Musterstraße 1');
```

## Testing and quality

```bash
composer test
composer analyse
composer format:test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for changes.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
