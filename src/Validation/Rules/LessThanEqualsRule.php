<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class LessThanEqualsRule extends AbstractRule
{
    protected static array $declaration = ['lte:'];
}
