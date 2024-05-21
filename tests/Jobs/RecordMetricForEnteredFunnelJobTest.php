<?php

use Rapkis\Conductor\Conductor;
use Rapkis\Conductor\EnteredFunnel;
use Rapkis\Conductor\Jobs\RecordMetricForEnteredFunnelJob;
use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;
use Rapkis\Conductor\Resources\Metric;
use Swis\JsonApi\Client\ItemHydrator;

it('records the metric for an entered funnel', function () {
    $hydrator = $this->app->make(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel(), [
        'featureSets' => [
            [
                'id' => '5678',
                FeatureSet::FEATURES => ['test' => 'foo', 'bar' => ['baz']],
            ],
        ],
        'metrics' => [
            ['id' => '5678', Metric::NAME => 'conversion'],
        ],
    ]);
    $entry = new EnteredFunnel($funnel, $funnel->featureSets->first());
    $job = new RecordMetricForEnteredFunnelJob($entry, 'conversion', 100);

    $conductor = $this->createMock(Conductor::class);
    $conductor->expects($this->once())->method('recordMetric')->with(
        $funnel->metrics->first(),
        $entry->set,
        100,
    );

    $job->handle($conductor);
});

it('skips recording the metric if it is not tracked in the funnel', function () {
    $hydrator = $this->app->make(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel(), [
        'featureSets' => [
            [
                'id' => '5678',
                FeatureSet::FEATURES => ['test' => 'foo', 'bar' => ['baz']],
            ],
        ],
        'metrics' => [
            ['id' => '5678', Metric::NAME => 'conversion'],
        ],
    ]);
    $entry = new EnteredFunnel($funnel, $funnel->featureSets->first());
    $job = new RecordMetricForEnteredFunnelJob($entry, 'click', 100);

    $conductor = $this->createMock(Conductor::class);
    $conductor->expects($this->never())->method('recordMetric');

    $job->handle($conductor);
});
