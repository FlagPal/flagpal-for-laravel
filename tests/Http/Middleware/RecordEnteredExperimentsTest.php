<?php

use FlagPal\FlagPal\EnteredFunnel;
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Http\Middleware\RecordEnteredExperiments;
use FlagPal\FlagPal\Jobs\RecordMetricForEnteredFunnelJob;
use FlagPal\FlagPal\Resources\Funnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Swis\JsonApi\Client\ItemHydrator;
use Symfony\Component\HttpFoundation\Response;

it('dispatches the entry metric job for each entered experiment funnel', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => 'experiment:entered']);

    $hydrator = $this->app->make(ItemHydrator::class);
    $experiment = $hydrator->hydrate(new Funnel, [
        'id' => '1',
        'kind' => 'experiment',
        'featureSets' => [['id' => '2']],
    ]);
    $entry = new EnteredFunnel($experiment, $experiment->featureSets->first());

    $flagPal = $this->createStub(FlagPal::class);
    $flagPal->method('getEnteredFunnels')->willReturn([$entry]);
    $this->app->instance(FlagPal::class, $flagPal);

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertPushed(RecordMetricForEnteredFunnelJob::class, function ($job) use ($entry) {
        return $job->entry === $entry && $job->metric === 'experiment:entered' && $job->value === 1;
    });
});

it('skips entered experience funnels', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => 'experiment:entered']);

    $hydrator = $this->app->make(ItemHydrator::class);
    $experience = $hydrator->hydrate(new Funnel, [
        'id' => '1',
        'kind' => 'experience',
        'featureSets' => [['id' => '2']],
    ]);
    $entry = new EnteredFunnel($experience, $experience->featureSets->first());

    $flagPal = $this->createStub(FlagPal::class);
    $flagPal->method('getEnteredFunnels')->willReturn([$entry]);
    $this->app->instance(FlagPal::class, $flagPal);

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertNotPushed(RecordMetricForEnteredFunnelJob::class);
});

it('does nothing when the entry metric is disabled', function () {
    Queue::fake();
    config(['flagpal.entry_metric' => null]);

    $hydrator = $this->app->make(ItemHydrator::class);
    $experiment = $hydrator->hydrate(new Funnel, [
        'id' => '1',
        'kind' => 'experiment',
        'featureSets' => [['id' => '2']],
    ]);
    $entry = new EnteredFunnel($experiment, $experiment->featureSets->first());

    $flagPal = $this->createStub(FlagPal::class);
    $flagPal->method('getEnteredFunnels')->willReturn([$entry]);
    $this->app->instance(FlagPal::class, $flagPal);

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $middleware->terminate(Request::create('/'), new Response);

    Queue::assertNotPushed(RecordMetricForEnteredFunnelJob::class);
});

it('passes the request through unchanged on handle', function () {
    $flagPal = $this->createStub(FlagPal::class);
    $this->app->instance(FlagPal::class, $flagPal);

    $middleware = $this->app->make(RecordEnteredExperiments::class);
    $request = Request::create('/');
    $response = new Response('ok');

    $result = $middleware->handle($request, fn () => $response);

    expect($result)->toBe($response);
});
