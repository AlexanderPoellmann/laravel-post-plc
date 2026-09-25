<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use AlexanderPoellmann\LaravelPostPlc\Tests\Support\ShipmentFixtures;
use AlexanderPoellmann\LaravelPostPlc\Validation\AddressValidator;

it('counts address length limits in characters and reports the supplied path', function (int $length, bool $valid): void {
    $address = AddressRow::from(array_replace(
        ShipmentFixtures::address()->toArray(),
        ['Name1' => str_repeat('ä', $length)],
    ));

    $result = app(AddressValidator::class)->validate($address, 'Sender');

    expect($result->isValid())->toBe($valid);

    if (! $valid) {
        expect($result->errors())->toHaveCount(1)
            ->and($result->errors()[0]->code)->toBe('address.max_length')
            ->and($result->errors()[0]->path)->toBe('Sender.Name1');
    }
})->with([
    'at the limit' => [100, true],
    'over the limit' => [101, false],
]);
