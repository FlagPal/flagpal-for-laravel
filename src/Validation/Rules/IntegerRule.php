<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class IntegerRule extends AbstractRule
{
    protected static array $declaration = [
        'numeric',
        'integer',
    ];
}
