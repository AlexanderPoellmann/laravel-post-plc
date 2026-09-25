<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Resolvers;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;

final class AllowedServices
{
    /**
     * @param  array<string, array{name: string|null, contract: bool|null, features: array<string, string|null>}>  $services
     */
    public function __construct(private readonly array $services) {}

    /** @param array<string, mixed> $response */
    public static function fromResponse(array $response): self
    {
        $rows = [];
        self::collectServiceRows($response, $rows);
        $services = [];

        foreach ($rows as $row) {
            $rawCode = (string) ($row['ThirdPartyID'] ?? '');
            $product = PostProductCodes::fromApiValue($rawCode);
            $code = $product?->apiValue() ?? $rawCode;

            if ($code === '') {
                continue;
            }

            $features = [];
            self::collectFeatureRows($row['FeatureList'] ?? [], $features);

            $featureMap = [];
            foreach ($features as $feature) {
                $featureCode = (string) ($feature['ThirdPartyID'] ?? '');
                if ($featureCode !== '') {
                    $featureMap[$featureCode] = isset($feature['Name']) ? (string) $feature['Name'] : null;
                }
            }

            $services[$code] = [
                'name' => isset($row['Name']) ? (string) $row['Name'] : null,
                'contract' => array_key_exists('Contract', $row) ? (bool) $row['Contract'] : null,
                'features' => $featureMap,
            ];
        }

        return new self($services);
    }

    public function allowsProduct(PostProductCodes $product): bool
    {
        return array_key_exists($product->apiValue(), $this->services);
    }

    public function allowsFeature(PostProductCodes $product, Features $feature): bool
    {
        return array_key_exists($feature->value, $this->services[$product->apiValue()]['features'] ?? []);
    }

    /** @return list<string> */
    public function productCodes(): array
    {
        return array_map(strval(...), array_keys($this->services));
    }

    /** @return list<string> */
    public function featureCodesFor(PostProductCodes $product): array
    {
        return array_map(strval(...), array_keys($this->services[$product->apiValue()]['features'] ?? []));
    }

    public function isEmpty(): bool
    {
        return $this->services === [];
    }

    /**
     * @param  list<array<array-key, mixed>>  $rows
     */
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

    /**
     * @param  list<array<array-key, mixed>>  $rows
     */
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
