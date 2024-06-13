<?php

use Rapkis\FlagPal\Support\Raffle;

test('can draw a winner', function () {
    $raffle = new Raffle();

    expect($raffle->draw(['contestant' => 1]))->toBe('contestant');
});

test('can force a winner', function () {
    $raffle = new Raffle();
    $raffle->alwaysDraw('winner');

    expect($raffle->draw(['contestant' => 1]))->toBe('winner');
});

test('validates empty pool', function () {
    $raffle = new Raffle();
    $raffle->draw([]);
})->throws(InvalidArgumentException::class, 'The raffle pool must not be empty');

test('validates weights', function (array $contestants) {
    $raffle = new Raffle();

    $raffle->draw($contestants);
})->throws(InvalidArgumentException::class, 'All weights must always be a positive integer')
    ->with([
        [
            [
                'contestant' => 0,
            ],
        ],
        [
            [
                'contestant_one' => 10,
                'contestant_two' => -1,
            ],
        ],
    ]);
