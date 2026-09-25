<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ImportShipmentResult extends Data
{
    public function __construct(
        #[DataCollectionOf(ColloRow::class)]
        public readonly ?DataCollection $ImportShipmentResult,
        public readonly ?string $zplLabelData,
        public readonly ?string $pdfData,
        public readonly ?string $shipmentDocuments,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
    ) {}
}
