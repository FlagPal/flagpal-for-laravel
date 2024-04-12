<?php

namespace Rapkis\Conductor\Validation\Rules;

use Rapkis\Conductor\Contracts\Rules\Rule;

abstract class AbstractRule implements Rule
{
    protected static array $declaration;

    public static function declaration(): array
    {
        return static::$declaration;
    }

    public function validate(mixed $value, array $parameters): ?bool
    {
        return null;
    }
}
