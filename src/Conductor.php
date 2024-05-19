<?php

namespace Rapkis\Conductor;

use Carbon\CarbonInterval;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Rapkis\Conductor\Actions\ResolveFeaturesFromFunnel;
use Rapkis\Conductor\Repositories\FunnelRepository;
use Rapkis\Conductor\Resources\Funnel;
use Swis\JsonApi\Client\InvalidResponseDocument;

class Conductor
{
    /** @var array<string,array> */
    private array $projects;

    private string $project;

    /** @var array<string,array<string, array>> */
    private array $entered = [];

    private int $cacheTtlSeconds;

    public function __construct(
        protected readonly FunnelRepository $funnelRepository,
        protected readonly ResolveFeaturesFromFunnel $resolver,
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

            $this->entered[$funnel->getId()] = [$set->getId() => $set->features];

            return array_merge($features, $set->features);
        }, $currentFeatures);

        return $features;
    }

    public function getEnteredFunnels(): array
    {
        return $this->entered;
    }

    protected function loadFunnels(): Collection
    {
        $parameters = [
            'filter' => ['active' => true],
            'include' => 'featureSets,metrics',
        ];
        $headers = ['Authorization' => "Bearer {$this->projects[$this->project]['bearer_token']}"];
        $cacheKey = "conductor-funnels-{$this->project}-".json_encode($parameters);

        if (($funnels = $this->cache()->get($cacheKey))) {
            return $funnels;
        }

        $document = $this->funnelRepository->all($parameters, $headers);
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
}
