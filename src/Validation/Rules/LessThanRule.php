<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class LessThanRule extends AbstractRule
{
    protected static array $declaration = ['lt:'];
}
