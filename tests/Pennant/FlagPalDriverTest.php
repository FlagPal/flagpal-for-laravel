<?php

use Laravel\Pennant\Contracts\FeatureScopeSerializeable;
use Rapkis\FlagPal\Contracts\Pennant\StoresFlagPalFeatures;
use Rapkis\FlagPal\FlagPal;
use Rapkis\FlagPal\Pennant\FlagPalDriver;
use Rapkis\FlagPal\Pennant\StatelessFeatures;

it('returns entered funnels from FlagPal', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->once())
        ->method('getEnteredFunnels')
        ->willReturn(['funnel1' => 'data1', 'funnel2' => 'data2']);

    $driver = new FlagPalDriver($flagPal);

    expect($driver->getEnteredFunnels())->toBe(['funnel1' => 'data1', 'funnel2' => 'data2']);
});

it('throws exception when trying to define a feature', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $driver = new FlagPalDriver($flagPal);

    expect(fn () => $driver->define('feature', fn () => true))
        ->toThrow(Exception::class, 'FlagPal can only define features externally.');
});

it('returns defined features', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->once())
        ->method('definedFeatures')
        ->willReturn([
            ['name' => 'feature1'],
            ['name' => 'feature2'],
        ]);

    $driver = new FlagPalDriver($flagPal);

    expect($driver->defined())->toBe(['feature1', 'feature2']);
});

it('gets all features for multiple scopes', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->method('definedFeatures')
        ->willReturn([
            ['name' => 'feature1'],
            ['name' => 'feature2'],
        ]);

    $flagPal->method('resolveFeatures')
        ->willReturnCallback(function ($features) {
            return $features + ['feature1' => 'value1', 'feature2' => 'value2'];
        });

    $driver = new FlagPalDriver($flagPal);

    $scope1 = new class implements FeatureScopeSerializeable
    {
        public function __toString()
        {
            return 'scope1';
        }

        public function featureScopeSerialize(): string
        {
            return 'scope1';
        }
    };

    $scope2 = new class implements FeatureScopeSerializeable
    {
        public function __toString()
        {
            return 'scope2';
        }

        public function featureScopeSerialize(): string
        {
            return 'scope2';
        }
    };

    $result = $driver->getAll([
        'feature1' => [$scope1, $scope2],
        'feature2' => [$scope1],
    ]);

    expect($result)->toBeArray()
        ->and($result['feature1'])->toBeArray()
        ->and($result['feature2'])->toBeArray()
        ->and($result['feature1'][0])->toBe('value1')
        ->and($result['feature1'][1])->toBe('value1')
        ->and($result['feature2'][0])->toBe('value2');
});

it('gets feature value for stateless features scope', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->method('definedFeatures')
        ->willReturn([
            ['name' => 'feature1'],
            ['name' => 'feature2'],
        ]);

    $flagPal->expects($this->once())
        ->method('resolveFeatures')
        ->with(['feature1' => 'initial'])
        ->willReturn(['feature1' => 'resolved', 'feature2' => 'value2']);

    $driver = new FlagPalDriver($flagPal);
    $scope = new StatelessFeatures(['feature1' => 'initial']);

    $result = $driver->get('feature1', $scope);

    expect($result)->toBe('resolved');
});

it('gets feature value for scope implementing StoresFlagPalFeatures', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->method('definedFeatures')
        ->willReturn([
            ['name' => 'feature1'],
            ['name' => 'feature2'],
        ]);

    $flagPal->expects($this->once())
        ->method('resolveFeatures')
        ->with(['feature1' => 'stored'])
        ->willReturn(['feature1' => 'resolved', 'feature2' => 'value2']);

    $driver = new FlagPalDriver($flagPal);

    $scope = new class implements FeatureScopeSerializeable, StoresFlagPalFeatures
    {
        public function getFlagPalFeatures(): StatelessFeatures
        {
            return new StatelessFeatures(['feature1' => 'stored']);
        }

        public function saveFlagPalFeatures(array $features): \Rapkis\FlagPal\Contracts\Pennant\StoresFlagPalFeatures
        {
            return $this;
        }

        public function featureScopeSerialize(): string
        {
            return 'stored-features-scope';
        }
    };

    $result = $driver->get('feature1', $scope);

    expect($result)->toBe('resolved');
});

it('sets feature value for scope implementing StoresFlagPalFeatures', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $driver = new FlagPalDriver($flagPal);

    $scope = new class implements FeatureScopeSerializeable, StoresFlagPalFeatures
    {
        public $savedFeatures = [];

        public function getFlagPalFeatures(): StatelessFeatures
        {
            return new StatelessFeatures([]);
        }

        public function saveFlagPalFeatures(array $features): \Rapkis\FlagPal\Contracts\Pennant\StoresFlagPalFeatures
        {
            $this->savedFeatures = $features;

            return $this;
        }

        public function featureScopeSerialize(): string
        {
            return 'save-features-scope';
        }
    };

    $driver->set('feature1', $scope, 'new-value');

    expect($scope->savedFeatures)->toBe(['feature1' => 'new-value']);
});

it('throws exception when trying to set a feature for all scopes', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $driver = new FlagPalDriver($flagPal);

    expect(fn () => $driver->setForAllScopes('feature', true))
        ->toThrow(Exception::class, 'You can set a feature for all scopes by creating an Experience in FlagPal');
});

it('deletes feature for scope implementing StoresFlagPalFeatures', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $driver = new FlagPalDriver($flagPal);

    $scope = new class implements FeatureScopeSerializeable, StoresFlagPalFeatures
    {
        public $savedFeatures = [];

        private $features = ['feature1' => 'value', 'feature2' => 'value2'];

        public function getFlagPalFeatures(): StatelessFeatures
        {
            return new StatelessFeatures($this->features);
        }

        public function saveFlagPalFeatures(array $features): \Rapkis\FlagPal\Contracts\Pennant\StoresFlagPalFeatures
        {
            $this->savedFeatures = $features;

            return $this;
        }

        public function featureScopeSerialize(): string
        {
            return 'delete-features-scope';
        }
    };

    $driver->delete('feature1', $scope);

    expect($scope->savedFeatures)->toBe(['feature2' => 'value2']);
});

it('throws exception when trying to purge all features', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $driver = new FlagPalDriver($flagPal);

    expect(fn () => $driver->purge(null))
        ->toThrow(Exception::class, 'You can not purge FlagPal features! Remove them from FlagPal instead.');
});

it('throws exception when trying to purge specific features', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $driver = new FlagPalDriver($flagPal);

    expect(fn () => $driver->purge(['feature1']))
        ->toThrow(Exception::class, 'You can not purge FlagPal features! Remove them from FlagPal instead.');
});

it('returns false for undefined features', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->method('definedFeatures')
        ->willReturn([
            ['name' => 'feature1'],
            ['name' => 'feature2'],
        ]);

    $driver = new FlagPalDriver($flagPal);
    $scope = new StatelessFeatures([]);

    expect($driver->get('undefined-feature', $scope))->toBeFalse();
});

it('returns defined features for scope', function () {
    $flagPal = $this->createMock(FlagPal::class);
    $flagPal->expects($this->once())
        ->method('definedFeatures')
        ->willReturn([
            ['name' => 'feature1'],
            ['name' => 'feature2'],
        ]);

    $driver = new FlagPalDriver($flagPal);
    $scope = new StatelessFeatures([]);

    expect($driver->definedFeaturesForScope($scope))->toBe(['feature1', 'feature2']);
});
