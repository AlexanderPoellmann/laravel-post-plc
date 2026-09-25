<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum ServiceMethods: string
{
    case ImportShipment = 'ImportShipment';
    case ImportShipmentAndGenerateBarcode = 'ImportShipmentAndGenerateBarcode';
    case ImportShipmentReturnImage = 'ImportShipmentReturnImage';
    case ImportShipmentForce = 'ImportShipmentForce';
    case ImportAddress = 'ImportAddress';
    case PerformEndOfDay = 'PerformEndOfDay';
    case PerformEndOfDaySelect = 'PerformEndOfDaySelect';
    case CancelShipments = 'CancelShipments';
    case GetAllowedServicesForCountry = 'GetAllowedServicesForCountry';
    case GetAvailableTimeWindowsForPickupOrder = 'GetAvailableTimeWindowsForPickupOrder';
    case ImportPickupOrderBusiness = 'ImportPickupOrderBusiness';
    case CancelPickupOrder = 'CancelPickupOrder';
    case BuildGroupageShipment = 'BuildGroupageShipment';
    case CompleteGroupageShipment = 'CompleteGroupageShipment';

    /** @deprecated PLC API v2.0 documents ImportPickupOrderBusiness. */
    case ImportPickupOrder = 'ImportPickupOrder';
}
