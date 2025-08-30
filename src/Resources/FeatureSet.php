<?php

namespace FlagPal\FlagPal\Resources;

use FlagPal\FlagPal\Contracts\Resources\Resource;
use Illuminate\Support\Carbon;
use Swis\JsonApi\Client\Item;

/**
 * @property string $funnel
 * @property string $name
 * @property int|null $weight
 * @property array<string, mixed> $features
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class FeatureSet extends Item implements Resource
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
