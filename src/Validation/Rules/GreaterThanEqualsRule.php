<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class GreaterThanEqualsRule extends AbstractRule
{
    protected static array $declaration = ['gte:'];
}
