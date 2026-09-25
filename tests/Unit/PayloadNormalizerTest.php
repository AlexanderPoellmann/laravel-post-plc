<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Support\PayloadNormalizer;
use Illuminate\Support\Collection;

it('normalizes nested Laravel collections without dropping their contents', function (): void {
    expect(PayloadNormalizer::request([
        'items' => new Collection([['active' => false, 'count' => 0, 'omit' => null]]),
    ]))->toBe(['items' => [['active' => false, 'count' => 0]]]);
});

it('preserves list shape when removing null request items', function (): void {
    expect(PayloadNormalizer::request(['items' => [null, false, null, 0]]))
        ->toBe(['items' => [false, 0]]);
});

it('preserves null response values while normalizing SOAP objects', function (): void {
    expect(PayloadNormalizer::response((object) [
        'items' => [(object) ['code' => '01', 'error' => null]],
    ]))->toBe(['items' => [['code' => '01', 'error' => null]]]);
});

it('removes only null values and preserves valid false and zero values', function (): void {
    $payload = PayloadNormalizer::request([
        'false' => false,
        'zero' => 0,
        'stringZero' => '0',
        'null' => null,
        'nested' => ['keep' => false, 'drop' => null],
    ]);

    expect($payload)->toBe([
        'false' => false,
        'zero' => 0,
        'stringZero' => '0',
        'nested' => ['keep' => false],
    ]);
});

it('serializes PLC product codes using their API representation', function (): void {
    expect(PayloadNormalizer::request(['product' => PostProductCodes::PostExpressOesterreich]))
        ->toBe(['product' => '01']);
});
