<?php

namespace Rapkis\Conductor\Resources;

use Illuminate\Support\Carbon;
use Rapkis\Conductor\Contracts\Resources\Resource;
use Rapkis\Conductor\Enums\FunnelKind;
use Swis\JsonApi\Client\Collection;
use Swis\JsonApi\Client\Interfaces\ManyRelationInterface;
use Swis\JsonApi\Client\Item;
use Swis\JsonApi\Client\Relations\HasManyRelation;

/**
 * @property FunnelKind $kind
 * @property bool $active
 * @property int $percent
 * @property int $weight
 * @property string $name
 * @property string $description
 * @property array $rules
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection $featureSets
 * @property Collection $metrics
 */
class Funnel extends Item implements Resource
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

    public const TYPE = 'funnels';

    protected $type = self::TYPE;

    protected $availableRelations = [
        'featureSets',
        'metrics',
    ];

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

    public function metrics(): ManyRelationInterface|HasManyRelation
    {
        return $this->hasMany(Metric::class);
    }
}
