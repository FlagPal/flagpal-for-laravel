<?php

namespace Rapkis\Conductor\Repositories;

use Swis\JsonApi\Client\Actions\FetchMany;
use Swis\JsonApi\Client\Actions\FetchOne;
use Swis\JsonApi\Client\BaseRepository;

class FeatureRepository extends BaseRepository
{
    use FetchMany;
    use FetchOne;

    protected $endpoint = 'features';
}
