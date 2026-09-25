<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Contracts;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;

interface CustomsRequirementResolver
{
    public function requiresCustoms(?AddressRow $sender, AddressRow $recipient): bool;
}
