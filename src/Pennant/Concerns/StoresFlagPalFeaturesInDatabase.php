<?php

namespace FlagPal\FlagPal\Pennant\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Drivers\DatabaseDriver;
use Laravel\Pennant\Feature;
use FlagPal\FlagPal\Pennant\StatelessFeatures;

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
        $toActivate = array_filter(array_diff($features, $currentFeatures));

        if (! empty($toDeactivate)) {
            $this->newFeatureQuery()
                ->where('scope', Feature::serializeScope($this))
                ->whereIn('name', array_keys($toDeactivate))
                ->delete();
        }

        $toActivate = collect($toActivate)->map(function ($value, $name) {
            return [
                'name' => $name,
                'scope' => Feature::serializeScope($this),
                'value' => json_encode($value, flags: JSON_THROW_ON_ERROR),
                DatabaseDriver::CREATED_AT => now(),
                DatabaseDriver::UPDATED_AT => now(),
            ];
        })->values();

        $this->newFeatureQuery()
            ->where('scope', Feature::serializeScope($this))
            ->upsert($toActivate->toArray(), ['name', 'scope'], ['value', DatabaseDriver::UPDATED_AT]);

        return $this;
    }

    protected function newFeatureQuery(): Builder
    {
        return DB::connection(config('pennant.stores.database.connection'))
            ->table(config('pennant.stores.database.table') ?? 'features');
    }
}
