<?php

namespace Rapkis\FlagPal\Repositories;

use Swis\JsonApi\Client\Actions\Create;
use Swis\JsonApi\Client\Actions\FetchOne;
use Swis\JsonApi\Client\BaseRepository;

class ActorRepository extends BaseRepository
{
    use Create;
    use FetchOne;

    protected $endpoint = 'actors';
}
