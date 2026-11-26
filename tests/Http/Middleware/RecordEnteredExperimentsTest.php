<?php

use FlagPal\FlagPal\EnteredFunnel;
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\FlagPalProjectRegistry;
use FlagPal\FlagPal\Http\Middleware\RecordEnteredExperiments;
use FlagPal\FlagPal\Jobs\RecordMetricForEnteredFunnelJob;
use FlagPal\FlagPal\Resources\Funnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Swis\JsonApi\Client\ItemHydrator;
use Symfony\Component\HttpFoundation\Response;

function entryFor(string $kind): EnteredFunnel
{
    $hydrator = app(ItemHydrator::class);
    $funnel = $hydrator->hydrate(new Funnel, [
        'id' => '1',
        'kind' => $kind,
        'featureSets' => [['id' => '2']],
    ]);

    return new EnteredFunnel($funnel, $funnel->featureSets->first());
}

function registryWith(EnteredFunnel ...$entries): FlagPalProjectRegistry
{
    $registry = new FlagPalProjectRegistry;

    foreach ($entries as $i => $entry) {
        $flagPal = test()->createStub(FlagPal::class);
        $flagPal->method('getEnteredFunnels')->willReturn([$entry]);
        $registry->remember("project-{$i}", fn () => $flagPal);
    }

    return $registry;
}

it('dispatches the entry metric job for each entered experiment funnel', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => 'experiment:entered']);

    $entry = entryFor('experiment');
    app()->instance(FlagPalProjectRegistry::class, registryWith($entry));

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertPushed(RecordMetricForEnteredFunnelJob::class, function ($job) use ($entry) {
        return $job->entry === $entry && $job->metric === 'experiment:entered' && $job->value === 1;
    });
});

it('skips entered experience funnels', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => 'experiment:entered']);

    $entry = entryFor('experience');
    app()->instance(FlagPalProjectRegistry::class, registryWith($entry));

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertNotPushed(RecordMetricForEnteredFunnelJob::class);
});

it('does nothing when the entry metric is disabled', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => null]);

    $entry = entryFor('experiment');
    app()->instance(FlagPalProjectRegistry::class, registryWith($entry));

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertNotPushed(RecordMetricForEnteredFunnelJob::class);
});

it('records entries from every project touched during the request', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => 'experiment:entered']);

    $entryA = entryFor('experiment');
    $entryB = entryFor('experiment');
    app()->instance(FlagPalProjectRegistry::class, registryWith($entryA, $entryB));

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertPushed(RecordMetricForEnteredFunnelJob::class, 2);
    Queue::assertPushed(RecordMetricForEnteredFunnelJob::class, fn ($job) => $job->entry === $entryA);
    Queue::assertPushed(RecordMetricForEnteredFunnelJob::class, fn ($job) => $job->entry === $entryB);
});

it('passes the request through unchanged on handle', function () {
    app()->instance(FlagPalProjectRegistry::class, new FlagPalProjectRegistry);

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $request = Request::create('/');
    $response = new Response('ok');

    $result = $middleware->handle($request, fn () => $response);

    expect($result)->toBe($response);
});
