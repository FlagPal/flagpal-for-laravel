<?php

namespace Rapkis\Conductor;

use DateInterval;
use Illuminate\Support\Collection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Rapkis\Conductor\Actions\ResolveFeaturesFromFunnel;
use Rapkis\Conductor\Repositories\FunnelRepository;
use Rapkis\Conductor\Resources\Funnel;
use Swis\JsonApi\Client\InvalidResponseDocument;

class Conductor implements LoggerAwareInterface
{
    /** @var array<string,array<string, array>> */
    private array $entered = [];

    public function __construct(
        protected readonly FunnelRepository $funnelRepository,
        protected readonly ResolveFeaturesFromFunnel $resolver,
        protected readonly ?CacheInterface $cache,
        protected ?LoggerInterface $logger = null,
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
            'include' => 'featureSets,metrics',
        ];
        $cacheKey = 'conductor-funnels-'.json_encode($parameters);

        if (($funnels = $this->cache?->get($cacheKey))) {
            return $funnels;
        }

        $document = $this->funnelRepository->all($parameters);
        if ($document instanceof InvalidResponseDocument || $document->hasErrors()) {
            $this->logger?->error('Conductor failed to load funnels', ['document' => $document->toArray()]);
        }

        $funnels = Collection::make($document->getData());
        $this->cache?->set(
            $cacheKey,
            $funnels,
            DateInterval::createFromDateString("{$this->cacheTtlSeconds} seconds")
        );

        return $funnels;
    }

    public function setLogger(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }
}
