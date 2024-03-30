<?php

use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;
use Rapkis\Conductor\Resources\Goal;
use Swis\JsonApi\Client\Collection;

it('has feature sets as a relation', function () {
    $funnel = new Funnel();
    $funnel->setId('1234');
    $funnel->featureSets()->associate(new Collection((new FeatureSet())->setId('5678')));

    expect($funnel->toJsonApiArray())->toBe([
        'type' => 'funnels',
        'id' => '1234',
        'relationships' => [
            'feature_sets' => [
                'data' => [],
            ],
        ],
    ]);
});

it('has goals as a relation', function () {
    $funnel = new Funnel();
    $funnel->setId('1234');
    $funnel->goals()->associate(new Collection((new Goal())->setId('5678')));

    expect($funnel->toJsonApiArray())->toBe([
        'type' => 'funnels',
        'id' => '1234',
        'relationships' => [
            'goals' => [
                'data' => [],
            ],
        ],
    ]);
});
