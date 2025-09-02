<?php

use FlagPal\FlagPal\Support\Arr;

test('diff recursive with simple arrays', function () {
    $array1 = ['a' => 1, 'b' => 2, 'c' => 3];
    $array2 = ['a' => 1, 'b' => 3, 'd' => 4];
    $expected = ['b' => 2, 'c' => 3];

    expect(Arr::diffRecursive($array1, $array2))->toBe($expected);
});

test('diff recursive with nested arrays', function () {
    $array1 = [
        'a' => 1,
        'b' => [
            'c' => 3,
            'd' => 4,
        ],
        'e' => 5,
    ];
    $array2 = [
        'a' => 1,
        'b' => [
            'c' => 3,
            'd' => 5,
        ],
        'f' => 6,
    ];
    $expected = [
        'b' => [
            'c' => 3,
            'd' => 4,
        ],
        'e' => 5,
    ];

    expect(Arr::diffRecursive($array1, $array2))->toBe($expected);
});

test('diff recursive with deeply nested arrays', function () {
    $array1 = [
        'a' => [
            'b' => [
                'c' => 1,
                'd' => 2,
            ],
            'e' => 3,
        ],
        'f' => 4,
    ];
    $array2 = [
        'a' => [
            'b' => [
                'c' => 1,
                'd' => 3,
            ],
            'e' => 3,
        ],
        'g' => 5,
    ];
    $expected = [
        'a' => [
            'b' => [
                'c' => 1,
                'd' => 2,
            ],
            'e' => 3,
        ],
        'f' => 4,
    ];

    expect(Arr::diffRecursive($array1, $array2))->toBe($expected);
});
