<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum ServiceMethods: string
{
    case ImportShipment = 'ImportShipment';
    case ImportShipmentAndGenerateBarcode = 'ImportShipmentAndGenerateBarcode';
    case ImportAddress = 'ImportAddress';
    case PerformEndOfDay = 'PerformEndOfDay';
    case PerformEndOfDaySelect = 'PerformEndOfDaySelect';
    case CancelShipments = 'CancelShipments';
    case GetAllowedServicesForCountry = 'GetAllowedServicesForCountry';
    case GetAvailableTimeWindowsForPickupOrder = 'GetAvailableTimeWindowsForPickupOrder';
    case ImportPickupOrder = 'ImportPickupOrder';
    case CancelPickupOrder = 'CancelPickupOrder';
}
