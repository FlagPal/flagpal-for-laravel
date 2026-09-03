<?php

namespace FlagPal\FlagPal\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array definedFeatures()
 * @method static array resolveFeatures(array $currentFeatures = [])
 * @method static array getEnteredFunnels()
 * @method static bool recordMetric(\FlagPal\FlagPal\Resources\Metric $metric, \FlagPal\FlagPal\Resources\FeatureSet $set, int $value, array $features = [], ?\DateTimeInterface $dateTime = null)
 * @method static \FlagPal\FlagPal\Resources\Actor|null getActor(string $reference)
 * @method static \FlagPal\FlagPal\Resources\Actor saveActorFeatures(string $reference, array $features)
 * @method static \FlagPal\FlagPal\Resources\Actor saveActor(\FlagPal\FlagPal\Resources\Actor $actor)
 * @method static \Illuminate\Support\Collection getFunnels()
 * @method static void forgetDefinedFeaturesCache(?string $project = null)
 * @method static void forgetFunnelsCache(?string $project = null)
 * @method static \FlagPal\FlagPal\FlagPal asProject(string $project)
 * @method static string getProject()
 *
 * @see \FlagPal\FlagPal\FlagPal
 */
class FlagPal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \FlagPal\FlagPal\FlagPal::class;
    }
}
