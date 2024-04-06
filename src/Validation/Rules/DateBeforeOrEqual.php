<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class DateBeforeOrEqual extends AbstractRule
{
    protected static array $declaration = ['before_or_equal:'];
}
