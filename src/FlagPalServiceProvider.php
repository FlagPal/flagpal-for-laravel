<?php

namespace Rapkis\FlagPal;

use Rapkis\FlagPal\Contracts\Resources\Resource;
use Rapkis\FlagPal\Resources\Actor;
use Rapkis\FlagPal\Resources\Feature;
use Rapkis\FlagPal\Resources\FeatureSet;
use Rapkis\FlagPal\Resources\Funnel;
use Rapkis\FlagPal\Resources\Metric;
use Rapkis\FlagPal\Resources\MetricTimeSeries;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Swis\JsonApi\Client\Client;
use Swis\JsonApi\Client\DocumentClient;
use Swis\JsonApi\Client\Interfaces\ClientInterface;
use Swis\JsonApi\Client\Interfaces\DocumentClientInterface;
use Swis\JsonApi\Client\Interfaces\DocumentParserInterface;
use Swis\JsonApi\Client\Interfaces\ResponseParserInterface;
use Swis\JsonApi\Client\Interfaces\TypeMapperInterface;
use Swis\JsonApi\Client\Parsers\DocumentParser;
use Swis\JsonApi\Client\Parsers\ResponseParser;
use Swis\JsonApi\Client\TypeMapper;

class FlagPalServiceProvider extends PackageServiceProvider
{
    protected array $items = [
        Feature::class,
        Funnel::class,
        FeatureSet::class,
        Metric::class,
        MetricTimeSeries::class,
        Actor::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('flagpal-for-laravel')
            ->hasConfigFile('flagpal');
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
                $client->setBaseUri(rtrim(config('flagpal.base_url'), '/').'/');

                return $client;
            }
        );

        $this->app->bind(ClientInterface::class, Client::class);
        $this->app->bind(DocumentClientInterface::class, DocumentClient::class);
    }

    public function packageBooted()
    {
        $mapper = $this->app->make(TypeMapperInterface::class);

        /** @var class-string<resource> $class */
        foreach ($this->items as $class) {
            $mapper->setMapping($class::TYPE, $class);
        }
    }
}
