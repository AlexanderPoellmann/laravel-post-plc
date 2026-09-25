<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PrinterLanguages: string
{
    case ZPL2 = 'ZPL2';
    case PDF = 'PDF';
}
