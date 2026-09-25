<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Transport;

use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use RicorocksDigitalAgency\Soap\Facades\Soap;
use RicorocksDigitalAgency\Soap\Response\Response;

final class SoapPlcTransport implements PlcTransport
{
    public function call(string $endpoint, ServiceMethods $method, array $payload): Response
    {
        return Soap::to($endpoint)->call($method->value, $payload);
    }
}
