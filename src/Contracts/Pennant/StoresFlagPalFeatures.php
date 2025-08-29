<?php

namespace Rapkis\FlagPal\Contracts\Pennant;

use Rapkis\FlagPal\Pennant\StatelessFeatures;

interface StoresFlagPalFeatures
{
    public function getFlagPalFeatures(): StatelessFeatures;

    public function saveFlagPalFeatures(array $features): self;
}
