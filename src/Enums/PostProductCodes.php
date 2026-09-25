<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PostProductCodes: int
{
    case Retourpaket = 28;
    case RetourpaketInternational = 63;
    case PremiumLight = 14;
    case PremiumSelect = 30;
    case Kleinpaket = 12;
    case NextDay = 65;
    case PaketOesterreich = 10;
    case PaketPremiumInternational = 45;
    case CombiFreightOesterreich = 47;
    case CombiFreightInternational = 49;
    case PaketPremiumOesterreichB2B = 31;
    case PostExpressOesterreich = 1;
    case PostExpressInternational = 46;
    case PaeckchenMMitSendungsverfolgung = 78;
    case PaketPlusIntOutbound = 70;
    case PaketLightIntNonBoxableOutbound = 69;
    case Kleinpaket2000 = 96;
    case Kleinpaket2000Plus = 16;

    /**
     * PLC defines product identifiers as text. This preserves the leading zero of
     * product code 01 while keeping the enum's historic int backing type.
     */
    public function apiValue(): string
    {
        return $this === self::PostExpressOesterreich ? '01' : (string) $this->value;
    }

    public static function fromApiValue(string|int $value): ?self
    {
        $candidate = (string) $value;

        foreach (self::cases() as $case) {
            if ($case->apiValue() === $candidate || (string) $case->value === ltrim($candidate, '0')) {
                return $case;
            }
        }

        return null;
    }

    public function isDomestic(): bool
    {
        return ! $this->isInternational();
    }

    public function isInternational(): bool
    {
        return match ($this) {
            self::RetourpaketInternational,
            self::PaketPremiumInternational,
            self::CombiFreightInternational,
            self::PostExpressInternational,
            self::PaketPlusIntOutbound,
            self::PaketLightIntNonBoxableOutbound => true,
            default => false,
        };
    }

    public function destinationScope(): ?string
    {
        return match ($this) {
            self::RetourpaketInternational,
            self::PaketPremiumInternational,
            self::CombiFreightInternational,
            self::PostExpressInternational,
            self::PaketPlusIntOutbound,
            self::PaketLightIntNonBoxableOutbound => 'international',
            self::PaketOesterreich,
            self::CombiFreightOesterreich,
            self::PaketPremiumOesterreichB2B,
            self::PostExpressOesterreich => 'domestic',
            default => null,
        };
    }

    public function isAvailableForDestination(string $countryCode): bool
    {
        $isAustria = strtoupper($countryCode) === 'AT';

        return match ($this->destinationScope()) {
            'domestic' => $isAustria,
            'international' => ! $isAustria,
            default => true,
        };
    }

    public function requiresWeight(): bool
    {
        return match ($this) {
            self::RetourpaketInternational,
            self::PaketPlusIntOutbound,
            self::PaketPremiumInternational => true,
            default => false,
        };
    }

    public function forBusinessOnly(): bool
    {
        return $this === self::PaketPremiumOesterreichB2B;
    }
}
