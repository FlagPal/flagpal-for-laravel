<?php

namespace Rapkis\Conductor\Validation;

use Illuminate\Support\Str;
use Rapkis\Conductor\Contracts\Rules\Rule;

class RuleFactory
{
    public function make(string $name): Rule
    {
        $className = __NAMESPACE__.'\\Rules\\'.Str::studly($name).'Rule';
        if (! class_exists($className)) {
            throw new \InvalidArgumentException("Rule \"{$name}\" does not exist");
        }

        return new $className();
    }
}
