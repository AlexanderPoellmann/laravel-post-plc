<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PickupLocationTypes: string
{
    case PersonalHandover = 'PersonalHandover';
    case Secure = 'Secure';
}
