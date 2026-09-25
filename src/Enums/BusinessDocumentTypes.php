<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum BusinessDocumentTypes: string
{
    case Receipt = 'Receipt';
    case InternationalCODMoneyOrder = 'InternationalCODMoneyOrder';
    case ParcelRegistrationCard = 'ParcelRegistrationCard';
    case CustomsDeclaration = 'CustomsDeclaration';
}
