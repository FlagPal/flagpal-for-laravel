<?php

namespace FlagPal\FlagPal\Repositories;

use Swis\JsonApi\Client\Actions\Create;
use Swis\JsonApi\Client\BaseRepository;

class MetricTimeSeriesRepository extends BaseRepository
{
    use Create;

    protected $endpoint = 'metric-time-series';
}
