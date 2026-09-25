<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PrinterEncoding: string
{
    case UTF = 'UTF-8';
    case WINDOWS = 'WINDOWS-1252';
}
