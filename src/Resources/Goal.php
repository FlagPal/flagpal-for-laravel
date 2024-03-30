<?php

namespace Rapkis\Conductor\Resources;

use Swis\JsonApi\Client\Item;

class Goal extends Item
{
    public const KIND = 'kind';

    public const NAME = 'name';

    public const DESCRIPTION = 'description';

    public const TYPE = 'goals';

    protected $type = self::TYPE;

    protected $fillable = [
        self::KIND,
        self::NAME,
        self::DESCRIPTION,
    ];
}
