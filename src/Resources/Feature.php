<?php

namespace Rapkis\Conductor\Resources;

use Illuminate\Support\Carbon;
use Rapkis\Conductor\Contracts\Resources\Resource;
use Swis\JsonApi\Client\Item;

/**
 * @property string $name
 * @property string $description
 * @property string $kind
 * @property array $rules
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Feature extends Item implements Resource
{
    public const NAME = 'name';

    public const DESCRIPTION = 'description';

    public const KIND = 'kind';

    public const RULES = 'rules';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const TYPE = 'features';

    protected $type = self::TYPE;

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::KIND,
        self::RULES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [self::RULES => 'array'];
}
