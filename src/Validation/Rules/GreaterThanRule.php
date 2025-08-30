<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class GreaterThanRule extends AbstractRule
{
    protected static array $declaration = ['gt:'];
}
