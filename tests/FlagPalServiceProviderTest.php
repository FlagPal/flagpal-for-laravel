<?php

use Rapkis\FlagPal\Resources\Feature;
use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;
use Rapkis\FlagPal\Resources\Metric;
use Swis\JsonApi\Client\Interfaces\ClientInterface;
use Swis\JsonApi\Client\Interfaces\ItemInterface;
use Swis\JsonApi\Client\Interfaces\TypeMapperInterface;

it('adds a missing slash for the api url', function () {
    config(['flagpal.base_url' => 'foo/bar']);
    $client = app(ClientInterface::class);
    expect($client->getBaseUri())->toBe('foo/bar/');

    config(['flagpal.base_url' => 'foo/bar/']);
    $client = app(ClientInterface::class);
    expect($client->getBaseUri())->toBe('foo/bar/');
});

it('maps resources to ItemMapper', function () {
    /** @var TypeMapperInterface $mapper */
    $mapper = app(TypeMapperInterface::class);
    $resources = [
        Feature::class,
        Funnel::class,
        FeatureSet::class,
        Metric::class,
    ];

    foreach ($resources as $resource) {
        /** @var ItemInterface $item */
        $item = app($resource);
        expect($mapper->hasMapping($item->getType()))->toBeTrue();
    }
});
