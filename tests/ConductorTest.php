<?php

use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;
use Rapkis\Conductor\Actions\ResolveFeaturesFromFunnel;
use Rapkis\Conductor\Conductor;
use Rapkis\Conductor\Repositories\FunnelRepository;
use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;
use Swis\JsonApi\Client\Collection;
use Swis\JsonApi\Client\Document;
use Swis\JsonApi\Client\ErrorCollection;
use Swis\JsonApi\Client\Interfaces\DocumentInterface;
use Swis\JsonApi\Client\ItemHydrator;

it('loads funnels from API', function () {
    $funnelRepository = $this->createMock(FunnelRepository::class);
    $conductor = new Conductor(
        $funnelRepository,
        $this->createStub(ResolveFeaturesFromFunnel::class),
        null
    );

    $document = $this->createStub(DocumentInterface::class);
    $document->method('getData')->willReturn(new Collection());
    $funnelRepository->expects($this->once())
        ->method('all')
        ->with([
            'filter' => ['active' => true],
            'include' => 'featureSets,metrics',
        ])
        ->willReturn($document);

    $conductor->resolveFeatures();
});

it('handles API errors', function (bool $hasLogger) {
    $logger = $this->createMock(LoggerInterface::class);
    $funnelRepository = $this->createStub(FunnelRepository::class);
    $conductor = new Conductor(
        $funnelRepository,
        $this->createStub(ResolveFeaturesFromFunnel::class),
        null,
    );

    if ($hasLogger) {
        $conductor->setLogger($logger);
    }

    $errors = new ErrorCollection([new \Swis\JsonApi\Client\Error('123', null, '401', '401')]);
    $document = new Document();
    $document->setErrors($errors);

    $funnelRepository->method('all')
        ->willReturn($document);

    if ($hasLogger) {
        $logger->expects($this->once())
            ->method('error')
            ->with('Conductor failed to load funnels', ['document' => ['errors' => $errors->toArray()]]);
    } else {
        $logger->expects($this->never())->method('error');
    }

    $conductor->resolveFeatures();
})->with([
    [false],
    [true],
]);

it('caches funnels', function () {
    $cache = app(Repository::class);
    $funnelRepository = $this->createMock(FunnelRepository::class);

    $conductor = new Conductor(
        $funnelRepository,
        $this->createStub(ResolveFeaturesFromFunnel::class),
        $cache,
    );

    $document = $this->createStub(DocumentInterface::class);
    $document->method('getData')->willReturn(new Collection());
    $funnelRepository->expects($this->once())
        ->method('all')
        ->willReturn($document);

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'conductor-funnels-'.json_encode($parameters);

    expect($cache->has($cacheKey))->toBeFalse();
    $conductor->resolveFeatures();
    expect($cache->has($cacheKey))->toBeTrue()
        ->and($cache->get($cacheKey))
        ->toBeInstanceOf(\Illuminate\Support\Collection::class);

    // won't hit the API a second time
    $conductor->resolveFeatures();
});

it('resolves features from all funnels', function () {
    $resolver = $this->createMock(ResolveFeaturesFromFunnel::class);
    /** @var \Psr\SimpleCache\CacheInterface $cache */
    $cache = app(Repository::class);

    $conductor = new Conductor(
        $this->createStub(FunnelRepository::class),
        $resolver,
        $cache,
    );

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'conductor-funnels-'.json_encode($parameters);

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

    $features = $conductor->resolveFeatures(['current' => 'features']);

    expect($features)->toBe([
        'current' => 'features',
        'test' => 'foo',
        'bar' => ['baz'],
    ])->and($conductor->getEnteredFunnels())->toBe([
        '1234' => ['5678' => ['test' => 'foo', 'bar' => ['baz']]],
    ]);
});

it('skips funnel if no set was resolved', function () {
    /** @var \Psr\SimpleCache\CacheInterface $cache */
    $cache = app(Repository::class);

    $conductor = new Conductor(
        $this->createStub(FunnelRepository::class),
        $this->createStub(ResolveFeaturesFromFunnel::class),
        $cache,
    );

    $parameters = [
        'filter' => ['active' => true],
        'include' => 'featureSets,metrics',
    ];
    $cacheKey = 'conductor-funnels-'.json_encode($parameters);

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

    $features = $conductor->resolveFeatures(['current' => 'features']);

    expect($features)->toBe([
        'current' => 'features',
    ])->and($conductor->getEnteredFunnels())->toBe([]);
});
