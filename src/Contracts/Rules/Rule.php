<?php

namespace Rapkis\Conductor\Contracts\Rules;

interface Rule
{
    public static function declaration(): array;

    public function validate(mixed $value, array $parameters): ?bool;
}
