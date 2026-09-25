<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ColloRow extends Data
{
    public function __construct(
        public readonly ?float $Weight,
        public readonly ?int $Length,
        public readonly ?int $Width,
        public readonly ?int $Height,
        #[DataCollectionOf(ColloCode::class)]
        public readonly ?DataCollection $ColloCodeList,
        #[DataCollectionOf(ColloArticleRow::class)]
        public readonly ?DataCollection $ColloArticleList,
    ) {}
}
