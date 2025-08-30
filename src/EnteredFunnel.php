<?php

namespace FlagPal\FlagPal;

use FlagPal\FlagPal\Resources\FeatureSet;
use FlagPal\FlagPal\Resources\Funnel;

class EnteredFunnel
{
    public function __construct(public readonly Funnel $funnel, public readonly FeatureSet $set) {}
}
