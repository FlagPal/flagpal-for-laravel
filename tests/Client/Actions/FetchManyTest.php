<?php

use Swis\JsonApi\Client\Interfaces\DocumentClientInterface;

it('passes headers from to underlying document client', function () {
    $documentClient = $this->createMock(DocumentClientInterface::class);
    class TestRepository
    {
        use \Rapkis\Conductor\Client\Actions\FetchMany;

        public function __construct(protected DocumentClientInterface $client)
        {
        }

        public function getClient()
        {
            return $this->client;
        }

        public function getEndpoint()
        {
            return 'example.com';
        }
    }

    $repository = new TestRepository($documentClient);

    $documentClient->expects($this->once())
        ->method('get')
        ->with(
            'example.com?',
            ['Test-Header' => 'Foo-Bar'],
        );

    $repository->all([], ['Test-Header' => 'Foo-Bar']);
});
