<?php

namespace Rapkis\Conductor;

use Rapkis\Conductor\Resources\Feature;
use Rapkis\Conductor\Resources\FeatureSet;
use Rapkis\Conductor\Resources\Funnel;
use Rapkis\Conductor\Resources\Goal;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Swis\JsonApi\Client\Client;
use Swis\JsonApi\Client\DocumentClient;
use Swis\JsonApi\Client\Interfaces\ClientInterface;
use Swis\JsonApi\Client\Interfaces\DocumentClientInterface;
use Swis\JsonApi\Client\Interfaces\DocumentParserInterface;
use Swis\JsonApi\Client\Interfaces\ItemInterface;
use Swis\JsonApi\Client\Interfaces\ResponseParserInterface;
use Swis\JsonApi\Client\Interfaces\TypeMapperInterface;
use Swis\JsonApi\Client\Parsers\DocumentParser;
use Swis\JsonApi\Client\Parsers\ResponseParser;
use Swis\JsonApi\Client\TypeMapper;

class ConductorServiceProvider extends PackageServiceProvider
{
    protected array $items = [
        Feature::class,
        Funnel::class,
        FeatureSet::class,
        Goal::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-conductor')
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->registerSharedTypeMapper();
        $this->registerParsers();
        $this->registerClients();
    }

    protected function registerSharedTypeMapper()
    {
        $this->app->bind(TypeMapperInterface::class, TypeMapper::class);
        $this->app->singleton(TypeMapper::class);
    }

    protected function registerParsers()
    {
        $this->app->bind(DocumentParserInterface::class, DocumentParser::class);
        $this->app->bind(ResponseParserInterface::class, ResponseParser::class);
    }

    protected function registerClients(): void
    {
        $this->app->extend(
            ClientInterface::class,
            static function (ClientInterface $client) {
                $client->setBaseUri(rtrim(config('conductor.base_uri'), '/').'/');

                return $client;
            }
        );

        /** @var Client $client */
        $client = $this->app->make(Client::class);
        $client->setDefaultHeaders(array_merge(
            $client->getDefaultHeaders(),
            ['Authorization' => 'Bearer '.config('conductor.bearer_token')],
        ));

        $this->app->bind(ClientInterface::class, fn () => $client);
        $this->app->bind(DocumentClientInterface::class, DocumentClient::class);
    }

    public function packageBooted()
    {
        $mapper = $this->app->make(TypeMapperInterface::class);

        foreach ($this->items as $class) {
            /** @var ItemInterface $item */
            $item = $this->app->make($class);

            $mapper->setMapping($item->getType(), $class);
        }
    }
}
