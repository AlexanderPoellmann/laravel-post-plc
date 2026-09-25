<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Data;

class AdditionalInformationResult extends Data
{
    public function __construct(
        public readonly string $ThirdPartyID,
        public readonly string $Name,
    ) {}
}
