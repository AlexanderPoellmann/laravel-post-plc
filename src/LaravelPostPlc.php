<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc;

use AlexanderPoellmann\LaravelPostPlc\Configuration\PlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Contracts\PlcTransport;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\CompleteGroupageShipmentResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentAndGenerateBarcodeResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentForceResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentReturnImageResult;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\InvalidPlcConfiguration;
use AlexanderPoellmann\LaravelPostPlc\Support\PayloadNormalizer;
use AlexanderPoellmann\LaravelPostPlc\Transport\SoapPlcTransport;
use Illuminate\Support\Collection;
use LogicException;
use RicorocksDigitalAgency\Soap\Response\Response;
use Spatie\LaravelData\Data;

class LaravelPostPlc
{
    protected ?ServiceMethods $method = null;

    protected ?Response $response = null;

    public function __construct(
        protected ?PlcConfiguration $configuration = null,
        protected ?PlcTransport $transport = null,
    ) {
        $this->configuration ??= PlcConfiguration::fromConfig();
        $this->transport ??= new SoapPlcTransport;
    }

    public function endpoint(): string
    {
        return $this->configuration->endpoint();
    }

    public function configuration(): PlcConfiguration
    {
        return $this->configuration;
    }

    public function forProfile(string|PlcConfiguration $profile): self
    {
        $configuration = $profile instanceof PlcConfiguration
            ? $profile
            : PlcConfiguration::fromConfig($profile);

        return new self($configuration, $this->transport);
    }

    public function getIdentifier(): string
    {
        return $this->configuration->identifier;
    }

    public function getClientId(): string
    {
        return $this->configuration->clientId;
    }

    public function getOrgUnitId(): string
    {
        return $this->configuration->orgUnitId;
    }

    public function getOrgUnitGuid(): string
    {
        return $this->configuration->orgUnitGuid;
    }

    public function isConfigured(): bool
    {
        return $this->configuration->isConfigured();
    }

    public function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw InvalidPlcConfiguration::missingCredentials();
        }
    }

    /**
     * @param  Data|array<string, mixed>  $data
     */
    public function request(ServiceMethods $method, Data|array $data, bool $asRow = false): Response
    {
        $this->method = $method;
        $this->response = null;

        $payload = PayloadNormalizer::request($data);
        $payload = $asRow ? ['row' => $payload] : $payload;

        $this->response = $this->transport->call($this->endpoint(), $method, $payload);

        return $this->response;
    }

    /**
     * Backward-compatible wrapper. New code can use request() when the raw SOAP
     * response is useful immediately.
     *
     * @param  Data|array<string, mixed>  $data
     */
    public function call(ServiceMethods $method, Data|array $data, bool $as_row = false): void
    {
        $this->request($method, $data, $as_row);
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function lastMethod(): ?ServiceMethods
    {
        return $this->method;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->response === null) {
            return [];
        }

        return PayloadNormalizer::response($this->response->response);
    }

    /** @return Collection<string, mixed> */
    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }

    /**
     * @template T of Data
     *
     * @param  class-string<T>  $dataClass
     * @return T
     */
    public function toData(string $dataClass): Data
    {
        return $dataClass::from($this->toArray());
    }

    public function toObject(): Data
    {
        if ($this->response === null) {
            throw new LogicException('No successful PLC response is available.');
        }

        return match ($this->method) {
            ServiceMethods::ImportShipment => ImportShipmentResult::from($this->toArray()),
            ServiceMethods::ImportShipmentAndGenerateBarcode => ImportShipmentAndGenerateBarcodeResult::from($this->toArray()),
            ServiceMethods::ImportShipmentReturnImage => ImportShipmentReturnImageResult::from($this->toArray()),
            ServiceMethods::ImportShipmentForce => ImportShipmentForceResult::from($this->toArray()),
            ServiceMethods::CompleteGroupageShipment => CompleteGroupageShipmentResult::from($this->toArray()),
            null => throw new LogicException('No PLC call has been made yet.'),
            default => throw new LogicException(sprintf(
                'No built-in response DTO is registered for %s. Use toArray(), toCollection(), or toData(YourData::class).',
                $this->method->value,
            )),
        };
    }
}
