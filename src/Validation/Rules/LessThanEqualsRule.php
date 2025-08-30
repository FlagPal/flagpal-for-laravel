<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class LessThanEqualsRule extends AbstractRule
{
    protected static array $declaration = ['lte:'];
}
