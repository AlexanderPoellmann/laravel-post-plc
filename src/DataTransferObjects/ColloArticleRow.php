<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\CustomsOptions;
use AlexanderPoellmann\LaravelPostPlc\Enums\Units;
use Spatie\LaravelData\Data;

class ColloArticleRow extends Data
{
    public function __construct(
        public readonly ?string $ArticleNumber,
        public readonly string $ArticleName,
        public readonly int|float|null $Quantity,
        public readonly ?Units $UnitID,
        public readonly string|int|null $HSTariffNumber,
        public readonly ?string $CountryOfOriginID,
        public readonly ?float $ValueOfGoodsPerUnit,
        public readonly ?string $CurrencyID,
        public readonly ?float $ConsumerUnitNetWeight,
        public readonly CustomsOptions $CustomsOptionID,
    ) {}

    public static function documents(string $description, ?string $articleNumber = null): self
    {
        return new self(
            ArticleNumber: $articleNumber,
            ArticleName: $description,
            Quantity: null,
            UnitID: null,
            HSTariffNumber: null,
            CountryOfOriginID: null,
            ValueOfGoodsPerUnit: null,
            CurrencyID: null,
            ConsumerUnitNetWeight: null,
            CustomsOptionID: CustomsOptions::Dokumente,
        );
    }

    public static function goods(
        string $description,
        int|float $quantity,
        Units $unit,
        string|int $hsTariffNumber,
        string $countryOfOrigin,
        float $valuePerUnit,
        string $currency,
        float $netWeight,
        CustomsOptions $customsOption = CustomsOptions::VerkaufVonWaren,
        ?string $articleNumber = null,
    ): self {
        return new self(
            ArticleNumber: $articleNumber,
            ArticleName: $description,
            Quantity: $quantity,
            UnitID: $unit,
            HSTariffNumber: $hsTariffNumber,
            CountryOfOriginID: strtoupper($countryOfOrigin),
            ValueOfGoodsPerUnit: $valuePerUnit,
            CurrencyID: strtoupper($currency),
            ConsumerUnitNetWeight: $netWeight,
            CustomsOptionID: $customsOption,
        );
    }
}
