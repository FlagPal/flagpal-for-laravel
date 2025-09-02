<?php

namespace FlagPal\FlagPal;

use Carbon\CarbonInterval;
use DateTimeInterface;
use FlagPal\FlagPal\Actions\ResolveFeaturesFromFunnel;
use FlagPal\FlagPal\Repositories\ActorRepository;
use FlagPal\FlagPal\Repositories\FeatureRepository;
use FlagPal\FlagPal\Repositories\FunnelRepository;
use FlagPal\FlagPal\Repositories\MetricTimeSeriesRepository;
use FlagPal\FlagPal\Resources\Actor;
use FlagPal\FlagPal\Resources\Feature;
use FlagPal\FlagPal\Resources\FeatureSet;
use FlagPal\FlagPal\Resources\Funnel;
use FlagPal\FlagPal\Resources\Metric;
use FlagPal\FlagPal\Resources\MetricTimeSeries;
use FlagPal\FlagPal\Validation\Validator;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Swis\JsonApi\Client\InvalidResponseDocument;
use Swis\JsonApi\Client\ItemHydrator;

class FlagPal
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
        protected readonly FeatureRepository $featureRepository,
        protected readonly Validator $validator,
        protected readonly ResolveFeaturesFromFunnel $resolver,
        protected readonly ItemHydrator $itemHydrator,
        protected readonly CacheManager $cache,
        protected LogManager $log,
    ) {
        $this->projects = config('flagpal.projects');
        $this->project = config('flagpal.default_project');
        $this->cacheTtlSeconds = (int) config('flagpal.cache.ttl', 60);
    }

    public function definedFeatures(): array
    {
        $cacheKey = "flagpal-features-{$this->project}";

        if (($features = $this->cache()->get($cacheKey))) {
            return $features;
        }

        $this->log()?->debug('FlagPal fetching features');

        $document = $this->featureRepository->all([], $this->headers());

        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            $this->log()?->error('FlagPal failed to fetch features', ['document' => $document->toArray()]);

            return [];
        }

        /** @var \Swis\JsonApi\Client\Collection $features */
        $features = $document->getData();

        $this->cache()->set(
            $cacheKey,
            $features->toArray(),
            CarbonInterval::seconds($this->cacheTtlSeconds),
        );

        return $features->toArray();
    }

    public function resolveFeatures(array $currentFeatures = []): array
    {
        $funnels = $this->loadFunnels();

        $this->log()?->debug('FlagPal resolving features', $currentFeatures);

        $currentFeatures = $this->filterInvalidFeatures($currentFeatures);

        $features = $funnels->reduce(function (array $features, Funnel $funnel) {
            $set = ($this->resolver)($funnel, $features);

            if (! $set) {
                return $features;
            }

            $this->entered[$funnel->getId()] = new EnteredFunnel($funnel, $set);

            return array_merge($features, $set->features);
        }, $currentFeatures);

        $features = $this->castFeatureValues($features);

        $this->log()?->debug('FlagPal resolved features', $features);

        return $features;
    }

    public function getEnteredFunnels(): array
    {
        return $this->entered;
    }

    public function recordMetric(Metric $metric, FeatureSet $set, int $value, ?DateTimeInterface $dateTime = null): bool
    {
        $item = $this->itemHydrator->hydrate(new MetricTimeSeries, [
            MetricTimeSeries::METRIC => $metric->toJsonApiArray(),
            MetricTimeSeries::FEATURE_SET => $set->toJsonApiArray(),
            MetricTimeSeries::VALUE => $value,
            MetricTimeSeries::TIME_SEGMENT => $dateTime?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
        ]);

        $document = $this->metricTimeSeriesRepository->create($item, [], $this->headers());

        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            $this->log()?->error('FlagPal failed to record a metric', ['document' => $document->toArray()]);

            return false;
        }

        return true;
    }

    public function getActor(string $reference): ?Actor
    {
        /** @var Actor|null $actor */
        $actor = $this->actorRepository->find($reference, [], $this->headers())->getData();

        return $actor;
    }

    public function saveActorFeatures(string $reference, array $features): Actor
    {
        /** @var Actor $actor */
        $actor = $this->itemHydrator->hydrate(new Actor, [
            Actor::FEATURES => $features,
        ]);
        $actor->setId($reference);

        return $this->saveActor($actor);
    }

    public function saveActor(Actor $actor): Actor
    {
        /** @var Actor $actor */
        $actor = $this->actorRepository->create($actor, [], $this->headers())->getData();

        return $actor;
    }

    protected function loadFunnels(): Collection
    {
        $parameters = [
            'filter' => ['active' => true],
            'include' => 'featureSets,metrics',
        ];
        $cacheKey = "flagpal-funnels-{$this->project}-".json_encode($parameters);

        if (($funnels = $this->cache()->get($cacheKey))) {
            return $funnels;
        }

        $this->log()?->debug('FlagPal loading funnels', ['parameters' => $parameters]);

        $document = $this->funnelRepository->all($parameters, $this->headers());
        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            $this->log()?->error('FlagPal failed to load funnels', ['document' => $document->toArray()]);

            return new Collection;
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

    public function getProject(): string
    {
        return $this->project;
    }

    protected function log(): ?LoggerInterface
    {
        $driver = config('flagpal.log.driver');

        return match ($driver) {
            null => null,
            'default' => $this->log->driver(),
            default => $this->log->driver($driver),
        };
    }

    protected function cache(): CacheInterface
    {
        $driver = config('flagpal.cache.driver', 'array');

        return match ($driver) {
            'default' => $this->cache->driver(),
            default => $this->cache->driver($driver),
        };
    }

    protected function headers(): array
    {
        return ['Authorization' => "Bearer {$this->projects[$this->project]['bearer_token']}"];
    }

    protected function castFeatureValues(array $features): array
    {
        $defined = collect($this->definedFeatures());

        return collect($features)
            ->mapWithKeys(
                fn (mixed $value, string $name) => [$name => Feature::castToKind($defined->firstWhere('name', $name)['kind'], $value)])
            ->toArray();
    }

    protected function filterInvalidFeatures(array $features): array
    {
        $defined = $this->definedFeatures();

        return collect($features)->filter(
            function (mixed $value, string $name) use ($defined) {
                $rules = collect($defined)->where('name', $name)->first()['rules'] ?? [];
                $rules = collect($rules)->map(fn (array $rules) => array_merge($rules, ['feature' => $name]))->toArray();

                if (! $this->validator->passes([$name => $value], $rules)) {
                    $this->log()?->warning('FlagPal failed to validate feature', [
                        'feature' => $name,
                        'value' => $value,
                        'rules' => $rules,
                    ]);

                    return false;
                }

                return true;
            }
        )->toArray();
    }
}
