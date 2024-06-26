<?php

namespace Rapkis\FlagPal\Actions;

use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;
use Rapkis\FlagPal\Support\Raffle;
use Rapkis\FlagPal\Validation\Validator;

class ResolveFeaturesFromFunnel
{
    public function __construct(
        private readonly Validator $validator,
        private readonly Raffle $raffle,
    ) {
    }

    public function __invoke(Funnel $funnel, array $currentFeatures): ?FeatureSet
    {
        if (! $funnel->active || $funnel->percent < random_int(1, 100)) {
            return null;
        }
        if (! $this->validator->passes($currentFeatures, $funnel->rules)) {
            return null;
        }

        $weightedChoices = [];
        /** @var FeatureSet $set */
        foreach ($funnel->featureSets as $set) {
            $weightedChoices[$set->getId()] = $set->weight ?? 1;
        }
        $chosen = $this->raffle->draw($weightedChoices);

        return $funnel->featureSets->firstWhere(fn (FeatureSet $set) => $set->getId() === (string) $chosen);
    }
}
