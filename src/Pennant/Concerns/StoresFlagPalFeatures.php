<?php

namespace FlagPal\FlagPal\Pennant\Concerns;

use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Pennant\HasFlagPalReference;
use FlagPal\FlagPal\Pennant\StatelessFeatures;

trait StoresFlagPalFeatures
{
    use HasFlagPalReference;

    protected FlagPal $flagPal;

    public function getFlagPalFeatures(): StatelessFeatures
    {
        return new StatelessFeatures($this->flagPal->getActor($this->getFlagPalReference())->features ?? []);
    }

    public function saveFlagPalFeatures(array $features): self
    {
        $this->flagPal->saveActorFeatures($this->getFlagPalReference(), $features);

        return $this;
    }
}
