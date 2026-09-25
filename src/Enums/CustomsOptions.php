<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum CustomsOptions: int
{
    case VerkaufVonWaren = 1;
    case Geschenk = 2;
    case Dokumente = 3;
    case Warenmuster = 4;
    case Rueckwaren = 5;
    case Anderes = 6;
}
