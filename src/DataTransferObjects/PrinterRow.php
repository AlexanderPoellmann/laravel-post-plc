<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterEncoding;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;
use Spatie\LaravelData\Data;

class PrinterRow extends Data
{
    public function __construct(
        public readonly ?PrinterLanguages $LanguageID = null,
        public readonly ?LabelSizes $LabelFormatID = null,
        public readonly ?PaperLayouts $PaperLayoutID = null,
        public readonly ?PrinterEncoding $Encoding = null,
    ) {}
}
