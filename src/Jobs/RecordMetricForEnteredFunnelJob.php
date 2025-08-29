<?php

namespace Rapkis\FlagPal\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Rapkis\FlagPal\EnteredFunnel;
use Rapkis\FlagPal\FlagPal;
use Rapkis\FlagPal\Resources\Metric;

class RecordMetricForEnteredFunnelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public readonly EnteredFunnel $entry,
        public readonly string $metric,
        public readonly int $value,
        public readonly ?\DateTimeInterface $dateTime = null,
    ) {}

    public function handle(FlagPal $flagPal): void
    {
        /** @var Metric|null $metric */
        $metric = $this->entry->funnel->metrics->firstWhere(Metric::NAME, $this->metric);
        if (! $metric) {
            return;
        }

        $success = $flagPal->recordMetric($metric, $this->entry->set, $this->value, $this->dateTime);

        if (! $success) {
            $this->fail('FlagPal failed to record a metric');
        }
    }
}
