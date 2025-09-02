<?php

namespace FlagPal\FlagPal\Resources;

use FlagPal\FlagPal\Contracts\Resources\Resource;
use Illuminate\Support\Carbon;
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

    public static function castToKind(string $kind, mixed $value): mixed
    {
        return match ($kind) {
            'string' => is_array($value) ? json_encode($value) : (string) $value,
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'array' => is_array($value) ? $value : json_decode($value, true),
            'date' => Carbon::parse($value),
            default => $value,
        };
    }
}
