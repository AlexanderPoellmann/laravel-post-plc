<?php

declare(strict_types=1);

arch('source files use strict types')
    ->expect('AlexanderPoellmann\\LaravelPostPlc')
    ->toUseStrictTypes();

arch('production code has no debugging helpers')
    ->expect('AlexanderPoellmann\\LaravelPostPlc')
    ->not->toUse(['dd', 'dump', 'ray']);
