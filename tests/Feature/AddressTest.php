<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Classes\Address;

beforeEach(function (): void {
    $this->address = (new Address)
        ->name('Example GmbH')
        ->postCode('1010')
        ->city('Wien')
        ->countryCode(' at ');
});

it('splits the final house number while preserving the street name', function (string $street, string $route, ?string $number): void {
    $address = $this->address->street($street)->get();

    expect($address->AddressLine1)->toBe($route)
        ->and($address->HouseNumber)->toBe($number)
        ->and($address->CountryID)->toBe('AT');
})->with([
    'number within street name' => ['Straße des 12. Februar 3', 'Straße des 12. Februar', '3'],
    'no house number' => ['Postfach Hauptbahnhof', 'Postfach Hauptbahnhof', null],
    'number suffix' => ['Hauptstraße 12a', 'Hauptstraße', '12a'],
    'apartment number' => ['Hauptstraße 12/3', 'Hauptstraße', '12/3'],
    'surrounding whitespace' => ['  Hauptstraße 12  ', 'Hauptstraße', '12'],
]);

it('uses the PLC VATID field name on the wire', function (): void {
    $address = $this->address->street('Main Street 1')->vatId('ATU12345678')->get();

    expect($address->toArray())
        ->toHaveKey('VATID', 'ATU12345678')
        ->not->toHaveKey('VatId');
});

it('replaces all name lines when the builder is reused', function (): void {
    $address = $this->address->street('Main Street 1')
        ->name('Old name', 'Old second line', 'Old third line', 'Old fourth line')
        ->name('New name')
        ->get();

    expect([$address->Name1, $address->Name2, $address->Name3, $address->Name4])
        ->toBe(['New name', null, null, null]);
});

it('clears a previous house number when replacing the full street', function (): void {
    $address = $this->address->street('Main Street 1')->street('Postfach Hauptbahnhof')->get();

    expect($address->AddressLine1)->toBe('Postfach Hauptbahnhof')
        ->and($address->HouseNumber)->toBeNull();
});
