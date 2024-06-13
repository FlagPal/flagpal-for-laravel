<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Validation\Rules;

class ContainsRule extends AbstractRule
{
    protected static array $declaration = [];

    public function validate(mixed $value, array $parameters): bool
    {
        if (is_string($value)) {
            return str_contains($value, $parameters[0]);
        }

        if (is_array($value)) {
            return ! empty(array_intersect($value, $parameters));
        }

        return false;
    }
}
