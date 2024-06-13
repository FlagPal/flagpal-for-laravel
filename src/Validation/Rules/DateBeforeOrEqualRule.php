<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class DateBeforeOrEqualRule extends AbstractRule
{
    protected static array $declaration = ['before_or_equal:'];
}
