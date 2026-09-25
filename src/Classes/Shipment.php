<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Classes;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\PrinterRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentDocumentEntry;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\BusinessDocumentTypes;
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterEncoding;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;
use AlexanderPoellmann\LaravelPostPlc\Enums\ReturnOptions;
use AlexanderPoellmann\LaravelPostPlc\Enums\ReturnPaths;
use AlexanderPoellmann\LaravelPostPlc\Facades\LaravelPostPlc;
use DateTimeInterface;

class Shipment extends PlcBase
{
    public function __construct()
    {
        $this->setup();
    }

    private function setup(): void
    {
        $this->add('ClientID', LaravelPostPlc::getClientId());
        $this->add('OrgUnitID', LaravelPostPlc::getOrgUnitId());
        $this->add('OrgUnitGuid', LaravelPostPlc::getOrgUnitGuid());
        $this->add('CustomerProduct', LaravelPostPlc::getIdentifier());
    }

    public function withPrinter(
        PrinterLanguages $language = PrinterLanguages::PDF,
        LabelSizes $labelSize = LabelSizes::LONG,
        PaperLayouts $paperLayout = PaperLayouts::A5T,
        PrinterEncoding $encoding = PrinterEncoding::UTF,
    ): self {
        $this->add('PrinterObject', new PrinterRow(
            LanguageID: $language,
            LabelFormatID: $labelSize,
            PaperLayoutID: $paperLayout,
            Encoding: $encoding,
        ));

        return $this;
    }

    public function withNumber(string $number): self
    {
        $this->add('Number', $number);

        return $this;
    }

    public function using(PostProductCodes $postProductCode): self
    {
        $this->add('DeliveryServiceThirdPartyID', $postProductCode);

        return $this;
    }

    public function from(AddressRow $address): self
    {
        $this->add('OUShipperAddress', $address);

        return $this;
    }

    public function to(AddressRow $address): self
    {
        $this->add('OURecipientAddress', $address);

        return $this;
    }

    public function costCenter(string $costCenter): self
    {
        $this->add('CostCenterThirdPartyID', $costCenter);

        return $this;
    }

    public function shippingWindow(DateTimeInterface|string|null $from, DateTimeInterface|string|null $to = null): self
    {
        $this->add('ShippingDateTimeFrom', $this->dateValue($from));
        $this->add('ShippingDateTimeTo', $this->dateValue($to));

        return $this;
    }

    public function shipperReferences(?string $reference1 = null, ?string $reference2 = null): self
    {
        $this->add('OUShipperReference1', $reference1);
        $this->add('OUShipperReference2', $reference2);

        return $this;
    }

    public function alternativeReturnAddress(AddressRow $address): self
    {
        $this->add('AlternativeReturnOrgUnitAddress', $address);

        return $this;
    }

    public function deliveryInstruction(string $instruction): self
    {
        $this->add('DeliveryInstruction', $instruction);

        return $this;
    }

    public function movementReferenceNumber(string $mrn): self
    {
        $this->add('MovementReferenceNumber', $mrn);

        return $this;
    }

    public function customsDescription(string $description): self
    {
        $this->add('CustomsDescription', $description);

        return $this;
    }

    public function createReturnShipment(bool $enabled = true): self
    {
        $this->add('CustomDataBit1', $enabled);

        return $this;
    }

    public function errorLabel(bool $enabled = true): self
    {
        $this->add('CustomDataBit2', $enabled);

        return $this;
    }

    public function returnMode(ReturnOptions $option, ?int $days = null): self
    {
        $this->add('ReturnModeID', $option->value);
        $this->add('ReturnDays', $days);

        return $this;
    }

    public function returnPath(ReturnPaths $path): self
    {
        $this->add('ReturnOptionID', $path->value);

        return $this;
    }

    /** @param list<ColloRow|array<string, mixed>> $parcels */
    public function parcels(array $parcels): self
    {
        $this->add('ColloList', $parcels);

        return $this;
    }

    /** @param list<FeatureRow|array<string, mixed>> $features */
    public function withFeatures(array $features): self
    {
        $this->add('FeatureList', $features);

        return $this;
    }

    /** @param list<ShipmentDocumentEntry|array<string, mixed>> $documents */
    public function shipmentDocuments(array $documents): self
    {
        $this->add('ShipmentDocumentEntryList', $documents);

        return $this;
    }

    /** @param list<BusinessDocumentTypes|string> $documentTypes */
    public function requestBusinessDocuments(array $documentTypes): self
    {
        $this->add('BusinessDocumentEntryList', array_values(array_map(
            static fn (BusinessDocumentTypes|string $type): string => $type instanceof BusinessDocumentTypes ? $type->value : $type,
            $documentTypes,
        )));

        return $this;
    }

    public function get(): ShipmentRow
    {
        return ShipmentRow::from($this->row);
    }

    private function dateValue(DateTimeInterface|string|null $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
}
