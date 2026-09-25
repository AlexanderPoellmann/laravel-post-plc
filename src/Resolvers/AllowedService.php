<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Resolvers;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;

final readonly class AllowedService
{
    /** @param array<string, string|null> $features */
    public function __construct(
        public ProductCode $code,
        public ?string $name,
        public ?bool $contractProduct,
        public ?int $order,
        public array $features,
    ) {}

    public function allowsFeature(Features|FeatureCode|string|int $feature): bool
    {
        return array_key_exists(FeatureCode::from($feature)->value, $this->features);
    }

    public function featureName(Features|FeatureCode|string|int $feature): ?string
    {
        return $this->features[FeatureCode::from($feature)->value] ?? null;
    }

    /** @return list<string> */
    public function featureCodes(): array
    {
        return array_values(array_map(strval(...), array_keys($this->features)));
    }

    /** @return array{code: string, name: string|null, contract: bool|null, order: int|null, features: array<string, string|null>} */
    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'name' => $this->name,
            'contract' => $this->contractProduct,
            'order' => $this->order,
            'features' => $this->features,
        ];
    }
}
