<?php

namespace Rapkis\Conductor;

use Carbon\CarbonInterval;
use DateTimeInterface;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Rapkis\Conductor\Actions\ResolveFeaturesFromFunnel;
use Rapkis\Conductor\Repositories\ActorRepository;
use Rapkis\Conductor\Repositories\FunnelRepository;
use Rapkis\Conductor\Repositories\MetricTimeSeriesRepository;
use Rapkis\Conductor\Resources\Actor;
use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;
use Rapkis\Conductor\Resources\Metric;
use Rapkis\Conductor\Resources\MetricTimeSeries;
use Swis\JsonApi\Client\InvalidResponseDocument;
use Swis\JsonApi\Client\ItemHydrator;

class Conductor
{
    /** @var array<string,array> */
    private array $projects;

    private string $project;

    /** @var array<EnteredFunnel> */
    private array $entered = [];

    private int $cacheTtlSeconds;

    public function __construct(
        protected readonly FunnelRepository $funnelRepository,
        protected readonly MetricTimeSeriesRepository $metricTimeSeriesRepository,
        protected readonly ActorRepository $actorRepository,
        protected readonly ResolveFeaturesFromFunnel $resolver,
        protected readonly ItemHydrator $itemHydrator,
        protected readonly CacheManager $cache,
        protected LogManager $log,
    ) {
        $this->projects = config('conductor.projects');
        $this->project = config('conductor.default_project');
        $this->cacheTtlSeconds = (int) config('conductor.cache.ttl', 60);
    }

    public function resolveFeatures(array $currentFeatures = []): array
    {
        $funnels = $this->loadFunnels();

        $features = $funnels->reduce(function (array $features, Funnel $funnel) {
            $set = ($this->resolver)($funnel, $features);

            if (! $set) {
                return $features;
            }

            $this->entered[$funnel->getId()] = new EnteredFunnel($funnel, $set);

            return array_merge($features, $set->features);
        }, $currentFeatures);

        return $features;
    }

    public function getEnteredFunnels(): array
    {
        return $this->entered;
    }

    public function recordMetric(Metric $metric, FeatureSet $set, int $value, ?DateTimeInterface $dateTime = null): bool
    {
        $item = $this->itemHydrator->hydrate(new MetricTimeSeries(), [
            MetricTimeSeries::METRIC => $metric->toJsonApiArray(),
            MetricTimeSeries::FEATURE_SET => $set->toJsonApiArray(),
            MetricTimeSeries::VALUE => $value,
            MetricTimeSeries::TIME_SEGMENT => $dateTime?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
        ]);

        $document = $this->metricTimeSeriesRepository->create($item, [], $this->headers());

        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            $this->log()?->error('Conductor failed to record a metric', ['document' => $document->toArray()]);

            return false;
        }

        return true;
    }

    public function getActor(string $reference): ?Actor
    {
        return $this->actorRepository->find($reference, [], $this->headers())->getData() ?: null;
    }

    public function saveActorFeatures(string $reference, array $features): Actor
    {
        $actor = $this->itemHydrator->hydrate(new Actor(), [
            Actor::FEATURES => $features,
        ]);
        $actor->setId($reference);

        return $this->saveActor($actor);
    }

    public function saveActor(Actor $actor): Actor
    {
        return $this->actorRepository->create($actor, [], $this->headers())->getData();
    }

    protected function loadFunnels(): Collection
    {
        $parameters = [
            'filter' => ['active' => true],
            'include' => 'featureSets,metrics',
        ];
        $cacheKey = "conductor-funnels-{$this->project}-".json_encode($parameters);

        if (($funnels = $this->cache()->get($cacheKey))) {
            return $funnels;
        }

        $document = $this->funnelRepository->all($parameters, $this->headers());
        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            $this->log()?->error('Conductor failed to load funnels', ['document' => $document->toArray()]);

            return new Collection();
        }

        $funnels = Collection::make($document->getData());
        $this->cache()->set(
            $cacheKey,
            $funnels,
            CarbonInterval::seconds($this->cacheTtlSeconds),
        );

        return $funnels;
    }

    public function asProject(string $project): self
    {
        $this->project = $project;

        return $this;
    }

    protected function log(): ?LoggerInterface
    {
        $driver = config('conductor.log.driver');

        return match ($driver) {
            null => null,
            'default' => $this->log->driver(),
            default => $this->log->driver($driver),
        };
    }

    protected function cache(): CacheInterface
    {
        $driver = config('conductor.cache.driver', 'array');

        return match ($driver) {
            'default' => $this->cache->driver(),
            default => $this->cache->driver($driver),
        };
    }

    protected function headers(): array
    {
        return ['Authorization' => "Bearer {$this->projects[$this->project]['bearer_token']}"];
    }
}
