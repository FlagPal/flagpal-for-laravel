<?php

namespace FlagPal\FlagPal\Contracts\Pennant;

use FlagPal\FlagPal\Pennant\StatelessFeatures;

interface StoresFlagPalFeatures
{
    public function getFlagPalFeatures(): StatelessFeatures;

    public function saveFlagPalFeatures(array $features): self;
}
