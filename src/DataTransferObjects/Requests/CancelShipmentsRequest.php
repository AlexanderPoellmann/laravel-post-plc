<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\Requests;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\CancelShipmentRow;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CancelShipmentsRequest extends Data
{
    public function __construct(
        #[DataCollectionOf(CancelShipmentRow::class)]
        public readonly DataCollection $shipments,
    ) {}
}
