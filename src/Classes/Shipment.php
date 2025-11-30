<?php

namespace AlexanderPoellmann\LaravelPostPlc\Classes;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;
use AlexanderPoellmann\LaravelPostPlc\Facades\LaravelPostPlc;

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

    public function withPrinter(PrinterLanguages $language = PrinterLanguages::PDF, LabelSizes $labelSize = LabelSizes::SHORT, PaperLayouts $paperLayout = PaperLayouts::A4): self
    {
        $this->add('PrinterObject', [
            'LanguageID' => $language,
            'LabelFormatID' => $labelSize,
            'PaperLayoutID' => $paperLayout,
        ]);

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

    public function parcels(array $parcels): self
    {
        $this->add('ColloList', $parcels);

        return $this;
    }

    public function withFeatures(array $features): self
    {
        $this->add('FeatureList', $features);

        return $this;
    }

    public function get(): ShipmentRow
    {
        return ShipmentRow::from($this->row);
    }
}
