<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Contracts;

use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use RicorocksDigitalAgency\Soap\Response\Response;

interface PlcTransport
{
    /** @param array<string, mixed> $payload */
    public function call(string $endpoint, ServiceMethods $method, array $payload): Response;
}
