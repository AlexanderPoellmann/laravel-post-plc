<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Casts\ProductCodeCast;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Transformers\ProductCodeTransformer;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ShipmentRow extends Data
{
    /** @param list<string>|null $BusinessDocumentEntryList */
    public function __construct(
        public readonly string $ClientID,
        public readonly string $OrgUnitID,
        #[MapName('OrgUnitGUID')]
        public readonly string $OrgUnitGuid,
        public readonly ?PrinterRow $PrinterObject,
        public readonly ?string $CostCenterThirdPartyID,
        public readonly ?string $Number,
        #[WithTransformer(ProductCodeTransformer::class)]
        #[WithCast(ProductCodeCast::class)]
        public readonly PostProductCodes|ProductCode|string $DeliveryServiceThirdPartyID,
        public readonly ?string $ShippingDateTimeFrom,
        public readonly ?string $ShippingDateTimeTo,
        public readonly ?AddressRow $OUShipperAddress,
        public readonly ?string $OUShipperReference1,
        public readonly ?string $OUShipperReference2,
        public readonly AddressRow $OURecipientAddress,
        public readonly ?AddressRow $OUImporterAddress,
        public readonly ?AddressRow $AlternativeReturnOrgUnitAddress,
        public readonly ?string $DeliveryInstruction,
        public readonly ?string $MovementReferenceNumber,
        public readonly ?string $CustomsDescription,
        public readonly ?bool $CustomDataBit1,
        public readonly ?bool $CustomDataBit2,
        public readonly ?string $CustomerProduct,
        public readonly ?string $RefBarcodeType,
        public readonly ?int $ReturnModeID,
        public readonly ?int $ReturnDays,
        public readonly ?int $ReturnOptionID,
        #[DataCollectionOf(ColloRow::class)]
        public readonly ?DataCollection $ColloList,
        #[DataCollectionOf(ShipmentDocumentEntry::class)]
        public readonly ?DataCollection $ShipmentDocumentEntryList,
        #[DataCollectionOf(FeatureRow::class)]
        public readonly ?DataCollection $FeatureList,
        public readonly ?array $BusinessDocumentEntryList,
    ) {}

    public function productCode(): ProductCode
    {
        return ProductCode::from($this->DeliveryServiceThirdPartyID);
    }

    public function knownProduct(): ?PostProductCodes
    {
        return $this->productCode()->known();
    }
}
