<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\DocumentTypes;
use Spatie\LaravelData\Data;

class ShipmentDocumentEntry extends Data
{
    public function __construct(
        public readonly DocumentTypes|int $DocumentID,
        public readonly int $Quantity,
        public readonly ?string $Number = null,
        public readonly ?int $ID = null,
        public readonly ?int $ShipmentID = null,
        public readonly ?string $DocumentDate = null,
        public readonly ?string $Comment = null,
        public readonly ?bool $HasDocumentChanged = null,
        public readonly ?string $MultimediaContent = null,
        public readonly ?string $MultimediaFileName = null,
        public readonly ?string $Reference1 = null,
        public readonly ?string $Reference2 = null,
        public readonly ?string $ThirdPartyType = null,
        public readonly ?string $Operation = null,
    ) {}
}
