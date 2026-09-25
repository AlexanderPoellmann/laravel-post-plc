<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum ReturnPaths: int
{
    case LandSea = 1;
    case Air = 2;
}
