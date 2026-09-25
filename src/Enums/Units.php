<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum Units: string
{
    case Buendel = 'BE';
    case BallenVerdichtet = 'BL';
    case BallenKomprimiert = 'BN';
    case ZylindrischeFlasche = 'BO';
    case Balken = 'BR';
    case Schachtel = 'BX';
    case Dezimeter = 'DMT';
    case Quadratfuss = 'FTQ';
    case Gramm = 'GRM';
    case Kilogramm = 'KGM';
    case Kilometer = 'KTM';
    case Kubikmeter = 'MTQ';
    case Meter = 'MTR';
    case Stueck = 'PCE';
    case Sack = 'SA';
}
