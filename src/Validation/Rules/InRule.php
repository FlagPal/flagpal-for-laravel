<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class InRule extends AbstractRule
{
    protected static array $declaration = ['required', 'in:'];
}
