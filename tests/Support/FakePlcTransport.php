<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Tests\Support;

use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use RicorocksDigitalAgency\Soap\Response\Response;

final class FakePlcTransport implements PlcTransport
{
    /** @var list<array{endpoint: string, method: ServiceMethods, payload: array<string, mixed>}> */
    public array $calls = [];

    public function __construct(private Response $response) {}

    /** @param array<string, mixed> $response */
    public static function responding(array $response = []): self
    {
        return new self(Response::new($response));
    }

    public function call(string $endpoint, ServiceMethods $method, array $payload): Response
    {
        $this->calls[] = compact('endpoint', 'method', 'payload');

        return $this->response;
    }
}
