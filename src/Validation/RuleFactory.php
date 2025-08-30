<?php

namespace FlagPal\FlagPal\Validation;

use FlagPal\FlagPal\Contracts\Rules\Rule;
use Illuminate\Support\Str;

class RuleFactory
{
    public function make(string $name): Rule
    {
        $className = __NAMESPACE__.'\\Rules\\'.Str::studly($name).'Rule';
        if (! class_exists($className)) {
            throw new \InvalidArgumentException("Rule \"{$name}\" does not exist");
        }

        return new $className;
    }
}
