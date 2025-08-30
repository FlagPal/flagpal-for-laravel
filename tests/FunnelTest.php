<?php

use FlagPal\FlagPal\Resources\Funnel;
use Swis\JsonApi\Client\ItemHydrator;

it('has feature sets as a relation', function () {
    /** @var ItemHydrator $hydrator */
    $hydrator = app(ItemHydrator::class);

    /** @var Funnel $funnel */
    $funnel = $hydrator->hydrate(new Funnel, [
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 100,
        Funnel::RULES => [],
        'featureSets' => [
            ['id' => '5678'],
        ],
    ], '1234');

    expect($funnel->featureSets)->toHaveCount(1);
});

it('has metrics as a relation', function () {
    /** @var ItemHydrator $hydrator */
    $hydrator = app(ItemHydrator::class);

    /** @var Funnel $funnel */
    $funnel = $hydrator->hydrate(new Funnel, [
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 100,
        Funnel::RULES => [],
        'metrics' => [
            ['id' => '5678'],
        ],
    ], '1234');

    expect($funnel->metrics)->toHaveCount(1);
});
