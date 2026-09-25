<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Casts\SoapCollectionCast;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ImportShipmentAndGenerateBarcodeResult extends Data
{
    public function __construct(
        #[WithCast(SoapCollectionCast::class, 'ColloRow')]
        #[DataCollectionOf(ColloRow::class)]
        public readonly ?DataCollection $ImportShipmentAndGenerateBarcodeResult,
        public readonly ?string $zplLabelData,
        public readonly ?string $pdfData,
        public readonly ?string $shipmentDocuments,
        public readonly ?string $qrCode,
        public readonly ?string $code128,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
    ) {}
}
