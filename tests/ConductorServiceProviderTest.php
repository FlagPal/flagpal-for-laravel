<?php

use Rapkis\Conductor\Resources\Feature;
use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;
use Rapkis\Conductor\Resources\Metric;
use Swis\JsonApi\Client\Client;
use Swis\JsonApi\Client\Interfaces\ClientInterface;
use Swis\JsonApi\Client\Interfaces\ItemInterface;
use Swis\JsonApi\Client\Interfaces\TypeMapperInterface;

it('adds a missing slash for the api uri', function () {
    config(['conductor.base_uri' => 'foo/bar']);
    $client = app(ClientInterface::class);
    expect($client->getBaseUri())->toBe('foo/bar/');

    config(['conductor.base_uri' => 'foo/bar/']);
    $client = app(ClientInterface::class);
    expect($client->getBaseUri())->toBe('foo/bar/');
});

it('sets a default bearer token', function () {
    /** @var Client $client */
    $client = app(ClientInterface::class);
    expect($client->getDefaultHeaders())->toHaveKey('Authorization');
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
