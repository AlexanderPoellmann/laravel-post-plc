<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum SecurePickupLocationTypes: int
{
    case FrontDoor = 1;
    case ApartmentDoor = 2;
    case Mailbox = 3;
    case Garage = 4;
    case BehindFence = 5;
    case Other = 6;
}
