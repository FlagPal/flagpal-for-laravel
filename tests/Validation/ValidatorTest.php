<?php

use Rapkis\FlagPal\Validation\Validator;

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
                'rule' => 'boolean',
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
                'rule' => 'equal',
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
                'rule' => 'equal',
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
                'rule' => 'equal',
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
                'rule' => 'equal',
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
                'rule' => 'equal',
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
                'rule' => 'in',
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
                'rule' => 'in',
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
                'rule' => 'equal',
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
                'rule' => 'equal',
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
                'rule' => 'equal',
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
                'rule' => 'contains',
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
                'rule' => 'contains',
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
                'rule' => 'contains',
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
                'rule' => 'contains',
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
                'rule' => 'in',
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
                'rule' => 'in',
                'value' => ['foo', 'bar'],
            ],
        ],
        false,
    ],
]);
