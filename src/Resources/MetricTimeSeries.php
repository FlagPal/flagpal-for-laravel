<?php

namespace FlagPal\FlagPal\Resources;

use FlagPal\FlagPal\Contracts\Resources\Resource;
use Swis\JsonApi\Client\Interfaces\ManyRelationInterface;
use Swis\JsonApi\Client\Item;
use Swis\JsonApi\Client\Relations\HasOneRelation;

/**
 * @property string $metric
 * @property string $featureSet
 * @property string $value
 * @property string $timeSegment
 */
class MetricTimeSeries extends Item implements Resource
{
    public const METRIC = 'metric';

    public const FEATURE_SET = 'featureSet';

    public const VALUE = 'value';

    public const TIME_SEGMENT = 'time_segment';

    public const TYPE = 'metric-time-series';

    protected $type = self::TYPE;

    protected $availableRelations = [
        self::METRIC,
        self::FEATURE_SET,
    ];

    protected $fillable = [
        self::METRIC,
        self::FEATURE_SET,
        self::VALUE,
        self::TIME_SEGMENT,
    ];

    public function featureSet(): ManyRelationInterface|HasOneRelation
    {
        return $this->hasOne(FeatureSet::class, self::FEATURE_SET);
    }

    public function metric(): ManyRelationInterface|HasOneRelation
    {
        return $this->hasOne(Metric::class);
    }
}
