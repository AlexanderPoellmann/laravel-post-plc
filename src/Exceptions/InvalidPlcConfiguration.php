<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Exceptions;

use InvalidArgumentException;

final class InvalidPlcConfiguration extends InvalidArgumentException
{
    public static function missingCredentials(): self
    {
        return new self('Austrian Post PLC credentials are incomplete. Configure client_id, org_unit_id and org_unit_guid.');
    }
}
