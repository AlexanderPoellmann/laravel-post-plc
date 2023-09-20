<?php

namespace AlexanderPoellmann\LaravelPostPlc;

use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use RicorocksDigitalAgency\Soap\Facades\Soap;
use RicorocksDigitalAgency\Soap\Response\Response;
use Spatie\LaravelData\Data;

class LaravelPostPlc
{
    protected string $endpoint;

    protected string $identifier;

    protected string $client_id;

    protected string $org_unit_id;

    protected string $org_unit_guid;

    protected bool $sandbox;

    protected ?Response $response = null;

    public function __construct()
    {
        $this->identifier    = config('services.post-plc.identifier', env('APP_NAME', 'Laravel-Post-PLC'));
        $this->client_id     = config('services.post-plc.client-id');
        $this->org_unit_id   = config('services.post-plc.org-unit-id');
        $this->org_unit_guid = config('services.post-plc.org-unit-guid');
        $this->sandbox       = config('services.post-plc.sandbox', false);
    }

    public function endpoint(): string
    {
        return $this->sandbox ? 'https://abn-plc.post.at/DataService/Post.Webservice/ShippingService.svc?wsdl'
                              : 'https://plc.post.at/DataService/Post.Webservice/ShippingService.svc?wsdl';
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getClientId(): string
    {
        return $this->client_id;
    }

    public function getOrgUnitId(): string
    {
        return $this->org_unit_id;
    }

    public function getOrgUnitGuid(): string
    {
        return $this->org_unit_guid;
    }

    public function call(ServiceMethods $method, Data $data, bool $as_row = false): void
    {
        $data_array = array_filter($data->toArray());

        if (app()->environment() === 'local')
            info('[Post PLC] Given data.', $data_array);

        $this->response = Soap::to($this->endpoint())
                              ->call($method->value, $as_row ? ['row' => $data_array] : $data_array);
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function toArray(): array
    {
        return (array) $this->getResponse();
    }

    public function hasError(): bool
    {
        return true;
    }
}
