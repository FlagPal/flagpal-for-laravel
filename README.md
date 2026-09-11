# FlagPal for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flagpal/flagpal-for-laravel.svg?style=flat-square)](https://packagist.org/packages/flagpal/flagpal-for-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/flagpal/flagpal-for-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/flagpal/flagpal-for-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/flagpal/flagpal-for-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/flagpal/flagpal-for-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/flagpal/flagpal-for-laravel.svg?style=flat-square)](https://packagist.org/packages/flagpal/flagpal-for-laravel)

**FlagPal for Laravel is the missing batteries for [Laravel Pennant](https://laravel.com/docs/pennant).** Pennant gives Laravel a clean feature-flag API, but ships with nowhere to manage flags, target users, run experiments, or read results - you'd have to build all of that yourself. This package plugs [FlagPal](https://flagpal.com) in as a Pennant driver, so `Feature::active(...)` is instantly backed by a real dashboard: percentage rollouts, targeting rules, multi-variant A/B tests, and conversion tracking, with no extra infrastructure and (for the common case) zero extra configuration.

🏠 [flagpal.com](https://flagpal.com) - the product · 📖 [docs.flagpal.com](https://docs.flagpal.com) - full documentation, concepts, and dashboard guides

If you just want to see it work: jump to [Quick Start](#quick-start) or [Your First A/B Test](#your-first-ab-test).

## Table of Contents

- [What is FlagPal?](#what-is-flagpal)
- [Why this package?](#why-this-package)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Your First A/B Test](#your-first-ab-test)
- [Reading Results](#reading-results)
- [Core Concepts](#core-concepts)
- [Multiple FlagPal Projects](#multiple-flagpal-projects)
- [Guests / Anonymous Visitors](#guests--anonymous-visitors)
- [Advanced Usage](#advanced-usage)
- [Using FlagPal Without Pennant](#using-flagpal-without-pennant)
- [Testing](#testing)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## What is FlagPal?

[FlagPal](https://flagpal.com) is a feature management and experimentation platform: a dashboard where your team defines feature flags, controls who sees what, and measures whether changes actually work - without a developer needing to deploy code for every rollout decision.

Four concepts, in one sentence each (full explanations in the [docs](https://docs.flagpal.com/getting-started/what-is-flagpal)):

| Concept | What it is |
|---|---|
| **Feature Flag** | A named, typed value - a light switch, a string, a number - that your app reads at runtime. |
| **Experience** | Delivers one fixed set of flag values to a targeted group of users (e.g. "beta users get `new_checkout_flow = true`"). |
| **Experiment** | An A/B (or A/B/n) test - splits traffic across multiple variants, each setting flags differently, so you can measure which one wins. |
| **Metric** | Something you measure against an Experiment - conversions, revenue, clicks - to decide the winner. |

You don't need to understand FlagPal's dashboard deeply to use this package - the [Quick Start](#quick-start) below gets you checking a flag in minutes, and the [A/B test walkthrough](#your-first-ab-test) explains the rest as you go.

## Why this package?

With Laravel Pennant's built-in drivers, your feature flag resolution rules live in your codebase itself - every flag is a PHP closure you write, maintain, and ship. That design has two real costs. First, rules can't change without a deployment: every rollout tweak, targeting adjustment, or experiment change has to go through code review and release, which restricts how quickly your team can deliver experiments and experiences. Second, Pennant leaves developers to whip up their own feature activation rule logic - percentage rollouts, targeting rules, multi-variant experiments, result tracking - making the learning curve steeper and scaling feature management across a team more difficult.

This package is a Pennant driver backed by FlagPal, so:

- `Feature::active('new-api')` and friends work exactly as Pennant already teaches you, no new API to learn for the common case.
- Flags, rollout percentages, targeting rules, and experiment variants are managed in the FlagPal dashboard - no redeploying your app to change who sees what.
- A/B tests are a first-class concept (Experiments), with conversion metrics and statistical results, not something you bolt on yourself.
- Registering the driver, caching resolved flags, and (optionally) tracking who was exposed to which variant are handled for you.

If you'd rather not use Pennant at all, the SDK works completely standalone too - see [Using FlagPal Without Pennant](#using-flagpal-without-pennant).

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- A [FlagPal](https://flagpal.com) account and project ([create one free](https://flagpal.com))

## Quick Start

1. Install the package:

   ```bash
   composer require flagpal/flagpal-for-laravel
   ```

2. Publish the config file:

   ```bash
   php artisan vendor:publish --tag="flagpal-for-laravel-config"
   ```

3. Grab your project's API URL and token from the FlagPal dashboard ([creating a project](https://docs.flagpal.com/getting-started/project-setup), [managing API tokens](https://docs.flagpal.com/guides/managing-api-tokens)) and add them to `.env`:

   ```env
   FLAGPAL_URL=https://flagpal.com/api/v1
   FLAGPAL_PROJECT="My Project"
   FLAGPAL_MY_PROJECT_TOKEN=your-token-here
   ```

   `FLAGPAL_MY_PROJECT_TOKEN` matches the project name from `config/flagpal.php` - see that file after publishing for the exact mapping if you rename it.

4. Tell Pennant to use FlagPal as its default store, also in `.env`:

   ```env
   PENNANT_STORE=flagpal
   ```

   Without this, Pennant keeps resolving flags through its default `database` store, and `Feature::active(...)` would never reach FlagPal.

That's it - nothing to add to `config/pennant.php`. The `flagpal` Pennant store itself, backed by your default project, is registered automatically. Check a flag anywhere in your app:

```php
use Laravel\Pennant\Feature;

if (Feature::active('new-checkout')) {
    // show the new checkout
}
```

`new-checkout` is a flag you define in the FlagPal dashboard - the SDK resolves whatever's configured there.

## Your First A/B Test

This walks through a complete, realistic example: **testing whether a green "Complete Purchase" button converts better than the current blue one.**

### 1. Create a feature flag

In the FlagPal dashboard, create a flag called `checkout-button-color` (type: string). This is the value your code will read - you're not hardcoding "blue" or "green" anywhere. ([guide →](https://docs.flagpal.com/guides/creating-feature-flag))

### 2. Create an Experiment with two variants

Create an **Experiment** (not an Experience - Experiments are what support multiple variants and statistical results). Give it two variants, each setting `checkout-button-color` to a different value, split 50/50:

- **Control** - `checkout-button-color = "blue"`
- **Variant** - `checkout-button-color = "green"`

([guide →](https://docs.flagpal.com/guides/running-experiment))

### 3. Attach a metric

Attach a metric to the Experiment - call it `conversion` - so FlagPal knows what "winning" means. ([guide →](https://docs.flagpal.com/guides/tracking-metrics))

### 4. Read the flag in your app

No dashboard concept changes your code - you always just read a flag:

```blade
{{-- resources/views/checkout.blade.php --}}
<button style="background-color: {{ \Laravel\Pennant\Feature::value('checkout-button-color') }}">
    Complete Purchase
</button>
```

Every visitor lands in Control or Variant according to the traffic split you configured, and `Feature::value(...)` returns the right color for them - automatically, consistently for that visitor, with no extra code.

### 5. Track who saw each variant

FlagPal needs to know how many people saw each variant to calculate a winner, not just how many converted. Register the entry-tracking middleware once, and it handles this automatically, after the response is already sent (no added latency):

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\FlagPal\FlagPal\Http\Middleware\RecordEnteredExperiments::class);
})
```

That's the entire setup for exposure tracking - no code at each flag check.

### 6. Record the conversion

When the purchase completes - in the same request the checkout page was rendered in - record the metric against the variant the visitor actually saw:

```php
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Resources\Metric;

$flagPal = app(FlagPal::class);

// ... purchase completes ...

foreach ($flagPal->getEnteredFunnels() as $entry) {
    $flagPal->recordMetric(new Metric(['name' => 'conversion']), $entry->set, 1);
}
```

`getEnteredFunnels()` gives you every Experiment/Experience variant this request resolved into - this records a conversion against whichever one the visitor was actually in.

If your conversion happens in a *later*, unrelated request (e.g. checkout completes days after signup), you'll need to persist which variant the visitor saw yourself - the same way you'd persist any other feature value; see [Using flags from your app's storage](#using-flags-from-your-apps-storage).

## Reading Results

Head to the Experiment's page in the FlagPal dashboard - exposure counts (from step 5), conversions (from step 6), and statistical significance are calculated for you. ([guide →](https://docs.flagpal.com/guides/reading-experiment-results))

## Core Concepts

Quick reference - see each doc page for the full explanation:

| Concept | Docs |
|---|---|
| Feature Flags | [docs.flagpal.com/concepts/feature-flags](https://docs.flagpal.com/concepts/feature-flags) |
| Experiences | [docs.flagpal.com/concepts/experiences](https://docs.flagpal.com/concepts/experiences) |
| Experiments | [docs.flagpal.com/concepts/experiments](https://docs.flagpal.com/concepts/experiments) |
| Metrics | [docs.flagpal.com/concepts/metrics](https://docs.flagpal.com/concepts/metrics) |
| Targeting Rules | [docs.flagpal.com/concepts/targeting-rules](https://docs.flagpal.com/concepts/targeting-rules) |
| Actors | [docs.flagpal.com/concepts/actors](https://docs.flagpal.com/concepts/actors) |
| Projects & Teams | [docs.flagpal.com/concepts/projects-and-teams](https://docs.flagpal.com/concepts/projects-and-teams) |

## Multiple FlagPal Projects

Many teams split flags across projects - for example, one project for A/B experiments, another for remote configuration. First, add each project's token to `config/flagpal.php`:

```php
// config/flagpal.php
'projects' => [
    'Experiments' => [
        'name' => 'Experiments',
        'bearer_token' => env('FLAGPAL_EXPERIMENTS_TOKEN'),
    ],
    'Remote Config' => [
        'name' => 'Remote Config',
        'bearer_token' => env('FLAGPAL_REMOTE_CONFIG_TOKEN'),
    ],
],
```

Then register a Pennant store per project:

```php
// config/pennant.php
'stores' => [
    'flagpal_experiments' => [
        'driver' => 'flagpal',
        'project' => 'Experiments',
    ],
    'flagpal_configs' => [
        'driver' => 'flagpal',
        'project' => 'Remote Config',
    ],
],
```

```php
use Laravel\Pennant\Feature;

Feature::store('flagpal_experiments')->active('checkout-redesign');
Feature::store('flagpal_configs')->value('payment-gateway');
```

Each store is fully isolated - switching projects on one can never affect another, whether you're using multiple Pennant stores or calling `FlagPal::asProject()` directly from unrelated services. Every call for a given project, from anywhere in the request, converges on the same stable instance.

## Guests / Anonymous Visitors

Storage-based scopes (below) assume an authenticated model to scope features to. For visitors who aren't authenticated yet, the package ships a ready-made cookie-backed scope: `FlagPal\FlagPal\Pennant\GuestScope`.

```php
// AppServiceProvider::boot()
Laravel\Pennant\Feature::resolveScopeUsing(
    fn () => Illuminate\Support\Facades\Auth::user() ?? new \FlagPal\FlagPal\Pennant\GuestScope(request())
);
```

Feature values resolved for a guest are stored in a cookie (`flagpal.guest.cookie`, `flagpal_guest` by default) for the configured TTL (`flagpal.guest.ttl`, 30 days by default), so a returning visitor keeps the same feature values without an account.

## Advanced Usage

### Scoped Features

Beyond the default Pennant scope, you can resolve features for any scope in three ways:

#### Stateless

Define your own scope directly, without any Laravel "magic" - useful for remote configuration unrelated to a specific model:

```php
$features = new \FlagPal\FlagPal\Pennant\StatelessFeatures(['locale' => \Illuminate\Support\Facades\App::getLocale()]);

\Laravel\Pennant\Feature::for($features)->value('payment-gateway'); // 'stripe' for US, 'boleto' for Brazil, configured in FlagPal
```

#### Using flags from your app's storage

Recommended for user/team/organization-scoped features - resolves via FlagPal but stores the result in your own database (using Pennant's `features` table), saving a round trip on every subsequent check:

```php
class User extends Model
{
    use Laravel\Pennant\Concerns\HasFeatures;
    use FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeaturesInDatabase;
}

$user->features()->set(['some-feature' => 'you-have-by-default']);
$user->features()->all(); // ['some-feature' => 'you-have-by-default', 'some-other-feature' => 'resolved-from-flagpal']
```

This is also how you persist which Experiment variant a scope should see across separate requests, for conversions that don't happen in the same request as the initial flag check.

#### Using FlagPal as a remote data warehouse

Simpler than storing in your app, at the cost of a network round trip: FlagPal stores and retrieves features for your scope directly.

```php
class User extends Model
{
    use Laravel\Pennant\Concerns\HasFeatures;
    use FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeatures;

    public function getFlagPalReference(): string
    {
        return $this->id; // uniquely identifies this scope in FlagPal; defaults to Feature::serializeScope($this) if omitted
    }
}

$user->features()->all(); // resolving automatically stores the result in FlagPal
```

### Managing Actors

FlagPal can also act as a data warehouse for arbitrary entities ("Actors") outside of Pennant's scope model - a user, a team, a project, anything. ([concept →](https://docs.flagpal.com/concepts/actors))

```php
use FlagPal\FlagPal\FlagPal;

$flagPal = app(FlagPal::class);

$actor = $flagPal->getActor('user-123');
$actor->features; // ['new-api' => true, 'dark-mode' => false]

$flagPal->saveActorFeatures('user-123', ['premium-access' => true]);
```

### Custom Cache Configuration

```php
// config/flagpal.php
'cache' => [
    'driver' => 'redis',
    'ttl' => 300, // 5 minutes
],
```

### Logging

```php
// config/flagpal.php
'log' => [
    'driver' => 'single', // any channel from config/logging.php, or null to disable
],
```

## Using FlagPal Without Pennant

Everything above works without Pennant at all - useful if you'd rather not adopt Pennant's scope model, or want the raw feature array.

```php
use FlagPal\FlagPal\FlagPal;

$flagPal = app(FlagPal::class);

$features = $flagPal->resolveFeatures();

if (in_array('new-api', $features)) {
    // Use the new API
}

// Rich (non-boolean) values work the same way
if ($features['checkout-flow'] === 'multi-step') {
    // Render a multi-step checkout
}
```

A facade is available as a shorthand for `app(FlagPal::class)`:

```php
use FlagPal\FlagPal\Facades\FlagPal;

$features = FlagPal::resolveFeatures();
```

### Resolving with pre-existing features

Pass in feature values you already know (from your own storage, another system, etc.) and FlagPal will apply Experiments/Experiences on top of them:

```php
$currentFeatures = [
    'dark-mode' => true,
    'checkout-flow' => 'single-page',
];

$features = $flagPal->resolveFeatures($currentFeatures);
```

### Switching projects directly

```php
$features = $flagPal->asProject('project_b')->resolveFeatures();
```

### Recording metrics manually

```php
use FlagPal\FlagPal\Resources\Metric;
use FlagPal\FlagPal\Resources\FeatureSet;

$metric = new Metric(['name' => 'conversion']);
$featureSet = new FeatureSet(['id' => 'checkout-v2']);

$flagPal->recordMetric($metric, $featureSet, 1);

// Optionally segment the metric by feature values (only features enabled for
// segmentation on the metric are recorded; the value itself is always recorded)
$flagPal->recordMetric($metric, $featureSet, 1, features: ['country' => 'US']);
```

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Rapolas Gruzdys](https://github.com/rapkis)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
