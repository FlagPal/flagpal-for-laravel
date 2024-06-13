<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class GreaterThanEqualsRule extends AbstractRule
{
    protected static array $declaration = ['gte:'];
}
