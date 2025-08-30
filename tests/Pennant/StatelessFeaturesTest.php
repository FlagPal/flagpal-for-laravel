<?php

use FlagPal\FlagPal\Pennant\StatelessFeatures;

it('stores features in a readonly property', function () {
    $features = ['feature1' => 'value1', 'feature2' => 'value2'];
    $statelessFeatures = new StatelessFeatures($features);

    expect($statelessFeatures->features)->toBe($features);
});

it('serializes features to JSON', function () {
    $features = ['feature1' => 'value1', 'feature2' => 'value2'];
    $statelessFeatures = new StatelessFeatures($features);

    expect($statelessFeatures->featureScopeSerialize())->toBe(json_encode($features));
});

it('handles empty features array', function () {
    $statelessFeatures = new StatelessFeatures([]);

    expect($statelessFeatures->features)->toBe([])
        ->and($statelessFeatures->featureScopeSerialize())->toBe('[]');
});

it('handles complex nested data structures', function () {
    $features = [
        'feature1' => 'value1',
        'feature2' => ['nested' => 'value', 'array' => [1, 2, 3]],
        'feature3' => (object) ['property' => 'value'],
    ];
    $statelessFeatures = new StatelessFeatures($features);

    expect($statelessFeatures->featureScopeSerialize())->toBe(json_encode($features));
});
