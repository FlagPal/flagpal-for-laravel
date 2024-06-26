<?php

use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use Rapkis\FlagPal\Actions\ResolveFeaturesFromFunnel;
use Rapkis\FlagPal\EnteredFunnel;
use Rapkis\FlagPal\FlagPal;
use Rapkis\FlagPal\Repositories\ActorRepository;
use Rapkis\FlagPal\Repositories\FunnelRepository;
use Rapkis\FlagPal\Repositories\MetricTimeSeriesRepository;
use Rapkis\FlagPal\Resources\Actor;
use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;
use Rapkis\FlagPal\Resources\Metric;
use Rapkis\FlagPal\Resources\MetricTimeSeries;
use Swis\JsonApi\Client\Collection;
use Swis\JsonApi\Client\Document;
use Swis\JsonApi\Client\ErrorCollection;
use Swis\JsonApi\Client\Interfaces\DocumentInterface;
use Swis\JsonApi\Client\ItemHydrator;

it('loads funnels from API', function () {
    $funnelRepository = $this->createMock(FunnelRepository::class);

    /** @var FlagPal $flagPal */
    $flagPal = $this->app->make(FlagPal::class, ['funnelRepository' => $funnelRepository]);

    $document = $this->createStub(DocumentInterface::class);
    $document->method('getData')->willReturn(new Collection());

    $funnelRepository->expects($this->once())
        ->method('all')
        ->with([
            'filter' => ['active' => true],
            'include' => 'featureSets,metrics',
        ])
        ->willReturn($document);

    $flagPal->resolveFeatures();
});

it('handles API errors', function (?string $logDriver) {
    config(['flagpal.log.driver' => $logDriver]);

    $logManager = $this->createStub(LogManager::class);

    $logger = $this->createMock(LoggerInterface::class);
    $logManager->method('driver')->willReturn($logger);

    $funnelRepository = $this->createStub(FunnelRepository::class);

    $flagPal = $this->app->make(FlagPal::class, [
        'funnelRepository' => $funnelRepository,
        'log' => $logManager,
    ]);

    $errors = new ErrorCollection([new \Swis\JsonApi\Client\Error('123', null, '401', '401')]);
    $document = new Document();
    $document->setErrors($errors);

    $funnelRepository->method('all')
        ->willReturn($document);

    $logger->expects($logDriver ? $this->once() : $this->never())
        ->method('error')
        ->with('FlagPal failed to load funnels', ['document' => ['errors' => $errors->toArray()]]);

    $flagPal->resolveFeatures();

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'flagpal-funnels-test project-'.json_encode($parameters);

    expect(Cache::has($cacheKey))->toBeFalse();
})->with([
    ['default'],
    ['null'],
    [null],
]);

it('caches funnels', function (?string $driver) {
    config(['flagpal.cache.driver' => $driver]);

    $cache = app(CacheManager::class);

    $funnelRepository = $this->createMock(FunnelRepository::class);

    $flagPal = $this->app->make(FlagPal::class, ['funnelRepository' => $funnelRepository]);

    $document = $this->createStub(DocumentInterface::class);
    $document->method('getData')->willReturn(new Collection());

    $funnelRepository->expects($this->once())
        ->method('all')
        ->willReturn($document);

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'flagpal-funnels-My Project-'.json_encode($parameters);

    expect($cache->has($cacheKey))->toBeFalse();

    $flagPal->resolveFeatures();

    expect($cache->has($cacheKey))->toBeTrue()
        ->and($cache->get($cacheKey))
        ->toBeInstanceOf(\Illuminate\Support\Collection::class);

    // won't hit the API a second time
    $flagPal->resolveFeatures();
})->with([
    ['default'],
    ['array'],
    [null],
]);

it('resolves features from all funnels', function () {
    $resolver = $this->createMock(ResolveFeaturesFromFunnel::class);

    /** @var CacheManager $cache */
    $cache = app(CacheManager::class);

    /** @var FlagPal $flagPal */
    $flagPal = $this->app->make(FlagPal::class, ['resolver' => $resolver]);

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'flagpal-funnels-My Project-'.json_encode($parameters);

    /** @var ItemHydrator $hydrator */
    $hydrator = app(ItemHydrator::class);

    /** @var Funnel $funnel */
    $funnel = $hydrator->hydrate(new Funnel(), [
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 100,
        Funnel::RULES => [],
        'featureSets' => [
            [
                'id' => '5678',
                FeatureSet::FEATURES => ['test' => 'foo', 'bar' => ['baz']],
            ],
        ],
    ], '1234');

    $cache->set($cacheKey, new \Illuminate\Support\Collection([$funnel]));

    $resolver->expects($this->once())
        ->method('__invoke')
        ->with($funnel, ['current' => 'features'])
        ->willReturn($funnel->featureSets->first());

    $features = $flagPal->resolveFeatures(['current' => 'features']);

    expect($features)->toBe([
        'current' => 'features',
        'test' => 'foo',
        'bar' => ['baz'],
    ])->and($flagPal->getEnteredFunnels())->toEqual([
        '1234' => new EnteredFunnel($funnel, $funnel->featureSets->first()),
    ]);
});

it('skips funnel if no set was resolved', function () {
    /** @var CacheManager $cache */
    $cache = app(CacheManager::class);

    $flagPal = $this->app->make(FlagPal::class);

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'flagpal-funnels-My Project-'.json_encode($parameters);

    /** @var ItemHydrator $hydrator */
    $hydrator = app(ItemHydrator::class);

    /** @var Funnel $funnel */
    $funnel = $hydrator->hydrate(new Funnel(), [
        Funnel::ACTIVE => true,
        Funnel::PERCENT => 100,
        Funnel::RULES => [['feature' => 'current', 'rule' => 'equal', 'value' => null]],
        'featureSets' => [
            [
                'id' => '5678',
                FeatureSet::FEATURES => ['test' => 'foo', 'bar' => ['baz']],
            ],
        ],
    ], '1234');

    $cache->set($cacheKey, new \Illuminate\Support\Collection([$funnel]));

    $features = $flagPal->resolveFeatures(['current' => 'features']);

    expect($features)->toBe([
        'current' => 'features',
    ])->and($flagPal->getEnteredFunnels())->toBe([]);
});

it('switches between projects', function () {
    config([
        'flagpal.default_project' => 'test project',
        'flagpal.projects' => [
            'test project' => [
                'name' => 'test project',
                'bearer_token' => 'foo bar secret',
            ],
            'other project' => [
                'name' => 'other project',
                'bearer_token' => 'other project secret',
            ],
        ],
    ]);

    $funnelRepository = $this->createMock(FunnelRepository::class);

    $flagPal = $this->app->make(FlagPal::class, ['funnelRepository' => $funnelRepository]);

    $document = $this->createStub(DocumentInterface::class);
    $document->method('getData')->willReturn(new Collection());

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];

    $funnelRepository->expects($this->once())
        ->method('all')
        ->with($parameters, ['Authorization' => 'Bearer other project secret'])
        ->willReturn($document);

    $flagPal
        ->asProject('other project')
        ->resolveFeatures();
});

it('records a metric', function (bool $hasErrors) {
    $metricTimeSeriesRepository = $this->createMock(MetricTimeSeriesRepository::class);

    /** @var FlagPal $flagPal */
    $flagPal = $this->app->make(FlagPal::class, ['metricTimeSeriesRepository' => $metricTimeSeriesRepository]);

    $document = new Document();
    if ($hasErrors) {
        $errors = new ErrorCollection([new \Swis\JsonApi\Client\Error('123', null, '401', '401')]);
        $document->setErrors($errors);
    }

    $metric = (new Metric())->setId('123');
    $set = (new FeatureSet())->setId('123');
    $payload = app(ItemHydrator::class)->hydrate(new MetricTimeSeries(), [
        MetricTimeSeries::METRIC => $metric->toJsonApiArray(),
        MetricTimeSeries::FEATURE_SET => $set->toJsonApiArray(),
        MetricTimeSeries::VALUE => 100,
        MetricTimeSeries::TIME_SEGMENT => $date = Carbon::now(),
    ]);

    $metricTimeSeriesRepository->expects($this->once())
        ->method('create')
        ->with($payload)
        ->willReturn($document);

    $success = $flagPal->recordMetric($metric, $set, 100, $date);

    if ($hasErrors) {
        expect($success)->toBeFalse();
    } else {
        expect($success)->toBeTrue();
    }
})->with([
    [false],
    [true],
]);

it('gets actor by reference', function (DocumentInterface $repositoryResult, ?Actor $expected) {
    $actorRepository = $this->createMock(ActorRepository::class);

    /** @var FlagPal $flagPal */
    $flagPal = $this->app->make(FlagPal::class, ['actorRepository' => $actorRepository]);

    $actorRepository->expects($this->once())
        ->method('find')
        ->with('test_actor')
        ->willReturn($repositoryResult);

    expect($flagPal->getActor('test_actor'))->toEqual($expected);
})->with([
    [(new Document())->setData(new Actor()), new Actor()],
    [new Document(), null],
]);

it('saves an actor', function () {
    $actorRepository = $this->createMock(ActorRepository::class);

    /** @var FlagPal $flagPal */
    $flagPal = $this->app->make(FlagPal::class, ['actorRepository' => $actorRepository]);

    $actor = new Actor();

    $actorRepository->expects($this->once())
        ->method('create')
        ->with($actor)
        ->willReturn((new Document())->setData($actor));

    expect($flagPal->saveActor($actor))->toBe($actor);
});

it('saves features for an actor', function () {
    $actorRepository = $this->createMock(ActorRepository::class);

    /** @var FlagPal $flagPal */
    $flagPal = $this->app->make(FlagPal::class, ['actorRepository' => $actorRepository]);

    /** @var ItemHydrator $itemHydrator */
    $itemHydrator = app(ItemHydrator::class);
    $actor = $itemHydrator->hydrate(new Actor(), [Actor::FEATURES => ['foo_feature' => 'foo_value']]);
    $actor->setId('test_actor');

    $actorRepository->expects($this->once())
        ->method('create')
        ->with($actor)
        ->willReturn((new Document())->setData($actor));

    expect($flagPal->saveActorFeatures('test_actor', ['foo_feature' => 'foo_value']))->toBe($actor);
});
