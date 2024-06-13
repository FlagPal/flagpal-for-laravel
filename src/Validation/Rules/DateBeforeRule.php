<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class DateBeforeRule extends AbstractRule
{
    protected static array $declaration = ['before:'];
}
