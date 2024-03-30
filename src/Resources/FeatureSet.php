<?php

namespace Rapkis\Conductor\Resources;

use Swis\JsonApi\Client\Item;

class FeatureSet extends Item
{
    public const FUNNEL = 'funnel';

    public const NAME = 'name';

    public const WEIGHT = 'weight';

    public const FEATURES = 'features';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const TYPE = 'feature-sets';

    protected $type = self::TYPE;

    protected $fillable = [
        self::FUNNEL,
        self::NAME,
        self::WEIGHT,
        self::FEATURES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [self::FEATURES => 'array'];
}
