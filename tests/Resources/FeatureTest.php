<?php

use FlagPal\FlagPal\Resources\Feature;
use Illuminate\Support\Carbon;

test('castToKind casts to string', function () {
    expect(Feature::castToKind('string', 'test'))->toBe('test')
        ->and(Feature::castToKind('string', 123))->toBe('123')
        ->and(Feature::castToKind('string', true))->toBe('1');

    $array = ['key' => 'value'];
    expect(Feature::castToKind('string', $array))->toBe(json_encode($array));
});

test('castToKind casts to integer', function () {
    expect(Feature::castToKind('integer', '123'))->toBe(123)
        ->and(Feature::castToKind('integer', 123))->toBe(123)
        ->and(Feature::castToKind('integer', true))->toBe(1)
        ->and(Feature::castToKind('integer', false))->toBe(0)
        ->and(Feature::castToKind('integer', 'test'))->toBe(0);
});

test('castToKind casts to boolean', function () {
    expect(Feature::castToKind('boolean', 'true'))->toBeTrue()
        ->and(Feature::castToKind('boolean', '1'))->toBeTrue()
        // In PHP, any non-empty string is cast to true, including 'false'
        ->and(Feature::castToKind('boolean', 'false'))->toBeTrue()
        ->and(Feature::castToKind('boolean', '0'))->toBeFalse()
        ->and(Feature::castToKind('boolean', ''))->toBeFalse()
        ->and(Feature::castToKind('boolean', 1))->toBeTrue()
        ->and(Feature::castToKind('boolean', 0))->toBeFalse()
        ->and(Feature::castToKind('boolean', true))->toBeTrue()
        ->and(Feature::castToKind('boolean', false))->toBeFalse();
});

test('castToKind casts to array', function () {
    $array = ['key' => 'value'];
    $json = json_encode($array);
    expect(Feature::castToKind('array', $json))->toBe($array)
        ->and(Feature::castToKind('array', $array))->toBe($array)
        ->and(Feature::castToKind('array', 'test'))->toBeNull();
});

test('castToKind casts to date', function () {
    $dateString = '2023-01-01';
    $result = Feature::castToKind('date', $dateString);
    expect($result)->toBeInstanceOf(Carbon::class)
        ->and($result->format('Y-m-d'))->toBe($dateString);

    $datetimeString = '2023-01-01 12:00:00';
    $result = Feature::castToKind('date', $datetimeString);
    expect($result)->toBeInstanceOf(Carbon::class)
        ->and($result->format('Y-m-d H:i:s'))->toBe($datetimeString);
});

test('castToKind returns original value for unknown kind', function () {
    expect(Feature::castToKind('unknown', 'test'))->toBe('test')
        ->and(Feature::castToKind('unknown', 123))->toBe(123)
        ->and(Feature::castToKind('unknown', true))->toBe(true)
        ->and(Feature::castToKind('unknown', ['key' => 'value']))->toBe(['key' => 'value']);
});
