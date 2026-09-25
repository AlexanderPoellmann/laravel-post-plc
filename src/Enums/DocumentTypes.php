<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Enums;

enum DocumentTypes: int
{
    case Invoice = 3;
    case CertificateOfOrigin = 4;
    case ExportLicense = 6;
    case Waybill = 15;
}
