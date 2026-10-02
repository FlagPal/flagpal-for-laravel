<?php

use FlagPal\FlagPal\EnteredFunnel;
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Jobs\RecordMetricForEnteredFunnelJob;
use FlagPal\FlagPal\Resources\FeatureSet;
use FlagPal\FlagPal\Resources\Funnel;
use FlagPal\FlagPal\Resources\Metric;
use Swis\JsonApi\Client\ItemHydrator;

it('records the metric for an entered funnel', function () {
    $hydrator = $this->app->make(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel, [
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

it('records the metric for an entered funnel with feature segmentation', function () {
    $hydrator = $this->app->make(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel, [
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
    $features = ['country' => 'US'];
    $job = new RecordMetricForEnteredFunnelJob($entry, 'conversion', 100, features: $features);

    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->once())->method('recordMetric')->with(
        $funnel->metrics->first(),
        $entry->set,
        100,
        $features,
        null,
    );

    $job->handle($flagPal);
});

it('skips recording the metric if it is not tracked in the funnel', function () {
    $hydrator = $this->app->make(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel, [
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

it('passes a zero value through to the client guard', function () {
    $hydrator = $this->app->make(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel, [
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
    $job = new RecordMetricForEnteredFunnelJob($entry, 'conversion', 0);

    // The zero guard lives in FlagPal::recordMetric, the single seam that
    // writes to the server. The job passes the value through untouched.
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->once())->method('recordMetric')->willReturn(true);

    $job->handle($flagPal);
});
