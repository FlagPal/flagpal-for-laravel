<?php

namespace Rapkis\Conductor\Repositories;

use Rapkis\Conductor\Client\Actions\FetchMany;
use Swis\JsonApi\Client\BaseRepository;

class FunnelRepository extends BaseRepository
{
    use FetchMany;

    protected $endpoint = 'funnels';
}
