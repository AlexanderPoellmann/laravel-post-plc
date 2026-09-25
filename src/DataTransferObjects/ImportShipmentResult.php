<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Casts\SoapCollectionCast;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ImportShipmentResult extends Data
{
    public function __construct(
        #[WithCast(SoapCollectionCast::class, 'ColloRow')]
        #[DataCollectionOf(ColloRow::class)]
        public readonly ?DataCollection $ImportShipmentResult,
        public readonly ?string $zplLabelData,
        public readonly ?string $pdfData,
        public readonly ?string $shipmentDocuments,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
    ) {}
}
