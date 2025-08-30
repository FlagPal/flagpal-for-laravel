<?php

namespace FlagPal\FlagPal\Resources;

use FlagPal\FlagPal\Contracts\Resources\Resource;
use Swis\JsonApi\Client\Item;

/**
 * @property string $kind
 * @property string $name
 * @property string $description
 */
class Metric extends Item implements Resource
{
    public const KIND = 'kind';

    public const NAME = 'name';

    public const DESCRIPTION = 'description';

    public const TYPE = 'metrics';

    protected $type = self::TYPE;

    protected $fillable = [
        self::KIND,
        self::NAME,
        self::DESCRIPTION,
    ];
}
