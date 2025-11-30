<?php

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum Features: string
{
    case Fragile = '004';
    case CashOnDelivery = '006';
    case TwentyFourHourService = '007';
    case ValueShipment = '011';
    case DeliveryBy10Am = '019';
    case CashOnDeliveryInternational = '022';
    case FragileInternational = '024';
    case SaturdayDelivery = '027';
    case FreeToPlaceOfUse = '029';
    case PersonalDelivery = '045';
    case PreferredPickupBranch = '052';
    case PreferredPickupStation = '053';
    case SenderNotification = '054';
    case NoPartialDelivery = '061';
    case Pallet = '062';
    case AdditionalInsurance = '063';
    case PosteRestante = '065';
    case POBox = '066';
    case ParcelInternationalFast = '071';
    case ShortStoragePeriod = '072';
    case LimitedQuantityDangerousGoods = '074';
    case ReusableBoxSmall = '081';
    case ReusableBoxMedium = '082';
    case ReusableBoxLarge = '083';
    case Fresh = '116';
    case LateDelivery = '117';
    case PreferredNeighbor = '122';
    case PreferredDropLocation = '123';
    case ImmediateReturn = '142';
}
