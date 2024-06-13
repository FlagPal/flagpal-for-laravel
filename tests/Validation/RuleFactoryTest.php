<?php

use Rapkis\FlagPal\Validation\RuleFactory;
use Rapkis\FlagPal\Validation\Rules\DateBeforeOrEqualRule;

it('can make a rule', function () {
    $factory = new RuleFactory();
    expect($factory->make('date_before_or_equal'))->toBeInstanceOf(DateBeforeOrEqualRule::class);
});

it('throws exception if rule class does not exist', function () {
    $factory = new RuleFactory();

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Rule "does not exist" does not exist');

    $factory->make('does not exist');
});
