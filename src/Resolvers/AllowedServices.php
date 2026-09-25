<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Resolvers;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\FeatureCode;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;

final class AllowedServices
{
    /** @param array<string, AllowedService> $services */
    public function __construct(private readonly array $services) {}

    /** @param array<string, mixed> $response */
    public static function fromResponse(array $response): self
    {
        $rows = [];
        self::collectServiceRows($response, $rows);
        $services = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['ThirdPartyID'] ?? ''));

            if ($code === '') {
                continue;
            }

            $features = [];
            self::collectFeatureRows($row['FeatureList'] ?? [], $features);

            $featureMap = [];
            foreach ($features as $feature) {
                $featureCode = trim((string) ($feature['ThirdPartyID'] ?? ''));
                if ($featureCode !== '') {
                    $featureMap[FeatureCode::from($featureCode)->value] = isset($feature['Name']) ? (string) $feature['Name'] : null;
                }
            }

            $productCode = ProductCode::from($code);
            $services[$productCode->value] = new AllowedService(
                code: $productCode,
                name: isset($row['Name']) ? (string) $row['Name'] : null,
                contractProduct: array_key_exists('Contract', $row) ? (bool) $row['Contract'] : null,
                order: isset($row['OrderID']) ? (int) $row['OrderID'] : null,
                features: $featureMap,
            );
        }

        uasort($services, static fn (AllowedService $a, AllowedService $b): int => ($a->order ?? PHP_INT_MAX) <=> ($b->order ?? PHP_INT_MAX));

        return new self($services);
    }

    /** @param array<string, array{code: string, name: string|null, contract: bool|null, order: int|null, features: array<string, string|null>}> $services */
    public static function fromArray(array $services): self
    {
        $mapped = [];

        foreach ($services as $service) {
            $code = ProductCode::from($service['code']);
            $mapped[$code->value] = new AllowedService(
                code: $code,
                name: $service['name'],
                contractProduct: $service['contract'],
                order: $service['order'],
                features: $service['features'],
            );
        }

        return new self($mapped);
    }

    public function product(PostProductCodes|ProductCode|string|int $product): ?AllowedService
    {
        return $this->services[ProductCode::from($product)->value] ?? null;
    }

    public function allowsProduct(PostProductCodes|ProductCode|string|int $product): bool
    {
        return $this->product($product) !== null;
    }

    public function allowsFeature(
        PostProductCodes|ProductCode|string|int $product,
        Features|FeatureCode|string|int $feature,
    ): bool {
        return $this->product($product)?->allowsFeature($feature) ?? false;
    }

    public function isContractProduct(PostProductCodes|ProductCode|string|int $product): ?bool
    {
        return $this->product($product)?->contractProduct;
    }

    /** @return list<AllowedService> */
    public function products(): array
    {
        return array_values($this->services);
    }

    /** @return list<string> */
    public function productCodes(): array
    {
        return array_values(array_map(strval(...), array_keys($this->services)));
    }

    /** @return list<string> */
    public function featureCodesFor(PostProductCodes|ProductCode|string|int $product): array
    {
        return $this->product($product)?->featureCodes() ?? [];
    }

    public function isEmpty(): bool
    {
        return $this->services === [];
    }

    /** @return array<string, array{code: string, name: string|null, contract: bool|null, order: int|null, features: array<string, string|null>}> */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->services as $code => $service) {
            $result[$code] = $service->toArray();
        }

        return $result;
    }

    /** @param list<array<array-key, mixed>> $rows */
    private static function collectServiceRows(mixed $node, array &$rows): void
    {
        if (is_object($node)) {
            $node = get_object_vars($node);
        }

        if (! is_array($node)) {
            return;
        }

        if (array_key_exists('ThirdPartyID', $node)
            && (array_key_exists('Contract', $node) || array_key_exists('OrderID', $node))) {
            $rows[] = $node;

            return;
        }

        foreach ($node as $child) {
            self::collectServiceRows($child, $rows);
        }
    }

    /** @param list<array<array-key, mixed>> $rows */
    private static function collectFeatureRows(mixed $node, array &$rows): void
    {
        if (is_object($node)) {
            $node = get_object_vars($node);
        }

        if (! is_array($node)) {
            return;
        }

        if (array_key_exists('ThirdPartyID', $node)
            && ! array_key_exists('Contract', $node)
            && ! array_key_exists('OrderID', $node)) {
            $rows[] = $node;

            return;
        }

        foreach ($node as $child) {
            self::collectFeatureRows($child, $rows);
        }
    }
}
