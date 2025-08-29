<?php

use Rapkis\FlagPal\Actions\ResolveFeaturesFromFunnel;
use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;
use Rapkis\FlagPal\Support\Raffle;
use Rapkis\FlagPal\Validation\Validator;
use Swis\JsonApi\Client\ItemHydrator;

it('resolves a feature set from funnel', function (?array $rules) {
    /** @var ItemHydrator $hydrator */
    $hydrator = app(ItemHydrator::class);

    /** @var Funnel $funnel */
    $funnel = $hydrator->hydrate(new Funnel(), [
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 100,
        Funnel::RULES => $rules,
        'featureSets' => [
            [
                'id' => '8888',
            ],
            [
                'id' => '9999',
                FeatureSet::WEIGHT => 100,
            ],
        ],
    ], '1234');

    $validator = $this->createStub(Validator::class);
    $validator->method('passes')->willReturn(true);

    $raffle = $this->createMock(Raffle::class);
    $raffle->expects($this->once())->method('draw')->with([
        '8888' => 1,
        '9999' => 100,
    ])->willReturn('9999');

    $resolver = new ResolveFeaturesFromFunnel($validator, $raffle);
    $set = $resolver($funnel, []);

    expect($set)->toBeInstanceOf(FeatureSet::class)
        ->and($set->getId())->toBe('9999');
})->with([
    [[]],
    [null]
]);

it('skips funnel if disabled', function () {
    $funnel = new Funnel();
    $funnel2 = new Funnel([Funnel::ACTIVE => false]);

    $resolver = app(ResolveFeaturesFromFunnel::class);

    expect($resolver($funnel, []))->toBeNull()
        ->and($resolver($funnel2, []))->toBeNull();
});

it('skips funnel if not enough percent', function () {
    $funnel = new Funnel([
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 0,
    ]);

    $resolver = app(ResolveFeaturesFromFunnel::class);

    expect($resolver($funnel, []))->toBeNull();
});

it('skips funnel if validation fails', function () {
    $funnel = new Funnel([
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 100,
        Funnel::RULES => [],
    ]);

    $validator = $this->createStub(Validator::class);
    $validator->method('passes')->willReturn(false);

    $resolver = new ResolveFeaturesFromFunnel($validator, new Raffle());

    expect($resolver($funnel, []))->toBeNull();
});
