<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class IntegerRule extends AbstractRule
{
    protected static array $declaration = [
        'numeric',
        'integer',
    ];
}
