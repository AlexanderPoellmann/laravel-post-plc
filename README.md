# Laravel Post PLC

A Laravel integration for the Austrian Post Label Center (Post Label Center / PLC), built on Spatie's Laravel package conventions.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alexanderpoellmann/laravel-post-plc.svg?style=flat-square)](https://packagist.org/packages/alexanderpoellmann/laravel-post-plc)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alexanderpoellmann/laravel-post-plc/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/alexanderpoellmann/laravel-post-plc/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/alexanderpoellmann/laravel-post-plc/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/alexanderpoellmann/laravel-post-plc/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alexanderpoellmann/laravel-post-plc.svg?style=flat-square)](https://packagist.org/packages/alexanderpoellmann/laravel-post-plc)

## PLC documentation

The official API description and example requests, responses, and labels can be downloaded from Austrian Post's [Post Label Center documentation downloads](https://www.post.at/g/c/post-labelcenter-dokumente).

The package models the current PLC API v2.0 contract (26 February 2025), including all 14 documented service methods, 22 current products and 49 additional-service codes. Legacy product constants from earlier PLC documentation remain available for existing customer contracts.

Austrian Post PLC documentation: https://www.post.at/g/c/post-labelcenter-dokumente

## Installation

```bash
composer require alexanderpoellmann/laravel-post-plc
```

Publish the configuration when you want to customize endpoints, capability caching, product policy or customs-country handling:

```bash
php artisan vendor:publish --tag="laravel-post-plc-config"
```

Configure the default PLC account in `.env`:

```dotenv
PLC_CLIENT_ID=
PLC_ORG_UNIT_ID=
PLC_ORG_UNIT_GUID=
PLC_IDENTIFIER="My Application"
PLC_SANDBOX=true
```

The legacy `services.post-plc` configuration remains supported as a fallback.

## Shared shipping contracts

`PostPlcShippingAdapter` implements `Carrier` and `CreatesShipments` from `alexanderpoellmann/shipping-contracts`. Its carrier identifier is `post-plc`. Select the Austrian Post product on the adapter; the shared shipment contains only addresses, parcels, an optional reference and an optional shipping date.

```php
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Shipping\PostPlcShippingAdapter;
use AlexanderPoellmann\Shipping\Data\Address;
use AlexanderPoellmann\Shipping\Data\Parcel;
use AlexanderPoellmann\Shipping\Data\Shipment;

$adapter = app(PostPlcShippingAdapter::class)
    ->forProduct(PostProductCodes::PaketOesterreich);

$result = $adapter->createShipment(new Shipment(
    sender: new Address('Sender GmbH', 'Rochusmarkt', '1030', 'Wien', 'AT', houseNumber: '5'),
    recipient: new Address('Recipient GmbH', 'Hauptplatz', '4020', 'Linz', 'AT', houseNumber: '1'),
    parcels: [new Parcel(weightInGrams: 1200, lengthInMillimeters: 300, widthInMillimeters: 200, heightInMillimeters: 100)],
    reference: 'ORDER-42',
));

foreach ($result->trackingNumbers as $trackingNumber) {
    $trackingNumber->value; // String, preserving leading zeros.
}

foreach ($result->labels as $label) {
    $label->contents; // Decoded PDF bytes or raw ZPL text.
    $label->mimeType;
    $label->trackingNumber; // Null for a document covering multiple parcels.
}
```

`forProduct()` also accepts a native `ProductCode` or string. Both `forProduct()` and `withPrinter()` return new adapters, so a configured instance can be reused without changing the container's default adapter. Printer configuration remains PLC-specific:

```php
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;

$thermal = $adapter->withPrinter(
    language: PrinterLanguages::ZPL2,
    labelSize: LabelSizes::SHORT,
    paperLayout: PaperLayouts::SHORT,
);
```

The adapter supports PDF, ZPL2, PDFZPL2 and None through `ImportShipment`. JPEG, GIF and PNG require the native `ImportShipmentReturnImage` workflow and are rejected by this adapter. PDF is decoded from PLC's base64 response; ZPL remains raw text. A combined response returns both documents. The first nonblank collo code for each returned parcel becomes its tracking number; additional carrier codes remain available through the native response DTO. Documents are associated with a tracking number only when the response contains exactly one parcel and one usable code.

Weights are converted from grams to kilograms. Dimensions are rounded up from millimeters to whole centimeters. The shared reference maps to both PLC `Number` and `OUShipperReference1`, so it must satisfy the 50-character `Number` limit. The shipping date maps to `ShippingDateTimeFrom` using the supplied date's local time.

The adapter runs `ShipmentValidator` before sending and throws `ShipmentValidationException` for invalid PLC input. A missing product throws `LogicException`; invalid product or printer configuration throws `InvalidArgumentException`. PLC error responses and invalid PDF encoding throw `PlcRequestException`; transport exceptions propagate. Creation is not automatically retried. Use the native shipment API below for customs articles, additional services, return settings and other PLC fields that the shared shipment cannot express.

Laravel registers the concrete adapter as a scoped service under the shared `shipping.adapters` tag. It does not bind `Carrier` or any capability interface globally, so applications can discover both Post and DPD adapters and select the needed concrete service:

```php
foreach (app()->tagged('shipping.adapters') as $carrier) {
    $carrier->carrier();
}
```

The tagged Post adapter still needs `forProduct()` before use. For a named PLC account, construct it with `new PostPlcShippingAdapter(app(\AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc::class)->forProfile('store-b'))`.

The Post adapter does not implement `DownloadsLabels` because it returns label contents directly, or `CancelsShipments` because native PLC cancellation needs the shipment `Number` and potentially all collo codes. Tracking, standalone label creation and pickup scheduling remain outside the shared capability API.

## Build and import a shipment

```php
use AlexanderPoellmann\LaravelPostPlc\Classes\Address;
use AlexanderPoellmann\LaravelPostPlc\Classes\Collo;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Facades\LaravelPostPlc;

$from = (new Address)
    ->name('Absender GmbH')
    ->street('Rochusmarkt 5')
    ->postCode('1030')
    ->city('Wien')
    ->countryCode('AT')
    ->phone('+431234567')
    ->email('shipping@example.com')
    ->get();

$to = (new Address)
    ->name('Musterfirma GmbH', 'c/o Maria Muster')
    ->street('Landesgerichtsstraße 1')
    ->postCode('1010')
    ->city('Wien')
    ->countryCode('AT')
    ->email('recipient@example.com')
    ->get();

$shipment = (new Shipment)
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
        (new Collo)->weight(0.4)->get(),
    ])
    ->get();

LaravelPostPlc::request(ServiceMethods::ImportShipment, $shipment, asRow: true);

$result = LaravelPostPlc::toObject();
```

`withPrinter()` uses the PLC defaults (`100x200`, `2xA5inA4`, PDF/UTF) unless overridden.

## Validate before sending

`ShipmentValidator` performs deterministic PLC preflight validation and returns all detected incompatibilities at once:

```php
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;

$validation = app(ShipmentValidator::class)->validate($shipment);
$validation->throwIfInvalid();
```

Local validation covers documented address lengths, domestic/international route scope for known products, contact requirements, incompatible delivery features, weights, return settings, COD/insurance data, German branch handling and customs/article rules.

The PLC service remains authoritative for rules that depend on the active customer setup, postcode, current Austrian Post configuration or contract-specific server logic.

## Customer-specific products and features

Use PLC's `GetAllowedServicesForCountry` as the live capability catalog:

```php
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServicesResolver;

$capabilities = app(AllowedServicesResolver::class)->forCountries('DE');

foreach ($capabilities->products() as $product) {
    $product->code->value;
    $product->name;
    $product->contractProduct; // PLC's "Vertragsprodukt Ja/Nein" flag
    $product->order;
    $product->featureCodes();
}

$capabilities->allowsProduct('45');
$capabilities->allowsFeature('45', '054');
```

Capability discovery is cached by PLC account, endpoint and countries by default. Configure `post-plc.capabilities.cache.ttl`, refresh the cached result with `forCountries('DE', fresh: true)`, or invalidate a country with `forget('DE')`. A PLC discovery error throws `PlcRequestException` and is never cached as an empty catalog.

`Contract` is exposed as PLC metadata (`contractProduct`); the package does not interpret it as proof that a commercial product is contracted for the customer.

### Merchant product policy

Applications often need to hide a subset of products/features even when PLC returns them. Configure this separately from PLC's live capability result:

```php
// config/post-plc.php
'capabilities' => [
    'policy' => [
        'enabled_products' => ['10', '45'],
        'disabled_products' => [],
        'disabled_features' => [
            '*' => ['006'],      // disable COD globally
            '45' => ['054'],     // disable sender info on this product
        ],
        'preferred_products' => [
            'AT' => '10',
            'DE' => '45',
            '*' => '70',
        ],
        'fallback_product' => null,
    ],
],
```

```php
use AlexanderPoellmann\LaravelPostPlc\Policies\ServicePolicy;

$policy = app(ServicePolicy::class);

$policy->allowsProduct($capabilities, '45');
$policy->allowsFeature($capabilities, '45', '054');
$preferred = $policy->preferredProduct($capabilities, 'DE');
```

This keeps three concerns separate: what PLC currently offers, what the merchant enables, and what the shipment validator allows for a concrete shipment.

## Forward-compatible product and feature codes

Enums cover every product and additional service in PLC API v2.0, but the wire boundary is intentionally not limited to enums. If Austrian Post introduces a new code before this package is released again, pass it through `ProductCode`, `FeatureCode`, or a raw string:

```php
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;

$shipment = (new Shipment)
    ->using(ProductCode::from('NEW-PRODUCT'))
    ->to($to)
    ->get();

FeatureRow::make('999', 'value');
```

Known codes remain available through `PostProductCodes` and `Features`. `PostProductCodes::apiValue()` preserves leading-zero codes such as `01` and `04`.

## Return labels

The package exposes the PLC return workflow as a first-class service instead of requiring callers to manually choose SOAP method names.

### Domestic printable return

```php
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnLabelService;
use AlexanderPoellmann\LaravelPostPlc\Returns\ReturnShipmentFactory;

$returnShipment = app(ReturnShipmentFactory::class)->fromAddresses(
    sender: $customerAddress,
    recipient: $warehouseAddress,
);

$result = app(ReturnLabelService::class)->createLabel($returnShipment);

$pdf = $result->pdfData;
```

For an Austrian sender and Austrian return recipient, the factory defaults to `Retourpaket` (`28`) and a PDF printer. The service validates the shipment before sending it. Inspect `errorCode` and `errorMessage` on the returned DTO before using its label data.

### QR / paperless domestic return

```php
$result = app(ReturnLabelService::class)->createQr($returnShipment);

$qrPngBase64 = $result->qrCode;
$code128Base64 = $result->code128;
```

This uses `ImportShipmentAndGenerateBarcode`. PLC API v2.0 documents QR generation for Retourpaket National and Paketmarken. Shipments with `FeatureRow::businessParcelStamp()` can also use `createQr()`.

### Create a return from an outbound shipment

```php
$returnShipment = app(ReturnShipmentFactory::class)->fromOutbound(
    outbound: $originalShipment,
    returnRecipient: $warehouseAddress,
);
```

The original recipient becomes the return sender. For international returns the factory deliberately requires an explicit product because PLC currently documents three international return variants (`04`, `63`, `66`) and the correct choice depends on the customer contract/workflow:

```php
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;

$returnShipment = app(ReturnShipmentFactory::class)->fromOutbound(
    $originalShipment,
    $warehouseAddress,
    PostProductCodes::RetourpaketInternationalStandard,
    parcels: [(new \AlexanderPoellmann\LaravelPostPlc\Classes\Collo)->weight(1.2)->get()],
);
```

Supply the return parcel weights explicitly; outbound tracking codes and contents are not copied. Returns across a customs border also need the appropriate article data and sender/recipient contacts.

## Multiple PLC accounts / profiles

The top-level configuration still represents the default account. Multi-store or multi-tenant applications can define named profiles:

```php
// config/post-plc.php
'profiles' => [
    'store-b' => [
        'client_id' => env('PLC_STORE_B_CLIENT_ID'),
        'org_unit_id' => env('PLC_STORE_B_ORG_UNIT_ID'),
        'org_unit_guid' => env('PLC_STORE_B_ORG_UNIT_GUID'),
        'identifier' => 'Store B',
        'sandbox' => false,
    ],
],
```

```php
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;

$client = app(LaravelPostPlc::class)->forProfile('store-b');
```

The returned client has independent credentials and response state while reusing the configured transport.

Use that client when constructing profile-specific helpers:

```php
$factory = new ReturnShipmentFactory($client);
$labels = new ReturnLabelService($client, app(ShipmentValidator::class));
$capabilities = new AllowedServicesResolver($client, app(\Illuminate\Contracts\Cache\Repository::class));
```

`default_profile` selects the profile used by container-resolved services. Merchant policy preferences are tried in order: the destination country, `*`, then `fallback_product`; unavailable or disabled candidates are skipped.

## PLC API v2.0 operations

The current service enum contains every operation documented by PLC API v2.0:

- `ImportShipment`
- `ImportShipmentAndGenerateBarcode`
- `ImportShipmentReturnImage`
- `ImportShipmentForce`
- `ImportAddress`
- `PerformEndOfDay`
- `PerformEndOfDaySelect`
- `CancelShipments`
- `GetAllowedServicesForCountry`
- `GetAvailableTimeWindowsForPickupOrder`
- `ImportPickupOrderBusiness`
- `CancelPickupOrder`
- `BuildGroupageShipment`
- `CompleteGroupageShipment`

Typed DTOs are provided for groupage rows/requests, pickup orders and the image/force shipment responses. The old `ImportPickupOrder` enum/request remains available only for source compatibility with earlier package versions; new integrations should use `ImportPickupOrderBusiness`.

Response DTOs accept SOAP collection wrappers with one or multiple parcels, tracking codes and images. `toArray()` preserves the raw response structure. Printer languages include `PDF`, `ZPL2`, `JPEG`, `GIF`, `PNG`, `PDFZPL2` and `None`.

## Customs and importer data

API v2.0 importer and customs address fields are supported, including `OUImporterAddress`, authorized exporter identification, customs duty/tax account numbers and province codes:

```php
$importer = (new Address)
    ->name('Importer GmbH')
    ->street('Importweg 1')
    ->postCode('1010')
    ->city('Wien')
    ->countryCode('AT')
    ->customsDutyAccountNumber('...')
    ->customsTaxAccountNumber('...')
    ->provinceCode('AT-9')
    ->get();

$shipment = (new Shipment)
    // ...
    ->importer($importer)
    ->referenceBarcodeType('C')
    ->get();
```

Use `ColloArticleRow::documents()` and `ColloArticleRow::goods()` for customs contents.

## Tracking / fulfillment updates

Tracking is not a PLC SOAP operation. This package therefore does not invent a polling method on the PLC client. Austrian Post exposes tracking through separate customer interfaces/APIs; once credentials for that interface are available it should be integrated behind a separate tracking boundary so PLC label creation and carrier status synchronization remain independent.

## Testing and quality

```bash
composer test
composer analyse
composer format:test
```

The test suite uses Pest and isolates the SOAP boundary through `PlcTransport`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for changes.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
