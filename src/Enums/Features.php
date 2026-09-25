<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum Features: string
{
    case Fragile = '004';
    case CashOnDelivery = '006';
    case TwentyFourHourService = '007';
    case ReturnReceipt = '009';
    case ValueShipment = '011';
    case DeliveryBy10Am = '019';
    case PreferredDate = '020';
    case CashOnDeliveryInternational = '022';
    case FragileInternational = '024';
    case SaturdayExpress = '025';
    case SaturdayDelivery = '027';
    case MiddayExpress = '028';
    case FreeToPlaceOfUse = '029';
    case PersonalDelivery = '045';
    case ContractPersonalDelivery = '047';
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
    case PreferredTimeWindow = '118';
    case RegisteredMail = '121';
    case PreferredNeighbor = '122';
    case PreferredDropLocation = '123';
    case ImmediateReturn = '142';
    case PreferredDay = '143';
    case PersonalDeliveryRegistered = '149';
    case RegisteredMailSimple = '152';
    case FormatP = '153';
    case FormatB = '154';
    case BusinessParcelStamp = '179';
    case NotificationWithoutDeliveryAttempt = '186';
    case SignatureRequired = '187';
    case CustomsClearanceDdp = '194';
    case IndividualExportDeclaration = '195';
    case ExportDeclaration = '199';
    case IndividualImportDeclaration = '200';
    case Eco = '202';
}
