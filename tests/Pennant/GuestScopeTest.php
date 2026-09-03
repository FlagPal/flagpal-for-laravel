<?php

use FlagPal\FlagPal\Pennant\GuestScope;
use FlagPal\FlagPal\Pennant\StatelessFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

it('reads features from a queued cookie when present', function () {
    $features = ['a' => true, 'b' => 1];
    Cookie::queue('flagpal_guest', json_encode($features), 1);

    $scope = new GuestScope(Request::create('/'));

    $result = $scope->getFlagPalFeatures();

    expect($result)->toBeInstanceOf(StatelessFeatures::class)
        ->and($result->features)->toEqual($features);
});

it('reads features from the request cookie when no queued cookie exists', function () {
    $request = Request::create('/', 'GET', [], ['flagpal_guest' => json_encode(['exp' => 'x42', 'enabled' => false])]);

    $scope = new GuestScope($request);

    expect($scope->getFlagPalFeatures()->features)->toEqual(['exp' => 'x42', 'enabled' => false]);
});

it('returns empty features when there is no cookie at all', function () {
    $scope = new GuestScope(Request::create('/'));

    expect($scope->getFlagPalFeatures()->features)->toEqual([]);
});

it('returns empty features when the cookie cannot be decoded', function () {
    $request = Request::create('/', 'GET', [], ['flagpal_guest' => 'definitely-not-json']);

    $scope = new GuestScope($request);

    expect($scope->getFlagPalFeatures()->features)->toEqual([]);
});

it('merges current features with newly saved ones and queues the cookie', function () {
    $request = Request::create('/', 'GET', [], ['flagpal_guest' => json_encode(['old' => 'value'])]);

    $scope = new GuestScope($request);
    $scope->saveFlagPalFeatures(['new' => 123]);

    $queued = Cookie::queued('flagpal_guest');
    expect($queued)->not->toBeNull();

    expect(json_decode($queued->getValue(), true))->toEqual(['old' => 'value', 'new' => 123]);
});

it('uses the configured cookie name and ttl', function () {
    config(['flagpal.guest.cookie' => 'custom_guest_cookie', 'flagpal.guest.ttl' => 5]);

    $scope = new GuestScope(Request::create('/'));
    $scope->saveFlagPalFeatures(['a' => 1]);

    $queued = Cookie::queued('custom_guest_cookie');
    expect($queued)->not->toBeNull()
        ->and($queued->getMaxAge())->toBe(5 * 60);
});

it('serializes scope as the Pennant null-scope sentinel', function () {
    $scope = new GuestScope(Request::create('/'));

    expect($scope->featureScopeSerialize())->toBe('__laravel_null');
});
