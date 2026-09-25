<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\PickupDateTimeWindowRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\PickupOrderRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PickupLocationTypes;
use AlexanderPoellmann\LaravelPostPlc\Enums\SecurePickupLocationTypes;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\ShipmentFixtures;
use AlexanderPoellmann\LaravelPostPlc\Validation\PickupOrderValidator;

beforeEach(function (): void {
    $this->order = [
        'PickupAddress' => ShipmentFixtures::address(),
        'PickupDateTimeWindow' => new PickupDateTimeWindowRow('2026-09-28T00:00:00', '07:00:00', '17:00:00'),
        'NumberOfPackages' => 1,
        'PickupLocationType' => PickupLocationTypes::PersonalHandover,
        'ContactPersonName' => 'Maxi Muster',
        'AcceptTermsAndConditions' => true,
    ];
});

it('accepts package counts at the allowed boundaries', function (int $packages): void {
    $order = PickupOrderRow::from(array_replace($this->order, ['NumberOfPackages' => $packages]));

    expect(app(PickupOrderValidator::class)->validate($order)->errors())->toBe([]);
})->with([1, 5]);

it('validates the pickup address using the same rules as shipments', function (): void {
    $address = AddressRow::from(array_replace(
        ShipmentFixtures::address()->toArray(),
        ['PostalCode' => '', 'Email' => 'invalid'],
    ));
    $order = PickupOrderRow::from(array_replace($this->order, ['PickupAddress' => $address]));

    $errors = app(PickupOrderValidator::class)->validate($order)->errors();

    expect(array_map(fn ($error) => [$error->code, $error->path], $errors))->toBe([
        ['address.required', 'PickupAddress.PostalCode'],
        ['address.email', 'PickupAddress.Email'],
    ]);
});

it('reports each invalid pickup field independently', function (array $changes, string $code, string $path): void {
    $order = PickupOrderRow::from(array_replace($this->order, $changes));

    $errors = app(PickupOrderValidator::class)->validate($order)->errors();

    expect($errors)->toHaveCount(1)
        ->and($errors[0]->code)->toBe($code)
        ->and($errors[0]->path)->toBe($path);
})->with([
    'no packages' => [['NumberOfPackages' => 0], '10090', 'NumberOfPackages'],
    'too many packages' => [['NumberOfPackages' => 6], '10090', 'NumberOfPackages'],
    'missing secure location' => [['PickupLocationType' => PickupLocationTypes::Secure], '10093', 'SecurePickupLocationType'],
    'secure location with personal handover' => [['SecurePickupLocationType' => SecurePickupLocationTypes::Other, 'OtherSecurePickupLocation' => 'Garage'], '10093', 'SecurePickupLocationType'],
    'missing location description' => [['PickupLocationType' => PickupLocationTypes::Secure, 'SecurePickupLocationType' => SecurePickupLocationTypes::Other], '10094', 'OtherSecurePickupLocation'],
    'terms not accepted' => [['AcceptTermsAndConditions' => false], '10096', 'AcceptTermsAndConditions'],
    'blank contact' => [['ContactPersonName' => ' '], 'pickup.contact_required', 'ContactPersonName'],
    'long first reference' => [['Reference1' => str_repeat('a', 46)], 'pickup.reference_too_long', 'Reference1'],
    'long second reference' => [['Reference2' => str_repeat('a', 46)], 'pickup.reference_too_long', 'Reference2'],
]);
