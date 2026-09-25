<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ImportAddressRequest extends Data
{
    public function __construct(
        public readonly string $clientID,
        public readonly string $orgUnitID,
        public readonly string $orgUnitGuid,
        #[DataCollectionOf(AddressRow::class)]
        public readonly DataCollection $addresses,
    ) {}
}
