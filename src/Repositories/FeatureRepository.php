<?php

namespace Rapkis\Conductor\Repositories;

use Swis\JsonApi\Client\Actions\FetchMany;
use Swis\JsonApi\Client\BaseRepository;

class FeatureRepository extends BaseRepository
{
    use FetchMany;

    protected $endpoint = 'features';
}
