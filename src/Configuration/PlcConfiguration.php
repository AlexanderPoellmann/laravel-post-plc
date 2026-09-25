<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Configuration;

use InvalidArgumentException;

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
        public ?string $profile = null,
    ) {}

    public static function fromConfig(?string $profile = null): self
    {
        $profile ??= config('post-plc.default_profile');
        $values = [];

        if (is_string($profile) && $profile !== '') {
            $values = config("post-plc.profiles.{$profile}");

            if (! is_array($values)) {
                throw new InvalidArgumentException(sprintf('PLC profile [%s] is not configured.', $profile));
            }
        }

        return new self(
            identifier: (string) ($values['identifier'] ?? config('post-plc.identifier') ?? config('services.post-plc.identifier') ?? config('app.name', 'Laravel-Post-PLC')),
            clientId: (string) ($values['client_id'] ?? config('post-plc.client_id') ?? config('services.post-plc.client-id', '')),
            orgUnitId: (string) ($values['org_unit_id'] ?? config('post-plc.org_unit_id') ?? config('services.post-plc.org-unit-id', '')),
            orgUnitGuid: (string) ($values['org_unit_guid'] ?? config('post-plc.org_unit_guid') ?? config('services.post-plc.org-unit-guid', '')),
            sandbox: (bool) ($values['sandbox'] ?? config('post-plc.sandbox') ?? config('services.post-plc.sandbox', false)),
            productionEndpoint: (string) ($values['endpoints']['production'] ?? config('post-plc.endpoints.production', 'https://plc.post.at/Post.Webservice/ShippingService.svc?wsdl')),
            sandboxEndpoint: (string) ($values['endpoints']['sandbox'] ?? config('post-plc.endpoints.sandbox', 'https://abn-plc.post.at/DataService/Post.Webservice/ShippingService.svc?wsdl')),
            profile: is_string($profile) && $profile !== '' ? $profile : null,
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

    public function cacheIdentity(): string
    {
        return hash('sha256', implode('|', [
            $this->endpoint(),
            $this->clientId,
            $this->orgUnitId,
            $this->orgUnitGuid,
        ]));
    }
}
