<?php

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
    case PostExpressOesterreich = 01;
    case PostExpressInternational = 46;
    case PaeckchenMMitSendungsverfolgung = 78;
    case PaketPlusIntOutbound = 70;
    case PaketLightIntNonBoxableOutbound = 69;
    case Kleinpaket2000 = 96;
    case Kleinpaket2000Plus = 16;

    public function isDomestic(): bool
    {
        return match ($this) {
            self::RetourpaketInternational,
            self::PaketPremiumInternational,
            self::CombiFreightInternational,
            self::PostExpressInternational,
            self::PaketPlusIntOutbound,
            self::PaketLightIntNonBoxableOutbound => false,
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
        return match ($this) {
            self::PaketPremiumOesterreichB2B => true,
            default => false,
        };
    }
}
