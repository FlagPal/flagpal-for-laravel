<?php

namespace Rapkis\FlagPal\Resources;

use Illuminate\Support\Carbon;
use Rapkis\FlagPal\Contracts\Resources\Resource;
use Swis\JsonApi\Client\Item;

/**
 * @property array $features
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Actor extends Item implements Resource
{
    public const FEATURES = 'features';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const TYPE = 'actors';

    protected $type = self::TYPE;

    protected $fillable = [
        self::FEATURES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [self::FEATURES => 'array'];
}
