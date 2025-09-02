<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class DateAfterOrEqualRule extends AbstractRule
{
    protected static array $declaration = ['after_or_equal:'];
}
