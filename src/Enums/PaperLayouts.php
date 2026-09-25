<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PaperLayouts: string
{
    case A5T = '2xA5inA4';
    case A5 = 'A5';
    case A4 = 'A4';
}
