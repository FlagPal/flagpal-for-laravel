<?php

namespace Rapkis\Conductor\Resources;

use Rapkis\Conductor\Enums\FunnelKind;
use Swis\JsonApi\Client\Interfaces\ManyRelationInterface;
use Swis\JsonApi\Client\Item;
use Swis\JsonApi\Client\Relations\HasManyRelation;

class Funnel extends Item
{
    public const KIND = 'kind';

    public const ACTIVE = 'active';

    public const PERCENT = 'percent';

    public const WEIGHT = 'weight';

    public const NAME = 'name';

    public const DESCRIPTION = 'description';

    public const RULES = 'rules';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $type = 'funnels';

    protected $fillable = [
        self::KIND,
        self::ACTIVE,
        self::PERCENT,
        self::WEIGHT,
        self::NAME,
        self::DESCRIPTION,
        self::RULES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::RULES => 'array',
        self::ACTIVE => 'boolean',
        self::KIND => FunnelKind::class,
    ];

    public function featureSets(): ManyRelationInterface|HasManyRelation
    {
        return $this->hasMany(FeatureSet::class);
    }

    public function goals(): ManyRelationInterface|HasManyRelation
    {
        return $this->hasMany(Goal::class);
    }
}