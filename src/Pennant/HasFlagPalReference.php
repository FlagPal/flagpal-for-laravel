<?php

namespace Rapkis\FlagPal\Pennant;

use Laravel\Pennant\Feature;

trait HasFlagPalReference
{
    /**
     * Get the FlagPal reference for a model.
     * This method is used by the FlagPalDriver to identify the scope.
     *
     * Uses the Laravel Pennant scope serialization by default.
     * Override this method if you need a different reference format.
     *
     * @return string
     */
    public function getFlagPalReference(): string
    {
        return Feature::serializeScope($this);
    }
}
