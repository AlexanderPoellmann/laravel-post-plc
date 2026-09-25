<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Casts\SoapCollectionCast;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ColloRow extends Data
{
    public function __construct(
        public readonly ?float $Weight,
        public readonly ?int $Length,
        public readonly ?int $Width,
        public readonly ?int $Height,
        #[WithCast(SoapCollectionCast::class, 'ColloCode')]
        #[DataCollectionOf(ColloCode::class)]
        public readonly ?DataCollection $ColloCodeList,
        #[WithCast(SoapCollectionCast::class, 'ColloArticleRow')]
        #[DataCollectionOf(ColloArticleRow::class)]
        public readonly ?DataCollection $ColloArticleList,
    ) {}
}
