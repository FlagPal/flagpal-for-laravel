<?php

namespace Rapkis\FlagPal;

use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;

class EnteredFunnel
{
    public function __construct(public readonly Funnel $funnel, public readonly FeatureSet $set)
    {
    }
}
