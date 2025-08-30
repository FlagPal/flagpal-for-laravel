<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class DateBeforeOrEqualRule extends AbstractRule
{
    protected static array $declaration = ['before_or_equal:'];
}
