<?php

namespace Rapkis\Conductor\Repositories;

use Rapkis\Conductor\Client\Actions\FetchMany;
use Swis\JsonApi\Client\BaseRepository;

class FeatureRepository extends BaseRepository
{
    use FetchMany;

    protected $endpoint = 'features';
}
