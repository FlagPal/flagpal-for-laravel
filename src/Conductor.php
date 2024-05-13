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
    /** @var array<string,array> */
    private array $projects;

    private string $project;

    /** @var array<string,array<string, array>> */
    private array $entered = [];

    public function __construct(
        protected array $config,
        protected readonly FunnelRepository $funnelRepository,
        protected readonly ResolveFeaturesFromFunnel $resolver,
        protected readonly ?CacheInterface $cache,
        protected ?LoggerInterface $logger = null,
        protected readonly int $cacheTtlSeconds = 60,
    ) {
        $this->projects = $this->config['projects'];
        $this->project = $this->config['default_project'];
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

        if (($funnels = $this->cache?->get($cacheKey))) {
            return $funnels;
        }

        $document = $this->funnelRepository->all($parameters, $headers);
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

    public function asProject(string $project): self
    {
        $this->project = $project;

        return $this;
    }
}
