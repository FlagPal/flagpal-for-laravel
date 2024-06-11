<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class InRule extends AbstractRule
{
    protected static array $declaration = ['required', 'in:'];
}
