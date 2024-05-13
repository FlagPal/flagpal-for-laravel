<?php

declare(strict_types=1);

namespace Rapkis\Conductor\Client\Actions;

trait FetchMany
{
    /**
     * Extension of \Swis\JsonApi\Client\Actions\FetchMany
     * See https://github.com/swisnl/json-api-client/issues/102
     *
     *
     * @return \Swis\JsonApi\Client\Interfaces\DocumentInterface
     */
    public function all(array $parameters = [], array $headers = [])
    {
        return $this->getClient()->get($this->getEndpoint().'?'.http_build_query($parameters), $headers);
    }
}
