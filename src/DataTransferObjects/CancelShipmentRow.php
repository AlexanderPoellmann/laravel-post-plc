<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class CancelShipmentRow extends Data
{
    /** @param list<string> $ColloCodeList */
    public function __construct(
        public readonly string $ClientID,
        public readonly string $OrgUnitID,
        public readonly string $OrgUnitGuid,
        public readonly string $Number,
        public readonly array $ColloCodeList,
    ) {}
}
