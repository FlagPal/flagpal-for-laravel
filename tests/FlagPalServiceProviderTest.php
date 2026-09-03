<?php

use FlagPal\FlagPal\Exceptions\InvalidConfigurationException;
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\FlagPalServiceProvider;
use FlagPal\FlagPal\Pennant\FlagPalDriver;
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
                'project' => 'bar',
            ],
        ],
    ]);

    /** @var FlagPalDriver $driver */
    $driver = Laravel\Pennant\Feature::store('foo')->getDriver();
    expect($driver->flagPal->getProject())->toBe('foo');

    $driver = Laravel\Pennant\Feature::store('bar')->getDriver();
    expect($driver->flagPal->getProject())->toBe('bar');
});

it('throws a clear exception when switching to an unknown project', function () {
    config([
        'flagpal.projects' => [
            'foo' => [],
        ],
        'flagpal.default_project' => 'foo',
    ]);

    /** @var FlagPal $flagPal */
    $flagPal = app(FlagPal::class);

    $flagPal->asProject('does-not-exist');
})->throws(InvalidConfigurationException::class, 'FlagPal project "does-not-exist" is not defined');

it('registers a default flagpal Pennant store when the app has not defined one', function () {
    config(['pennant.stores' => []]);

    (new FlagPalServiceProvider(app()))->packageBooted();

    expect(config('pennant.stores.flagpal'))->toBe(['driver' => FlagPalDriver::NAME]);
});

it('does not override an app-defined flagpal Pennant store', function () {
    config(['pennant.stores.flagpal' => ['driver' => 'flagpal', 'project' => 'foo']]);

    (new FlagPalServiceProvider(app()))->packageBooted();

    expect(config('pennant.stores.flagpal'))->toBe(['driver' => 'flagpal', 'project' => 'foo']);
});

it('resolves FlagPal as a scoped/shared instance within a request', function () {
    expect(app(FlagPal::class))->toBe(app(FlagPal::class));
});
