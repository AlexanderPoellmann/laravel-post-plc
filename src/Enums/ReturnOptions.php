<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum ReturnOptions: int
{
    case Instant = 1;
    case AfterDays = 2;
    case Obsolete = 3;
}
