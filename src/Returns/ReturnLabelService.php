<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Returns;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentAndGenerateBarcodeResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentResult;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\ShipmentValidationException;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServices;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;
use InvalidArgumentException;

final readonly class ReturnLabelService
{
    public function __construct(
        private LaravelPostPlc $client,
        private ShipmentValidator $validator,
    ) {}

    public function createLabel(ShipmentRow $shipment, ?AllowedServices $allowedServices = null): ImportShipmentResult
    {
        $this->assertReturnShipment($shipment, $allowedServices);
        $this->client->request(ServiceMethods::ImportShipment, $shipment, asRow: true);

        return ImportShipmentResult::from($this->client->toArray());
    }

    public function createQr(ShipmentRow $shipment, ?AllowedServices $allowedServices = null): ImportShipmentAndGenerateBarcodeResult
    {
        $knownProduct = $shipment->knownProduct();
        $hasParcelStamp = false;
        foreach ($shipment->FeatureList ?? [] as $feature) {
            if ($feature->code()->equals(Features::BusinessParcelStamp)) {
                $hasParcelStamp = true;
                break;
            }
        }

        if ($knownProduct !== null && $knownProduct !== PostProductCodes::Retourpaket && ! $hasParcelStamp) {
            throw new InvalidArgumentException(
                'PLC API v2.0 documents QR-code generation for Retourpaket National (28) and Paketmarken. Use createLabel() for other known return products.',
            );
        }

        $this->assertValidShipment($shipment, $allowedServices);
        $this->client->request(ServiceMethods::ImportShipmentAndGenerateBarcode, $shipment, asRow: true);

        return ImportShipmentAndGenerateBarcodeResult::from($this->client->toArray());
    }

    private function assertReturnShipment(ShipmentRow $shipment, ?AllowedServices $allowedServices): void
    {
        $knownProduct = $shipment->knownProduct();
        if ($knownProduct !== null && ! $knownProduct->isReturnProduct()) {
            throw new InvalidArgumentException(sprintf(
                'Product %s is not a documented PLC return product.',
                $shipment->productCode()->value,
            ));
        }

        $this->assertValidShipment($shipment, $allowedServices);
    }

    private function assertValidShipment(ShipmentRow $shipment, ?AllowedServices $allowedServices): void
    {
        $result = $this->validator->validate($shipment, $allowedServices);
        if ($result->isInvalid()) {
            throw new ShipmentValidationException($result);
        }
    }
}
