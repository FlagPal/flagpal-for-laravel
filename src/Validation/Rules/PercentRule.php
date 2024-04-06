<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class PercentRule extends AbstractRule
{
    protected static array $declaration = ['lte:'];
}
