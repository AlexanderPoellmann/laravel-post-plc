<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Configuration;

final readonly class PlcConfiguration
{
    public function __construct(
        public string $identifier,
        public string $clientId,
        public string $orgUnitId,
        public string $orgUnitGuid,
        public bool $sandbox,
        public string $productionEndpoint,
        public string $sandboxEndpoint,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            identifier: (string) (config('post-plc.identifier') ?? config('services.post-plc.identifier') ?? config('app.name', 'Laravel-Post-PLC')),
            clientId: (string) (config('post-plc.client_id') ?? config('services.post-plc.client-id', '')),
            orgUnitId: (string) (config('post-plc.org_unit_id') ?? config('services.post-plc.org-unit-id', '')),
            orgUnitGuid: (string) (config('post-plc.org_unit_guid') ?? config('services.post-plc.org-unit-guid', '')),
            sandbox: (bool) (config('post-plc.sandbox') ?? config('services.post-plc.sandbox', false)),
            productionEndpoint: (string) config('post-plc.endpoints.production', 'https://plc.post.at/Post.Webservice/ShippingService.svc?wsdl'),
            sandboxEndpoint: (string) config('post-plc.endpoints.sandbox', 'https://abn-plc.post.at/DataService/Post.Webservice/ShippingService.svc?wsdl'),
        );
    }

    public function endpoint(): string
    {
        return $this->sandbox ? $this->sandboxEndpoint : $this->productionEndpoint;
    }

    /** @return array{clientID: string, orgUnitID: string, orgUnitGuid: string} */
    public function credentials(): array
    {
        return [
            'clientID' => $this->clientId,
            'orgUnitID' => $this->orgUnitId,
            'orgUnitGuid' => $this->orgUnitGuid,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== ''
            && $this->orgUnitId !== ''
            && $this->orgUnitGuid !== '';
    }
}
