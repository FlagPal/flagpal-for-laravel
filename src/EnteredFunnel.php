<?php

namespace Rapkis\Conductor;

use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;

class EnteredFunnel
{
    public function __construct(public readonly Funnel $funnel, public readonly FeatureSet $set)
    {
    }
}
