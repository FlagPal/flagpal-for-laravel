<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class DateAfterRule extends AbstractRule
{
    protected static array $declaration = ['date_after:'];
}
