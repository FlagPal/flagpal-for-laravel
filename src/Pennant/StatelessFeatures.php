<?php

declare(strict_types=1);

namespace Rapkis\FlagPal\Pennant;

use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

class StatelessFeatures implements FeatureScopeSerializeable
{
    public function __construct(
        public readonly array $features,
    ) {
    }
    public function featureScopeSerialize(): string
    {
        return json_encode($this->features);
    }
}
