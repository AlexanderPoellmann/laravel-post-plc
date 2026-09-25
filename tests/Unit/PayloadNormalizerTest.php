<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Support\PayloadNormalizer;

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
