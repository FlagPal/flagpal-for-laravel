<?php

namespace Rapkis\Conductor;

use DateInterval;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;
use Rapkis\Conductor\Actions\ResolveFeaturesFromFunnel;
use Rapkis\Conductor\Repositories\FunnelRepository;
use Rapkis\Conductor\Resources\Funnel;
use Swis\JsonApi\Client\InvalidResponseDocument;

class Conductor
{
    /** @var array<string,array<string, array>> */
    private array $entered = [];

    public function __construct(
        protected readonly FunnelRepository $funnelRepository,
        protected readonly ResolveFeaturesFromFunnel $resolver,
        public readonly ?CacheInterface $cache,
        protected readonly int $cacheTtlSeconds = 60,
    ) {
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
            'include' => 'featureSets,goals',
        ];
        $cacheKey = 'conductor-funnels-'.json_encode($parameters);

        if ($this->cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $document = $this->funnelRepository->all($parameters);
        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            // todo do something with errors.
            // do we throw an exception or allow to fail silently? or do we provide an option for both?
        }

        $funnels = Collection::make($document->getData());
        $this->cache?->set(
            $cacheKey,
            $funnels, // todo does it make sense to set an object in cache?
            DateInterval::createFromDateString("{$this->cacheTtlSeconds} seconds")
        );

        return $funnels;
    }
}
