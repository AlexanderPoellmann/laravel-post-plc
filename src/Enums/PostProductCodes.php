<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PostProductCodes: int
{
    case BusinessMailInternational = 89;
    case BusinessMailInternationalSelect = 90;
    case CombiFreightInternational = 49;
    case CombiFreightOesterreich = 47;
    case Feldpostpaket = 56;
    case Kleinpaket2000 = 96;
    case Kleinpaket2000Plus = 16;
    case NextDay = 65;
    case PaketPlusIntOutbound = 70;
    case PaketOesterreich = 10;
    case PaketPremiumInternational = 45;
    case PaketPremiumOesterreichB2B = 31;
    case PremiumSelect = 30;
    case PostExpressInternational = 46;
    case PostExpressOesterreich = 1;
    case Retourpaket = 28;
    case RetourpaketInternational = 63;
    case RetourpaketInternationalAbgabeAusland = 4;
    case RetourpaketInternationalStandard = 66;
    case SameDay = 64;
    case WertpaketPremium = 97;
    case Zeitfensterzustellung = 15;

    /** Legacy product not listed in PLC API v2.0; kept for existing customer contracts. */
    case PremiumLight = 14;

    /** Legacy product not listed in PLC API v2.0; kept for existing customer contracts. */
    case Kleinpaket = 12;

    /** Legacy product not listed in PLC API v2.0; kept for existing customer contracts. */
    case PaeckchenMMitSendungsverfolgung = 78;

    /** Legacy product not listed in PLC API v2.0; kept for existing customer contracts. */
    case PaketLightIntNonBoxableOutbound = 69;

    public function apiValue(): string
    {
        return match ($this) {
            self::PostExpressOesterreich => '01',
            self::RetourpaketInternationalAbgabeAusland => '04',
            default => (string) $this->value,
        };
    }

    public static function fromApiValue(string|int $value): ?self
    {
        $candidate = trim((string) $value);

        foreach (self::cases() as $case) {
            if ($case->apiValue() === $candidate || (string) $case->value === ltrim($candidate, '0')) {
                return $case;
            }
        }

        return null;
    }

    public function isLegacy(): bool
    {
        return in_array($this, [
            self::PremiumLight,
            self::Kleinpaket,
            self::PaeckchenMMitSendungsverfolgung,
            self::PaketLightIntNonBoxableOutbound,
        ], true);
    }

    public function isReturnProduct(): bool
    {
        return in_array($this, [
            self::Retourpaket,
            self::RetourpaketInternational,
            self::RetourpaketInternationalAbgabeAusland,
            self::RetourpaketInternationalStandard,
        ], true);
    }

    public function isDomestic(): bool
    {
        return $this->destinationScope() === 'domestic';
    }

    public function isInternational(): bool
    {
        return $this->destinationScope() === 'international';
    }

    public function destinationScope(): ?string
    {
        return match ($this) {
            self::BusinessMailInternational,
            self::BusinessMailInternationalSelect,
            self::RetourpaketInternational,
            self::RetourpaketInternationalAbgabeAusland,
            self::RetourpaketInternationalStandard,
            self::PaketPremiumInternational,
            self::CombiFreightInternational,
            self::PostExpressInternational,
            self::PaketPlusIntOutbound,
            self::PaketLightIntNonBoxableOutbound,
            self::PaeckchenMMitSendungsverfolgung => 'international',

            self::PaketOesterreich,
            self::CombiFreightOesterreich,
            self::PaketPremiumOesterreichB2B,
            self::PremiumSelect,
            self::PostExpressOesterreich,
            self::Retourpaket,
            self::Feldpostpaket,
            self::SameDay,
            self::WertpaketPremium,
            self::Zeitfensterzustellung => 'domestic',

            default => null,
        };
    }

    public function isAvailableForDestination(string $countryCode): bool
    {
        return $this->isAvailableForRoute('AT', $countryCode);
    }

    public function isAvailableForRoute(string $originCountryCode, string $destinationCountryCode): bool
    {
        $origin = strtoupper($originCountryCode);
        $destination = strtoupper($destinationCountryCode);
        $isDomesticRoute = $origin === 'AT' && $destination === 'AT';
        $isInternationalRoute = ($origin === 'AT') !== ($destination === 'AT');

        return match ($this->destinationScope()) {
            'domestic' => $isDomesticRoute,
            'international' => $isInternationalRoute,
            default => true,
        };
    }

    public function requiresWeight(): bool
    {
        return match ($this) {
            self::RetourpaketInternational,
            self::RetourpaketInternationalAbgabeAusland,
            self::RetourpaketInternationalStandard,
            self::PaketPlusIntOutbound,
            self::PaketPremiumInternational => true,
            default => false,
        };
    }

    public function forBusinessOnly(): bool
    {
        return in_array($this, [
            self::PaketPremiumOesterreichB2B,
            self::BusinessMailInternational,
            self::BusinessMailInternationalSelect,
        ], true);
    }
}
