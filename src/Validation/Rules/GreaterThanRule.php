<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class GreaterThanRule extends AbstractRule
{
    protected static array $declaration = ['gt:'];
}
