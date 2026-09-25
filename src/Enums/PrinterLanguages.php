<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum PrinterLanguages: string
{
    case ZPL2 = 'ZPL2';
    case PDF = 'PDF';
    case JPEG = 'JPEG';
    case GIF = 'GIF';
    case PNG = 'PNG';
    case PDFZPL2 = 'PDFZPL2';
    case None = 'None';
}
