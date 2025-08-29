<?php

namespace Rapkis\FlagPal\Pennant\Concerns;

use Laravel\Pennant\Concerns\HasFeatures;
use Laravel\Pennant\Feature;
use Rapkis\FlagPal\FlagPal;
use Rapkis\FlagPal\Pennant\HasFlagPalReference;
use Rapkis\FlagPal\Pennant\StatelessFeatures;

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
