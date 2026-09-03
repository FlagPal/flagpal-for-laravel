<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Pennant;

use FlagPal\FlagPal\Contracts\Pennant\StoresFlagPalFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

/**
 * A ready-made Pennant scope for visitors who aren't authenticated yet,
 * backed by a cookie. Typical usage in a service provider:
 *
 *   Feature::resolveScopeUsing(fn () => Auth::user() ?? new GuestScope(request()));
 */
class GuestScope implements FeatureScopeSerializeable, StoresFlagPalFeatures
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function getFlagPalFeatures(): StatelessFeatures
    {
        $cookie = Cookie::queued($this->cookieName());
        $value = $cookie ? $cookie->getValue() : $this->request->cookie($this->cookieName());

        return new StatelessFeatures(json_decode($value ?? '', true) ?? []);
    }

    public function saveFlagPalFeatures(array $features): self
    {
        $features = array_merge($this->getFlagPalFeatures()->features, $features);

        Cookie::queue($this->cookieName(), json_encode($features), $this->cookieTtl());

        return $this;
    }

    /**
     * Every request builds a new GuestScope instance, so identity can't be
     * used for Pennant's per-request memoization of resolved features.
     * Returning Pennant's null-scope sentinel here makes every GuestScope
     * instance collapse to the same memoization key within a request,
     * which is what we want: the guest's identity lives in the cookie,
     * not in the PHP object.
     */
    public function featureScopeSerialize(): string
    {
        return '__laravel_null';
    }

    private function cookieName(): string
    {
        return config('flagpal.guest.cookie', 'flagpal_guest');
    }

    private function cookieTtl(): int
    {
        return (int) config('flagpal.guest.ttl', 60 * 24 * 30);
    }
}
