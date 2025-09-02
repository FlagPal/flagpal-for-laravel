<?php

namespace FlagPal\FlagPal\Pennant;

use Exception;
use FlagPal\FlagPal\Contracts\Pennant\StoresFlagPalFeatures;
use FlagPal\FlagPal\FlagPal;
use Illuminate\Support\Collection;
use Laravel\Pennant\Contracts\DefinesFeaturesExternally;
use Laravel\Pennant\Contracts\Driver;

// todo features are cached in Pennant by serialized scope. We need to clear it every time before resolving features (maybe?)
class FlagPalDriver implements DefinesFeaturesExternally, Driver
{
    public const NAME = 'flagpal';

    public function __construct(
        public readonly FlagPal $flagPal,
    ) {}

    public function getEnteredFunnels(): array
    {
        return $this->flagPal->getEnteredFunnels();
    }

    public function define(string $feature, callable $resolver): void
    {
        throw new Exception('FlagPal can only define features externally.');
    }

    public function defined(): array
    {
        return $this->definedFeaturesForScope([]);
    }

    public function getAll(array $features): array
    {
        $features = Collection::make($features)
            ->map(fn ($scopes, $feature) => Collection::make($scopes)
                ->map(fn ($scope) => $this->get($feature, $scope))
                ->all())
            ->all();

        return $features;
    }

    public function get(string $feature, mixed $scope): mixed
    {
        $defined = $this->defined();
        if (! in_array($feature, $defined)) {
            return false;
        }

        $features = [];

        if ($scope instanceof StatelessFeatures) {
            $features = $scope->features;
        }

        if ($scope instanceof StoresFlagPalFeatures) {
            $features = $scope->getFlagPalFeatures()->features;
        }

        // todo map values by type here?
        $features = array_intersect_key($features, array_flip($defined));

        $features = $this->flagPal->resolveFeatures($features);

        if ($scope instanceof StoresFlagPalFeatures) {
            $scope->saveFlagPalFeatures($features);
        }

        return $features[$feature] ?? null;
    }

    public function set(string $feature, mixed $scope, mixed $value): void
    {
        if ($scope instanceof StoresFlagPalFeatures) {
            $currentFeatures = $scope->getFlagPalFeatures()->features;
            $scope->saveFlagPalFeatures(array_merge($currentFeatures, [$feature => $value]));
        }
    }

    public function setForAllScopes(string $feature, mixed $value): void
    {
        throw new Exception('You can set a feature for all scopes by creating an Experience in FlagPal');
    }

    public function delete(string $feature, mixed $scope): void
    {
        if ($scope instanceof StoresFlagPalFeatures) {
            $features = $scope->getFlagPalFeatures()->features;
            unset($features[$feature]);
            $scope->saveFlagPalFeatures($features);
        }
    }

    public function purge(?array $features): void
    {
        throw new Exception('You can not purge FlagPal features! Remove them from FlagPal instead.');
    }

    public function definedFeaturesForScope(mixed $scope): array
    {
        return collect($this->flagPal->definedFeatures())->pluck('name')->toArray();
    }
}
