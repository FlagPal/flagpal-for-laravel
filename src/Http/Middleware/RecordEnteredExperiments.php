<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Http\Middleware;

use Closure;
use FlagPal\FlagPal\Enums\FunnelKind;
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Jobs\RecordMetricForEnteredFunnelJob;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt-in middleware that records the `flagpal.entry_metric` metric, once
 * per request, for every Experiment funnel a scope was resolved into.
 *
 * Runs in terminate(), after the response has already been sent, so it
 * adds no perceived latency. Register it explicitly where you want entry
 * tracking; it isn't added to the app's middleware stack automatically.
 */
class RecordEnteredExperiments
{
    public function __construct(
        private readonly FlagPal $flagPal,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $metric = config('flagpal.entry_metric');

        if (! $metric) {
            return;
        }

        foreach ($this->flagPal->getEnteredFunnels() as $entry) {
            if ($entry->funnel->kind !== FunnelKind::EXPERIMENT->value) {
                continue;
            }

            RecordMetricForEnteredFunnelJob::dispatch($entry, $metric, 1);
        }
    }
}
