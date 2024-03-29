<?php

namespace Rapkis\Conductor\Resources;

use Swis\JsonApi\Client\Item;

class Feature extends Item
{
    public const ID = 'id';

    public const NAME = 'name';

    public const DESCRIPTION = 'description';

    public const KIND = 'kind';

    public const RULES = 'rules';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $type = 'features';

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
