<?php

namespace FlagPal\FlagPal\Pennant\Concerns;

use FlagPal\FlagPal\Pennant\StatelessFeatures;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Drivers\DatabaseDriver;
use Laravel\Pennant\Feature;

trait StoresFlagPalFeaturesInDatabase
{
    protected StatelessFeatures $cachedFeatures;

    public function getFlagPalFeatures(): StatelessFeatures
    {
        if ($this->cachedFeatures ?? null) {
            return $this->cachedFeatures;
        }

        return $this->cachedFeatures = new StatelessFeatures($this->newFeatureQuery()
            ->where('scope', Feature::serializeScope($this))
            ->pluck('value', 'name')
            ->mapWithKeys(fn ($value, $key) => [$key => json_decode($value, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR)])
            ->toArray());
    }

    public function saveFlagPalFeatures(array $features): self
    {
        $currentFeatures = $this->getFlagPalFeatures()->features;
        $toDeactivate = array_diff_key($currentFeatures, $features);
        $toDeactivate = collect($toDeactivate)->merge(collect($features)->filter(fn ($value, $name) => $value === null))->toArray();

        /*
         * Comparing JSON values because of two reasons:
         * - easier to compare objects. and they are stored as json anyways
         * - array features must be replaced completely if ANY of their internal nested value changes
         */
        $toActivate = collect($features)
            ->filter(fn ($value, $name) => $value !== null)
            ->map(fn ($value, $name) => json_encode($value, flags: JSON_THROW_ON_ERROR))
            ->diff(collect($currentFeatures)->map(fn ($value, $name) => json_encode($value, flags: JSON_THROW_ON_ERROR)))
            ->toArray();

        if (! empty($toDeactivate)) {
            $this->newFeatureQuery()
                ->where('scope', Feature::serializeScope($this))
                ->whereIn('name', array_keys($toDeactivate))
                ->delete();
        }

        $toActivate = collect($toActivate)->map(function (string $value, $name) {
            return [
                'name' => $name,
                'scope' => Feature::serializeScope($this),
                'value' => $value,
                DatabaseDriver::CREATED_AT => now(),
                DatabaseDriver::UPDATED_AT => now(),
            ];
        })->values();

        $this->newFeatureQuery()
            ->where('scope', Feature::serializeScope($this))
            ->upsert($toActivate->toArray(), ['name', 'scope'], ['value', DatabaseDriver::UPDATED_AT]);

        $this->cachedFeatures = new StatelessFeatures(array_filter($features, fn($value) => !is_null($value)));

        return $this;
    }

    protected function newFeatureQuery(): Builder
    {
        return DB::connection(config('pennant.stores.database.connection'))
            ->table(config('pennant.stores.database.table') ?? 'features');
    }
}
