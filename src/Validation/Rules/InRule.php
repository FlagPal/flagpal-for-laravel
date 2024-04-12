<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class InRule extends AbstractRule
{
    protected static array $declaration = ['required', 'in:'];
}
