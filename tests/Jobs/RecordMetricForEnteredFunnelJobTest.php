<?php

use Rapkis\FlagPal\EnteredFunnel;
use Rapkis\FlagPal\FlagPal;
use Rapkis\FlagPal\Jobs\RecordMetricForEnteredFunnelJob;
use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;
use Rapkis\FlagPal\Resources\Metric;
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

    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->once())->method('recordMetric')->with(
        $funnel->metrics->first(),
        $entry->set,
        100,
    );

    $job->handle($flagPal);
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

    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->never())->method('recordMetric');

    $job->handle($flagPal);
});
