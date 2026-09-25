<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class ColloCode extends Data
{
    public function __construct(
        public readonly string $Code,
        public readonly int|string $NumberTypeID,
        public readonly ?string $OUCarrierThirdPartyID = null,
    ) {}
}
