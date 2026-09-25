<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use Spatie\LaravelData\Data;

/** @phpstan-consistent-constructor */
class FeatureRow extends Data
{
    public function __construct(
        public readonly Features $ThirdPartyID,
        public readonly ?string $Value1 = null,
        public readonly ?string $Value2 = null,
        public readonly ?string $Value3 = null,
        public readonly ?string $Value4 = null,
    ) {}

    public static function make(
        Features $feature,
        int|float|string|null $value1 = null,
        int|float|string|null $value2 = null,
        int|float|string|null $value3 = null,
        int|float|string|null $value4 = null,
    ): static {
        return new static(
            ThirdPartyID: $feature,
            Value1: self::stringValue($value1),
            Value2: self::stringValue($value2),
            Value3: self::stringValue($value3),
            Value4: self::stringValue($value4),
        );
    }

    public static function cashOnDelivery(int|float|string $amount, string $currency, string $iban, string $bic, string $accountHolder, string $paymentReference): static
    {
        return self::make(
            Features::CashOnDelivery,
            $amount,
            strtoupper($currency),
            "$iban|$bic|$accountHolder",
            $paymentReference,
        );
    }

    public static function cashOnDeliveryInternational(int|float|string $amount, string $currency, string $iban, string $bic, string $accountHolder, string $paymentReference): static
    {
        return self::make(
            Features::CashOnDeliveryInternational,
            $amount,
            strtoupper($currency),
            "$iban|$bic|$accountHolder",
            $paymentReference,
        );
    }

    public static function valueShipment(int|float|string $amount, string $currency): static
    {
        return self::make(Features::ValueShipment, $amount, strtoupper($currency));
    }

    public static function preferredPickupBranch(int|string|null $branchCode = null): static
    {
        return self::make(Features::PreferredPickupBranch, $branchCode);
    }

    public static function preferredPickupStation(int|string|null $stationCode = null): static
    {
        return self::make(Features::PreferredPickupStation, $stationCode);
    }

    public static function senderNotification(string $emailOrPhone): static
    {
        return self::make(Features::SenderNotification, $emailOrPhone);
    }

    public static function additionalInsurance(int|float|string $amount, string $currency): static
    {
        return self::make(Features::AdditionalInsurance, $amount, strtoupper($currency));
    }

    public static function posteRestante(int|string|null $branchCode = null): static
    {
        return self::make(Features::PosteRestante, $branchCode);
    }

    public static function postBox(int|string $branchCode, string $postBoxCode): static
    {
        return self::make(Features::POBox, $branchCode, $postBoxCode);
    }

    public static function preferredNeighbor(string $name, string $streetAndStreetNumber): static
    {
        return self::make(Features::PreferredNeighbor, $name, $streetAndStreetNumber);
    }

    public static function preferredDropLocation(string $location): static
    {
        return self::make(Features::PreferredDropLocation, $location);
    }

    public static function fragile(): static
    {
        return self::make(Features::Fragile);
    }

    public static function twentyFourHourService(): static
    {
        return self::make(Features::TwentyFourHourService);
    }

    public static function deliveryBy10Am(): static
    {
        return self::make(Features::DeliveryBy10Am);
    }

    public static function fragileInternational(): static
    {
        return self::make(Features::FragileInternational);
    }

    public static function saturdayDelivery(): static
    {
        return self::make(Features::SaturdayDelivery);
    }

    public static function freeToPlaceOfUse(): static
    {
        return self::make(Features::FreeToPlaceOfUse);
    }

    public static function personalDelivery(): static
    {
        return self::make(Features::PersonalDelivery);
    }

    public static function noPartialDelivery(): static
    {
        return self::make(Features::NoPartialDelivery);
    }

    public static function pallet(): static
    {
        return self::make(Features::Pallet);
    }

    public static function parcelInternationalFast(): static
    {
        return self::make(Features::ParcelInternationalFast);
    }

    public static function shortStoragePeriod(): static
    {
        return self::make(Features::ShortStoragePeriod);
    }

    public static function limitedQuantityDangerousGoods(): static
    {
        return self::make(Features::LimitedQuantityDangerousGoods);
    }

    public static function reusableBoxSmall(): static
    {
        return self::make(Features::ReusableBoxSmall);
    }

    public static function reusableBoxMedium(): static
    {
        return self::make(Features::ReusableBoxMedium);
    }

    public static function reusableBoxLarge(): static
    {
        return self::make(Features::ReusableBoxLarge);
    }

    public static function fresh(): static
    {
        return self::make(Features::Fresh);
    }

    public static function lateDelivery(): static
    {
        return self::make(Features::LateDelivery);
    }

    public static function immediateReturn(): static
    {
        return self::make(Features::ImmediateReturn);
    }

    private static function stringValue(int|float|string|null $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
