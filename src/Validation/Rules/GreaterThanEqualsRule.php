<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class GreaterThanEqualsRule extends AbstractRule
{
    protected static array $declaration = ['gte:'];
}
