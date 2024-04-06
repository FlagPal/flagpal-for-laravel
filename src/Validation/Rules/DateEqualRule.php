<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Validation\Rules;

class DateEqualRule extends AbstractRule
{
    protected static array $declaration = ['date_equals:'];
}
