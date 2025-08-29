<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Drivers\DatabaseDriver;
use Laravel\Pennant\Feature;
use Rapkis\FlagPal\Contracts\Pennant\StoresFlagPalFeatures as StoresFlagPalFeaturesContract;
use Rapkis\FlagPal\Pennant\Concerns\StoresFlagPalFeaturesInDatabase;
use Rapkis\FlagPal\Pennant\StatelessFeatures;

it('gets features from database', function () {
    $builder = $this->createMock(Builder::class);
    $collection = collect([
        'feature1' => json_encode('value1'),
        'feature2' => json_encode(['nested' => 'value']),
    ]);

    $builder->expects($this->once())
        ->method('where')
        ->with('scope', 'test-scope')
        ->willReturnSelf();

    $builder->expects($this->once())
        ->method('pluck')
        ->with('value', 'name')
        ->willReturn($collection);

    DB::shouldReceive('connection')
        ->once()
        ->with(null)
        ->andReturnSelf();

    DB::shouldReceive('table')
        ->once()
        ->with('features')
        ->andReturn($builder);

    $model = new class implements StoresFlagPalFeaturesContract {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString() {
            return 'test-model';
        }
    };

    // Mock the Feature::serializeScope method
    Feature::shouldReceive('serializeScope')
        ->once()
        ->with($model)
        ->andReturn('test-scope');

    $features = $model->getFlagPalFeatures();

    expect($features)->toBeInstanceOf(StatelessFeatures::class)
        ->and($features->features)->toBe([
            'feature1' => 'value1',
            'feature2' => ['nested' => 'value'],
        ]);
});

it('caches features after first retrieval', function () {
    $builder = $this->createMock(Builder::class);
    $collection = collect([
        'feature1' => json_encode('value1'),
    ]);

    $builder->expects($this->once())
        ->method('where')
        ->with('scope', 'test-scope')
        ->willReturnSelf();

    $builder->expects($this->once())
        ->method('pluck')
        ->with('value', 'name')
        ->willReturn($collection);

    DB::shouldReceive('connection')
        ->once()
        ->with(null)
        ->andReturnSelf();

    DB::shouldReceive('table')
        ->once()
        ->with('features')
        ->andReturn($builder);

    $model = new class implements StoresFlagPalFeaturesContract {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString() {
            return 'test-model';
        }
    };

    // Mock the Feature::serializeScope method
    Feature::shouldReceive('serializeScope')
        ->once()
        ->with($model)
        ->andReturn('test-scope');

    // First call should hit the database
    $features1 = $model->getFlagPalFeatures();

    // Second call should use cached value
    $features2 = $model->getFlagPalFeatures();

    expect($features1)->toBe($features2);
});

it('saves features to database', function () {
    $builder = $this->createMock(Builder::class);
    $collection = collect([
        'feature1' => json_encode('old-value'),
        'feature2' => json_encode('value2'),
    ]);

    // For getFlagPalFeatures and other operations
    $builder->expects($this->exactly(3))
        ->method('where')
        ->with('scope', 'test-scope')
        ->willReturnSelf();

    $builder->expects($this->once())
        ->method('pluck')
        ->with('value', 'name')
        ->willReturn($collection);

    // For delete
    $builder->expects($this->once())
        ->method('whereIn')
        ->with('name', ['feature2'])
        ->willReturnSelf();

    $builder->expects($this->once())
        ->method('delete');

    // For upsert
    $builder->expects($this->once())
        ->method('upsert')
        ->with($this->callback(function ($items) {
            return count($items) === 1
                && $items[0]['name'] === 'feature1'
                && $items[0]['scope'] === 'test-scope'
                && json_decode($items[0]['value']) === 'new-value';
        }), ['name', 'scope'], ['value', DatabaseDriver::UPDATED_AT]);

    DB::shouldReceive('connection')
        ->times(3)
        ->with(null)
        ->andReturnSelf();

    DB::shouldReceive('table')
        ->times(3)
        ->with('features')
        ->andReturn($builder);

    $model = new class implements StoresFlagPalFeaturesContract {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString() {
            return 'test-model';
        }
    };

    // Mock the Feature::serializeScope method
    Feature::shouldReceive('serializeScope')
        ->times(4)
        ->with($model)
        ->andReturn('test-scope');

    $result = $model->saveFlagPalFeatures([
        'feature1' => 'new-value',
        'feature2' => null, // This should be deleted
    ]);

    expect($result)->toBe($model);
});
