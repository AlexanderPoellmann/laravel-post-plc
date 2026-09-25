<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\DocumentTypes;
use Spatie\LaravelData\Data;

class ShipmentDocumentEntry extends Data
{
    public function __construct(
        public readonly int $DocumentID,
        public readonly int $Quantity,
        public readonly ?string $Number = null,
    ) {}

    public static function make(DocumentTypes|int $document, int $quantity, ?string $number = null): self
    {
        return new self(
            DocumentID: $document instanceof DocumentTypes ? $document->value : $document,
            Quantity: $quantity,
            Number: $number,
        );
    }
}
