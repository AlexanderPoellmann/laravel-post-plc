<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Policies;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServices;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;

final readonly class ServicePolicy
{
    /** @var list<string> */
    private array $enabledProducts;

    /** @var list<string> */
    private array $disabledProducts;

    /** @var array<string, list<string>> */
    private array $disabledFeatures;

    /** @var array<string, string> */
    private array $preferredProducts;

    private ?string $fallbackProduct;

    /**
     * @param  list<string>  $enabledProducts
     * @param  list<string>  $disabledProducts
     * @param  array<string, list<string>>  $disabledFeatures
     * @param  array<string, string>  $preferredProducts
     */
    public function __construct(
        array $enabledProducts = [],
        array $disabledProducts = [],
        array $disabledFeatures = [],
        array $preferredProducts = [],
        ?string $fallbackProduct = null,
    ) {
        $normalizeProduct = static fn (string $code): string => ProductCode::from($code)->value;
        $this->enabledProducts = array_map($normalizeProduct, $enabledProducts);
        $this->disabledProducts = array_map($normalizeProduct, $disabledProducts);

        $features = [];
        foreach ($disabledFeatures as $product => $codes) {
            $key = $product === '*' ? '*' : ProductCode::from($product)->value;
            $features[$key] = array_values(array_unique(array_merge(
                $features[$key] ?? [],
                array_map(static fn (string $code): string => FeatureCode::from($code)->value, $codes),
            )));
        }
        $this->disabledFeatures = $features;

        $preferred = [];
        foreach ($preferredProducts as $country => $product) {
            $preferred[strtoupper(trim($country))] = $normalizeProduct($product);
        }
        $this->preferredProducts = $preferred;
        $this->fallbackProduct = $fallbackProduct === null ? null : $normalizeProduct($fallbackProduct);
    }

    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('post-plc.capabilities.policy', []);

        return new self(
            enabledProducts: self::stringList($config['enabled_products'] ?? []),
            disabledProducts: self::stringList($config['disabled_products'] ?? []),
            disabledFeatures: self::featureMap($config['disabled_features'] ?? []),
            preferredProducts: self::stringMap($config['preferred_products'] ?? []),
            fallbackProduct: isset($config['fallback_product']) && $config['fallback_product'] !== '' ? (string) $config['fallback_product'] : null,
        );
    }

    public function allowsProduct(AllowedServices $services, PostProductCodes|ProductCode|string|int $product): bool
    {
        $code = ProductCode::from($product)->value;

        if (! $services->allowsProduct($code)) {
            return false;
        }

        if (in_array($code, $this->disabledProducts, true)) {
            return false;
        }

        return $this->enabledProducts === [] || in_array($code, $this->enabledProducts, true);
    }

    public function allowsFeature(
        AllowedServices $services,
        PostProductCodes|ProductCode|string|int $product,
        Features|FeatureCode|string|int $feature,
    ): bool {
        $productCode = ProductCode::from($product)->value;
        $featureCode = FeatureCode::from($feature)->value;

        if (! $this->allowsProduct($services, $productCode) || ! $services->allowsFeature($productCode, $featureCode)) {
            return false;
        }

        $disabled = array_merge(
            $this->disabledFeatures['*'] ?? [],
            $this->disabledFeatures[$productCode] ?? [],
        );

        return ! in_array($featureCode, $disabled, true);
    }

    public function preferredProduct(AllowedServices $services, string $countryCode): ?ProductCode
    {
        $country = strtoupper(trim($countryCode));
        foreach ([$this->preferredProducts[$country] ?? null, $this->preferredProducts['*'] ?? null, $this->fallbackProduct] as $candidate) {
            if ($candidate !== null && $this->allowsProduct($services, $candidate)) {
                return ProductCode::from($candidate);
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (mixed $value): string => (string) $value, $values)));
    }

    /** @return array<string, string> */
    private static function stringMap(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $mapped = [];
        foreach ($values as $key => $value) {
            $mapped[strtoupper((string) $key)] = (string) $value;
        }

        return $mapped;
    }

    /** @return array<string, list<string>> */
    private static function featureMap(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $mapped = [];
        foreach ($values as $product => $features) {
            $mapped[(string) $product] = self::stringList($features);
        }

        return $mapped;
    }
}
