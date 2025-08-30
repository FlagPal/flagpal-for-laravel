<?php

use FlagPal\FlagPal\Resources\Feature;
use FlagPal\FlagPal\Resources\FeatureSet;
use FlagPal\FlagPal\Resources\Funnel;
use FlagPal\FlagPal\Resources\Metric;
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

it('registers the pennant driver', function () {
    config([
        'flagpal.projects' => [
            'foo' => [],
            'bar' => [],
        ],
        'flagpal.default_project' => 'foo',
        'pennant.stores' => [
            'foo' => [
                'driver' => 'flagpal',
                'project' => null,
            ],

            'bar' => [
                'driver' => 'flagpal',
                'project' => 'Bar',
            ],
        ],
    ]);

    /** @var \FlagPal\FlagPal\Pennant\FlagPalDriver $driver */
    $driver = \Laravel\Pennant\Feature::store('foo')->getDriver();
    expect($driver->flagPal->getProject())->toBe('foo');

    $driver = \Laravel\Pennant\Feature::store('bar')->getDriver();
    expect($driver->flagPal->getProject())->toBe('Bar');
});
