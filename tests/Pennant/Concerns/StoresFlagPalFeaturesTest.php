<?php

use FlagPal\FlagPal\Contracts\Pennant\StoresFlagPalFeatures as StoresFlagPalFeaturesContract;
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeatures;
use FlagPal\FlagPal\Pennant\StatelessFeatures;
use FlagPal\FlagPal\Resources\Actor;

it('gets features from FlagPal actor', function () {
    $flagPal = $this->createMock(FlagPal::class);

    $actor = new Actor;
    $actor->features = ['feature1' => 'value1', 'feature2' => 'value2'];

    $flagPal->expects($this->once())
        ->method('getActor')
        ->with('test-reference')
        ->willReturn($actor);

    $model = new class($flagPal) implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeatures;

        public function __construct(FlagPal $flagPal)
        {
            $this->flagPal = $flagPal;
        }

        public function getFlagPalReference(): string
        {
            return 'test-reference';
        }
    };

    $features = $model->getFlagPalFeatures();

    expect($features)->toBeInstanceOf(StatelessFeatures::class)
        ->and($features->features)->toBe(['feature1' => 'value1', 'feature2' => 'value2']);
});

it('saves features to FlagPal actor', function () {
    $flagPal = $this->createMock(FlagPal::class);

    $flagPal->expects($this->once())
        ->method('saveActorFeatures')
        ->with('test-reference', ['feature1' => 'new-value', 'feature2' => 'value2']);

    $model = new class($flagPal) implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeatures;

        public function __construct(FlagPal $flagPal)
        {
            $this->flagPal = $flagPal;
        }

        public function getFlagPalReference(): string
        {
            return 'test-reference';
        }
    };

    $result = $model->saveFlagPalFeatures(['feature1' => 'new-value', 'feature2' => 'value2']);

    expect($result)->toBe($model);
});

it('handles null actor response', function () {
    $flagPal = $this->createMock(FlagPal::class);

    $flagPal->expects($this->once())
        ->method('getActor')
        ->with('test-reference')
        ->willReturn(null);

    $model = new class($flagPal) implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeatures;

        public function __construct(FlagPal $flagPal)
        {
            $this->flagPal = $flagPal;
        }

        public function getFlagPalReference(): string
        {
            return 'test-reference';
        }
    };

    expect($model->getFlagPalFeatures())->toEqual(new StatelessFeatures([]));
});
