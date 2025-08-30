<?php

namespace FlagPal\FlagPal\Validation;

use FlagPal\FlagPal\Contracts\Rules\Rule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;

class Validator
{
    public function __construct(
        public readonly Factory $validator,
        public readonly RuleFactory $ruleFactory,
    ) {}

    public function passes(array $features, array $rules): bool
    {
        foreach ($rules as $rule) {
            /** @var Rule $rule */
            $ruleClass = $this->ruleFactory->make($rule['rule']);
            $valid = $ruleClass->validate($rule['value'], Arr::wrap($features[$rule['feature']] ?? []));

            if ($valid === false) {
                return false;
            }
        }

        return $this->validator->make($features, $this->transformRulesForLaravelValidator($rules))->passes();
    }

    protected function transformRulesForLaravelValidator(array $rules): array
    {
        $result = [];
        foreach ($rules as $rule) {
            $ruleClass = $this->ruleFactory->make($rule['rule']);
            $laravelRules = $ruleClass::declaration();
            foreach ($laravelRules as $key => $laravelRule) {
                if (str_ends_with($laravelRule, ':')) {
                    $value = implode(',', Arr::wrap($rule['value']));
                    $laravelRules[$key] = "$laravelRule{$value}";
                }
            }
            $result[$rule['feature']] = $laravelRules;
        }

        return $result;
    }
}
