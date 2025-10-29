<?php

use FlagPal\FlagPal\Contracts\Pennant\StoresFlagPalFeatures as StoresFlagPalFeaturesContract;
use FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeaturesInDatabase;
use FlagPal\FlagPal\Pennant\StatelessFeatures;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Drivers\DatabaseDriver;
use Laravel\Pennant\Feature;

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

    $model = new class implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString()
        {
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

    $model = new class implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString()
        {
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
            return count($items) === 2
                && $items[0]['name'] === 'feature1'
                && $items[0]['scope'] === 'test-scope'
                && json_decode($items[0]['value']) === 'new-value'
                && $items[1]['name'] === 'feature3'
                && $items[1]['scope'] === 'test-scope'
                && json_decode($items[1]['value']) === false;
        }), ['name', 'scope'], ['value', DatabaseDriver::UPDATED_AT]);

    DB::shouldReceive('connection')
        ->times(3)
        ->with(null)
        ->andReturnSelf();

    DB::shouldReceive('table')
        ->times(3)
        ->with('features')
        ->andReturn($builder);

    $model = new class implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString()
        {
            return 'test-model';
        }
    };

    // Mock the Feature::serializeScope method
    Feature::shouldReceive('serializeScope')
        ->times(5)
        ->with($model)
        ->andReturn('test-scope');

    $result = $model->saveFlagPalFeatures([
        'feature1' => 'new-value',
        'feature2' => null, // This should be deleted
        'feature3' => false,
    ]);

    expect($model->getFlagPalFeatures()->features)->toBe(['feature1' => 'new-value', 'feature3' => false]);;

    expect($result)->toBe($model);
});

it('saves nested array feature values to database', function () {
    $builder = $this->createMock(Builder::class);
    $currentFeatures = collect([
        'array_feature' => json_encode(['this' => 'is', 'a' => ['nested', 'array']]),
    ]);

    DB::shouldReceive('connection')
        ->times(2)
        ->with(null)
        ->andReturnSelf();

    DB::shouldReceive('table')
        ->times(2)
        ->with('features')
        ->andReturn($builder);

    // For getFlagPalFeatures and other operations
    $builder->expects($this->exactly(2))
        ->method('where')
        ->with('scope', 'test-scope')
        ->willReturnSelf();

    $builder->expects($this->once())
        ->method('pluck')
        ->with('value', 'name')
        ->willReturn($currentFeatures);

    $builder->expects($this->never())
        ->method('delete');

    $this->travelTo('2000-01-01 00:00:01');

    // For upsert
    $builder->expects($this->once())
        ->method('upsert')
        ->with([
            ['name' => 'array_feature', 'scope' => 'test-scope', 'value' => json_encode(['this' => 'is', 'a' => ['different', 'array']]), 'created_at' => '2000-01-01 00:00:01', 'updated_at' => '2000-01-01 00:00:01'],
        ], ['name', 'scope'], ['value', DatabaseDriver::UPDATED_AT]);

    $model = new class implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString()
        {
            return 'test-model';
        }
    };

    // Mock the Feature::serializeScope method
    Feature::shouldReceive('serializeScope')
        ->times(3)
        ->with($model)
        ->andReturn('test-scope');

    $result = $model->saveFlagPalFeatures([
        'array_feature' => ['this' => 'is', 'a' => ['different', 'array']],
    ]);

    expect($result)->toBe($model);
});

it('saves carbon feature values to database', function () {
    $builder = $this->createMock(Builder::class);
    $currentFeatures = collect();

    DB::shouldReceive('connection')
        ->times(2)
        ->with(null)
        ->andReturnSelf();

    DB::shouldReceive('table')
        ->times(2)
        ->with('features')
        ->andReturn($builder);

    // For getFlagPalFeatures and other operations
    $builder->expects($this->exactly(2))
        ->method('where')
        ->with('scope', 'test-scope')
        ->willReturnSelf();

    $builder->expects($this->once())
        ->method('pluck')
        ->with('value', 'name')
        ->willReturn($currentFeatures);

    $builder->expects($this->never())
        ->method('delete');

    $this->travelTo('2000-01-01 00:00:01');

    // For upsert
    $builder->expects($this->once())
        ->method('upsert')
        ->with([
            ['name' => 'date_feature', 'scope' => 'test-scope', 'value' => '"2000-01-01T00:00:01.000000Z"', 'created_at' => '2000-01-01 00:00:01', 'updated_at' => '2000-01-01 00:00:01'],
        ], ['name', 'scope'], ['value', DatabaseDriver::UPDATED_AT]);

    $model = new class implements StoresFlagPalFeaturesContract
    {
        use StoresFlagPalFeaturesInDatabase;

        public function __toString()
        {
            return 'test-model';
        }
    };

    // Mock the Feature::serializeScope method
    Feature::shouldReceive('serializeScope')
        ->times(3)
        ->with($model)
        ->andReturn('test-scope');

    $result = $model->saveFlagPalFeatures([
        'date_feature' => Carbon::parse('2000-01-01 00:00:01'),
    ]);

    expect($result)->toBe($model);
});
