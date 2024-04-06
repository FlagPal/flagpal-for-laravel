<?php

use Rapkis\Conductor\Validation\RuleFactory;
use Rapkis\Conductor\Validation\Rules\EqualRule;

it('can make a rule', function () {
    $factory = new RuleFactory();
    expect($factory->make('EqualRule'))->toBeInstanceOf(EqualRule::class);
});

it('throws exception if rule class does not exist', function () {
    $factory = new RuleFactory();

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Rule "does not exist" does not exist');

    $factory->make('does not exist');
});
