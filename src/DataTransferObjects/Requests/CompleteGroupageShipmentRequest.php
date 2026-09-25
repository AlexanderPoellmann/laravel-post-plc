<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentGroupageCompletionRow;
use Spatie\LaravelData\Data;

class CompleteGroupageShipmentRequest extends Data
{
    public function __construct(
        public readonly string $clientID,
        public readonly string $orgUnitID,
        public readonly string $orgUnitGuid,
        public readonly ShipmentGroupageCompletionRow $row,
    ) {}
}
