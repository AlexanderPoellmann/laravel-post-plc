<?php

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use Spatie\LaravelData\Data;

class FeatureRow extends Data
{
    public function __construct(
        public readonly Features $ThirdPartyID,
        public readonly ?string $Value1 = null,
        public readonly ?string $Value2 = null,
        public readonly ?string $Value3 = null,
        public readonly ?string $Value4 = null,
    ) {}

    public static function cashOnDelivery(int|float|string $amount, string $currency, string $iban, string $bic, string $accountHolder, string $paymentReference): static
    {
        return new self(
            ThirdPartyID: Features::CashOnDelivery,
            Value1: $amount,
            Value2: $currency,
            Value3: "$iban|$bic|$accountHolder",
            Value4: $paymentReference,
        );
    }

    public static function cashOnDeliveryInternational(int|float|string $amount, string $currency, string $iban, string $bic, string $accountHolder, string $paymentReference): static
    {
        return new self(
            ThirdPartyID: Features::CashOnDeliveryInternational,
            Value1: $amount,
            Value2: $currency,
            Value3: "$iban|$bic|$accountHolder",
            Value4: $paymentReference,
        );
    }

    public static function valueShipment(int|float|string $amount, string $currency): static
    {
        return new self(
            ThirdPartyID: Features::ValueShipment,
            Value1: $amount,
            Value2: $currency,
        );
    }

    public static function preferredPickupBranch(int|string $branchCode): static
    {
        return new self(
            ThirdPartyID: Features::PreferredPickupBranch,
            Value1: $branchCode,
        );
    }

    public static function preferredPickupStation(int|string $stationCode): static
    {
        return new self(
            ThirdPartyID: Features::PreferredPickupStation,
            Value1: $stationCode,
        );
    }

    public static function senderNotification(int|string $emailOrPhone): static
    {
        return new self(
            ThirdPartyID: Features::SenderNotification,
            Value1: $emailOrPhone,
        );
    }

    public static function additionalInsurance(int|float|string $amount, string $currency): static
    {
        return new self(
            ThirdPartyID: Features::AdditionalInsurance,
            Value1: $amount,
            Value2: $currency,
        );
    }

    public static function posteRestante(int|string $branchCode): static
    {
        return new self(
            ThirdPartyID: Features::PosteRestante,
            Value1: $branchCode,
        );
    }

    public static function postBox(int|string $branchCode, string $postBoxCode): static
    {
        return new self(
            ThirdPartyID: Features::PosteRestante,
            Value1: $branchCode,
            Value2: $postBoxCode,
        );
    }

    public static function preferredNeighbor(string $name, string $streetAndStreetNumber): static
    {
        return new self(
            ThirdPartyID: Features::PreferredNeighbor,
            Value1: $name,
            Value2: $streetAndStreetNumber,
        );
    }

    public static function preferredDropLocation(string $location): static
    {
        return new self(
            ThirdPartyID: Features::PreferredDropLocation,
            Value1: $location,
        );
    }

    public static function fragile(): static
    {
        return new self(
            ThirdPartyID: Features::Fragile,
        );
    }

    public static function fragileInternational(): static
    {
        return new self(
            ThirdPartyID: Features::FragileInternational,
        );
    }

    public static function personalDelivery(): static
    {
        return new self(
            ThirdPartyID: Features::PersonalDelivery,
        );
    }
}
