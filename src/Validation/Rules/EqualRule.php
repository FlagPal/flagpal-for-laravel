<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Validation\Rules;

class EqualRule extends AbstractRule
{
    protected static array $declaration = [];

    public function validate(mixed $value, array $parameters): bool
    {
        if (is_array($value)) {
            return serialize($value) === serialize($parameters);
        }

        return $value === ($parameters[0] ?? null);
    }
}
