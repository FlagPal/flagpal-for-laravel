<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class DateAfterOrEqualRule extends AbstractRule
{
    protected static array $declaration = ['date_after_or_equal'];
}
