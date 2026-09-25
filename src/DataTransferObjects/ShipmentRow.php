<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Transformers\PostProductCodeTransformer;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ShipmentRow extends Data
{
    /** @param list<string>|null $BusinessDocumentEntryList */
    public function __construct(
        public readonly string $ClientID,
        public readonly string $OrgUnitID,
        public readonly string $OrgUnitGuid,
        public readonly ?PrinterRow $PrinterObject,
        public readonly ?string $CostCenterThirdPartyID,
        public readonly ?string $Number,
        #[WithTransformer(PostProductCodeTransformer::class)]
        public readonly PostProductCodes $DeliveryServiceThirdPartyID,
        public readonly ?string $ShippingDateTimeFrom,
        public readonly ?string $ShippingDateTimeTo,
        public readonly ?AddressRow $OUShipperAddress,
        public readonly ?string $OUShipperReference1,
        public readonly ?string $OUShipperReference2,
        public readonly AddressRow $OURecipientAddress,
        public readonly ?AddressRow $AlternativeReturnOrgUnitAddress,
        public readonly ?string $DeliveryInstruction,
        public readonly ?string $MovementReferenceNumber,
        public readonly ?string $CustomsDescription,
        public readonly ?bool $CustomDataBit1,
        public readonly ?bool $CustomDataBit2,
        public readonly ?string $CustomerProduct,
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
}
