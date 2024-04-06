<?php

use Rapkis\Conductor\Validation\Validator;

it('validates features by rules', function (array $features, array $rules, bool $expected) {
    /** @var Validator $validator */
    $validator = app(Validator::class);

    expect($validator->passes($features, $rules))->toBe($expected);
})->with([
    [
        [],
        [],
        true,
    ],
    [
        ['green_button' => false],
        [
            [
                'feature' => 'green_button',
                'rule' => 'BooleanRule',
                'value' => false,
            ],
        ],
        true,
    ],
    [
        ['green_button' => false],
        [
            [
                'feature' => 'green_button',
                'rule' => 'EqualRule',
                'value' => null,
            ],
        ],
        false,
    ],
    [
        ['green_button' => false],
        [],
        true,
    ],
    [
        [],
        [
            [
                'feature' => 'red_button',
                'rule' => 'EqualRule',
                'value' => true,
            ],
        ],
        false,
    ],
    [
        ['red_button' => true],
        [
            [
                'feature' => 'red_button',
                'rule' => 'EqualRule',
                'value' => true,
            ],
        ],
        true,
    ],
    [
        [],
        [
            [
                'feature' => 'red_button',
                'rule' => 'EqualRule',
                'value' => null,
            ],
        ],
        true,
    ],
    [
        ['green_button' => null],
        [
            [
                'feature' => 'red_button',
                'rule' => 'EqualRule',
                'value' => null,
            ],
        ],
        true,
    ],
    [
        ['green_button' => 'foo'],
        [
            [
                'feature' => 'green_button',
                'rule' => 'InRule',
                'value' => ['foo', 'bar'],
            ],
        ],
        true,
    ],
    [
        ['green_button' => 'old'],
        [
            [
                'feature' => 'green_button',
                'rule' => 'InRule',
                'value' => ['foo', 'bar'],
            ],
        ],
        false,
    ],
    [
        ['green_button' => ['array', 'foo', 'bar']],
        [
            [
                'feature' => 'green_button',
                'rule' => 'EqualRule',
                'value' => ['array', 'foo', 'bar'],
            ],
        ],
        true,
    ],
    [
        ['green_button' => ['array', 'foo', 'bar']],
        [
            [
                'feature' => 'green_button',
                'rule' => 'EqualRule',
                'value' => [],
            ],
        ],
        false,
    ],
    [
        ['green_button' => ['array', 'foo', 'bar']],
        [
            [
                'feature' => 'green_button',
                'rule' => 'EqualRule',
                'value' => null,
            ],
        ],
        false,
    ],
    [
        ['green_button' => 'test'],
        [
            [
                'feature' => 'green_button',
                'rule' => 'ContainsRule',
                'value' => null,
            ],
        ],
        false,
    ],
    [
        ['green_button' => 'est'],
        [
            [
                'feature' => 'green_button',
                'rule' => 'ContainsRule',
                'value' => 'test',
            ],
        ],
        true,
    ],
    [
        ['green_button' => ['est', 'ignored']],
        [
            [
                'feature' => 'green_button',
                'rule' => 'ContainsRule',
                'value' => 'test',
            ],
        ],
        true,
    ],
    [
        ['green_button' => []],
        [
            [
                'feature' => 'green_button',
                'rule' => 'ContainsRule',
                'value' => ['test'],
            ],
        ],
        false,
    ],
    [
        ['green_button' => 'foo'],
        [
            [
                'feature' => 'green_button',
                'rule' => 'InRule',
                'value' => ['foo', 'bar'],
            ],
        ],
        true,
    ],
    [
        ['green_button' => ''],
        [
            [
                'feature' => 'green_button',
                'rule' => 'InRule',
                'value' => ['foo', 'bar'],
            ],
        ],
        false,
    ],
]);
