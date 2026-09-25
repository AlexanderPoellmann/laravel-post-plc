<?php

declare(strict_types=1);

use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use RicorocksDigitalAgency\Soap\Facades\Soap;
use RicorocksDigitalAgency\Soap\Request\Request;
use RicorocksDigitalAgency\Soap\Response\Response;

it('passes the endpoint, operation and payload to the SOAP client', function (): void {
    $response = Response::new(['errorCode' => null]);
    Soap::fake(['*' => $response]);
    $payload = ['row' => ['CustomDataBit1' => false, 'ReturnDays' => 0]];

    $result = app(PlcTransport::class)->call('https://plc.invalid/service?wsdl', ServiceMethods::ImportShipment, $payload);

    expect($result)->toBe($response);
    Soap::assertSentCount(1);
    Soap::assertSent(fn (Request $request): bool => $request->getEndpoint() === 'https://plc.invalid/service?wsdl'
        && $request->getMethod() === 'ImportShipment'
        && $request->getBody() === $payload);
});
