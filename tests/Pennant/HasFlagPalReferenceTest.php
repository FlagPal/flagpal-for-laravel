<?php

use Laravel\Pennant\Contracts\FeatureScopeSerializeable;
use Laravel\Pennant\Feature;
use FlagPal\FlagPal\Pennant\HasFlagPalReference;

it('returns serialized scope as reference', function () {
    $model = new class implements FeatureScopeSerializeable
    {
        use HasFlagPalReference;

        public $id = 123;

        public function __toString()
        {
            return 'Model:123';
        }

        public function featureScopeSerialize(): string
        {
            return 'Model:123';
        }
    };

    $serialized = Feature::serializeScope($model);

    expect($model->getFlagPalReference())->toBe($serialized);
});

it('can be used in different model types', function () {
    $model1 = new class implements FeatureScopeSerializeable
    {
        use HasFlagPalReference;

        public $id = 123;

        public function __toString()
        {
            return 'Model1:123';
        }

        public function featureScopeSerialize(): string
        {
            return 'Model1:123';
        }
    };

    $model2 = new class implements FeatureScopeSerializeable
    {
        use HasFlagPalReference;

        public $id = 456;

        public function __toString()
        {
            return 'Model2:456';
        }

        public function featureScopeSerialize(): string
        {
            return 'Model2:456';
        }
    };

    expect($model1->getFlagPalReference())->not->toBe($model2->getFlagPalReference());
});

it('can be overridden in child classes', function () {
    $model = new class implements FeatureScopeSerializeable
    {
        use HasFlagPalReference;

        public $id = 123;

        public function getFlagPalReference(): string
        {
            return 'custom-reference-'.$this->id;
        }

        public function featureScopeSerialize(): string
        {
            return 'Model3:123';
        }
    };

    expect($model->getFlagPalReference())->toBe('custom-reference-123');
});
