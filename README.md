# FlagPal for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flagpal/flagpal-for-laravel.svg?style=flat-square)](https://packagist.org/packages/flagpal/flagpal-for-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/flagpal/flagpal-for-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/flagpal/flagpal-for-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/flagpal/flagpal-for-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/flagpal/flagpal-for-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/flagpal/flagpal-for-laravel.svg?style=flat-square)](https://packagist.org/packages/flagpal/flagpal-for-laravel)

### FlagPal is the batteries you were missing in Laravel Pennant 🔋
This powerful Laravel Pennant driver lets you control feature flags and run A/B tests using the familiar Pennant syntax. 
Use it to roll out features, run experiments, or use it for remote configuration.

## Features

- Drop-in driver for Laravel Pennant
- Resolve feature flags from a remote API
- Local resolution and built-in caching for improved performance
- Support for multiple projects with different configurations
- Metric recording for tracking experiment performance
- Local or remote feature flag storage options for your users
- Non-opinionated, scalable solution to cover any business or technical needs 

## Installation

You can install the package via composer:

```bash
composer require flagpal/flagpal-for-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="flagpal-for-laravel-config"
```

### Pennant quick start

This package registers a Laravel Pennant driver named `flagpal`.

1) Add the store to your `config/pennant.php`:

```php
return [
    'stores' => [
        'flagpal' => [
            'driver' => 'flagpal',
            // 'project' => null, // optional, uses the default project from config/flagpal.php
        ],
    ],
];
```

2) Use the driver with Pennant's Feature facade:

```php
use Laravel\Pennant\Feature;

// If 'flagpal' is your default store in config/pennant.php
if (Feature::active('new-api')) {
    // ...
}

// Or explicitly select the driver
if (Feature::store('flagpal')->active('new-api')) {
    // ...
}
```

## Configuration

After publishing the configuration file, you'll find it at `config/flagpal.php`. Here you can configure:

### API Connection

```php
// Base URL for the FlagPal API
'base_url' => env('FLAGPAL_URL'),

// Default project to use
'default_project' => env('FLAGPAL_PROJECT'),
```

### Projects Configuration

```php
'projects' => [
    'My Project' => [
        'name' => 'My Project',
        'bearer_token' => env('FLAGPAL_MY_PROJECT_TOKEN'),
    ],
    // Add more projects as needed
],
```

Need multiple FlagPal projects? See the section ["Working with Multiple FlagPal Projects"](#working-with-multiple-flagpal-projects) below for a full example.

### Caching Options

Even though FlagPal resolves feature flags locally, all the initial setup needs to be retrieved from a remote API. 
For this reason caching is necessary to ensure your application's performance. 
Without a caching layer, local feature resolution becomes impossible as every single feature check would call the FlagPal API.
Using your default application cache driver is usually enough, but you can change it in the `flagpal.php` config file.
```php
'cache' => [
    'driver' => 'default', // Use any cache driver from your cache.php config
    'ttl' => 60, // Cache TTL in seconds
],
```

### Logging Options

Logging
```php
'log' => [
    'driver' => 'default', // Use any log driver from your logging.php config
],
```

## Using with Laravel Pennant

FlagPal is designed in a way to adapt to your application needs. You can use as many or as little features as you'd like.
At the core, it consists of three basic concepts:
- Feature flags. They're the building blocks of your logic. For example, a feature flag called `new-api`.
- Experiments and Experiences (also known as Funnels). They target your currently provided feature flags and resolve new ones, by the rules defined in the FlagPal dashboard. The only difference between an Experiment or an Experience is that Experiments can have multiple variants, but an Experience only has one.
- Metrics. You may define the metrics you'd like to track for a specific Experiment to make business decisions. A Metric, for example, can be a `conversion`, `revenue`, `interaction`, or anything else. Be as abstract or specific as you need to be. 

With this concept in mind, you can start resolving your features by chaining Experiments or Experiences one after another, and passing your features every time.

#### Pennant
As this is a Laravel package, it comes with a Laravel Pennant driver. FlagPal works completely with or without the driver. This section focuses on Pennant usage; for standalone (non-Pennant) examples, see "Using without Laravel Pennant" below.
Keep in mind that FlagPal's goal is to give you **options**, while Pennant is rather opinionated. All of Pennant's and FlagPal's features coexist and may simply need a bit more verbose configuration.
Since there is a lot of Laravel "magic" going behind the hood, please see this section on how to configure the Laravel Pennant driver.

### Basic Feature Resolution

#### With Pennant

```php
use Laravel\Pennant\Feature;

// Assuming you have the driver set up as default in config/pennant.php
// Check if a specific feature is active. You define these features in FlagPal's dashboard
if (Feature::active('new-api')) {
    // Use the new API
} else {
    // Use the legacy API
}

// If the driver isn't default, you should call it every time: Feature::store('flagpal')->active('new-api')
```



### Rich Feature Values

You can define your features not only in binary states (active/inactive), but store rich values as well.
There are multiple value types available, that should cover most of your needs: boolean, string, integer, array, date

#### With Pennant

```php
use Laravel\Pennant\Feature;

// Check a value of a specific feature
if (Feature::value('checkout-flow') === 'multi-step') {
    // Render a multi-step checkout flow
} else {
    // Render the checkout within a single page
}
```


### Resolving with pre-existing features

#### With Pennant

```php
use Laravel\Pennant\Feature;

// Flags and values can be anything defined in your application
// and have the same names defined in FlagPal's dashboard

// These feature values can be retrieved from anywhere: your the database (like your User model), cache, other Pennant drivers. It's up to you
$currentFeatures = [
    'dark-mode' => true,
    'checkout-flow' => 'single-page',
    'trial-days-remaining' => 14,
];

$currentFeatures = new \FlagPal\FlagPal\Pennant\StatelessFeatures($currentFeatures);

// Check if a specific feature is active
if (Feature::for($currentFeatures)->active('show-trial-reminder')) {
    // Trigger some promotional message
}
```


### Working with Multiple FlagPal Projects
First, make sure you have your project configured in `config/flagpal.php`

#### With Pennant
To use multiple projects with Laravel Pennant, it's best to [register them as separate drivers](https://laravel.com/docs/master/pennant#registering-the-driver).
You can skip registering them in your application's service provider, as this is already done by `FlagPalServiceProvider`. 
You only need to define your projects in `config/pennant.php` 

```php
<?php

// config/pennant.php

return [
    'stores' => [

        // one of the default drivers from pennant
        'array' => [
            'driver' => 'array',
        ],

        // one of the default drivers from pennant
        'database' => [
            'driver' => 'database',
            'connection' => null,
            'table' => 'features',
        ],
        
        // your first flagpal project driver
        'flagpal' => [
            'driver' => 'flagpal',
            'project' => null // if not set, uses the default project from flagpal.php config
        ],
        // your second flagpal project driver
        'flagpal_project_b' => [
            'driver' => 'flagpal',
            'project' => 'project_b',
        ],
    ],
];

// Usage
Feature::driver('flagpal_project_b')->all();
```


[//]: # TODO()
### Recording Metrics

```php
use FlagPal\FlagPal\FlagPal;
use FlagPal\FlagPal\Resources\Metric;
use FlagPal\FlagPal\Resources\FeatureSet;

// Create a metric and feature set
$metric = new Metric(['name' => 'conversion']);
$featureSet = new FeatureSet(['id' => 'checkout-v2']);

// Record a metric with a value
app(FlagPal::class)->recordMetric($metric, $featureSet, 1);
```

### Managing Actors (optional)

FlagPal can be used as a stateless feature resolver but also works as a data warehouse for your needs.
You can store and retrieve data for "Actors", which is an abstract name for your any entity in your application: a user, a team, a project, etc. 

```php
use FlagPal\FlagPal\FlagPal;

// Create a FlagPal instance or use dependency injection
$flagPal = app(FlagPal::class);

// Get an actor by reference
$actor = $flagPal->getActor('user-123');
$actor->features; // ['new-api' => true, 'dark-mode' => false, 'checkout-flow' => 'multi-step'];

// Save actor features
$actor = $flagPal->saveActorFeatures('user-123', ['premium-access' => true]);
```

### Accessing Entered Funnels

After every feature resolution, each funnel is stored in-memory for easy access.
You can store this for analytics, debugging, and so on.

```php
use FlagPal\FlagPal\FlagPal;

// Create a FlagPal instance or use dependency injection
$flagPal = app(FlagPal::class);

// After resolving features, get the funnels that were entered
$enteredFunnels = $flagPal->getEnteredFunnels();

// To take it up a notch, you can even save them into a single feature, to ensure the
// same customer doesn't re-enter the same funnel (an A/B test for example)
$user = User::find(1);
$currentFeatures = $user->features;
$user->features = array_merge($currentFeatures, ['entered-funnels' => array_keys($enteredFunnels)]);

// Make sure your Experiments and Experiences actually target a feature flag called "entered-funnels".
// This is just an example. You define your own settings in FlagPal.
$flagPal->resolveFeatures($user->features);
```

## Advanced Usage

### Custom Cache Configuration

The package uses Laravel's cache system. You can configure a specific cache driver for FlagPal:

```php
// In config/flagpal.php
'cache' => [
    'driver' => 'redis',
    'ttl' => 300, // 5 minutes
],
```

### Logging

FlagPal logs errors when API operations fail. Configure the logging driver:

```php
// In config/flagpal.php
'log' => [
    'driver' => 'single', // Use a specific log channel
],
```

## Advanced Pennant usage

### Scoped Features

You can use scoped features with Pennant and FlagPal in multiple ways:
- As a stateless collection of features (this is a building block of the following options)
- Using feature flags from your application's storage (recommended)
- Using FlagPal as a remote data warehouse (simpler than storing in your app, but less control)

#### Stateless
Using FlagPal in a stateless way is probably simplest to understand because it doesn't involve any Laravel "magic": you define your own Pennant scope, instead of relying on a default.
This approach is most commonly used if you're using FlagPal as a remote configurator. For example, imagine you need to use different payment gateways, depending on your current APP's locale to provide the best customer experience:
```php
// Create your stateless features (your app's, or subsystem's configuration)
$features = new \FlagPal\FlagPal\Pennant\StatelessFeatures(['locale' => \Illuminate\Support\Facades\App::getLocale()]);

\Laravel\Pennant\Feature::driver('flagpal_payments_project')->for($features)->value('payment-gateway'); // could be 'stripe' for US, or 'boleto' for Brazil. All configured in FlagPal
```

#### Using flags from your app's storage

This approach may be the most common use case when you want to keep track of features for specific models (like User, Team, Organization). This approach is used by Laravel Pennant itself and its DatabaseDriver.
It's recommended to store features in your own storage (like a database), and provide those features as `StatelessFeatures` for FlagPal. For convenience, you can use the `features` database table that is already created by Pennant.
Storing feature flags locally provides you with a few additional benefits:
- You save a round trip to the FlagPal's API. This increases your application's performance, since you only need to query your database, which is usually quicker.
- You may want to manipulate your data more directly (like performing custom analytical queries, or mass updating some values).

To use this approach, your model only needs to implement the `FlagPal\FlagPal\Contracts\Pennant\StoresFlagPalFeatures` interface.
There's a trait that should cover most of the common use cases for storing feature flags, if you're using Pennant: `FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeaturesInDatabase`

```php
class User extends Model
{
    use Laravel\Pennant\Concerns\HasFeatures;
    use FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeaturesInDatabase;
}

// Usage
/** @var User $user */
$user = User::first();
$user->features()->set(['some-feature' => 'you-have-by-default']);

// Resolving features via Pennant will automatically save them, if you have the method saveFlagPalFeatures() defined in your model/scope. It's the same how the DatabaseDriver works, but you can store it however YOU want.
$user->features()->all(); // ['some-feature' => 'you-have-by-default', 'some-other-feature' => 'resolved-from-flagpal']
```

#### Using FlagPal as a remote data warehouse
In this scenario, you can avoid storing any data on your own, and trust FlagPal to do it for you. In this case, you can instead use the trait `FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeatures`.
It calls the FlagPal API to save and retrieve features for your scope. Each scope must send a reference/identifier for itself, so that you can track which features belong to which scopes. 
By default, the reference is generated through Pennant: `Feature::serializeScope($this)`. However, you can customize your reference by re-defining the method `getFlagPalReference()`. 
This reference must uniquely identify your scope (User, Team, etc.) and will be used to automatically store and retrieve the features in FlagPal via the API:

```php
class User extends Model
{
    use Laravel\Pennant\Concerns\HasFeatures;
    use FlagPal\FlagPal\Pennant\Concerns\StoresFlagPalFeatures;
    
    // Implementing the method manually
    public function getFlagPalReference(): string 
    {
        return $this->email; // better yet, use ID, UUID, or some other more depersonalized property
    }
}

// Usage

$user = User::first();

// resolving features will automatically store all their values for your scope in FlagPal itself.
$user->features()->all(); // ['some-feature' => 'you-have-by-default', 'some-other-feature' => 'resolved-from-flagpal']
```

## Using without Laravel Pennant

FlagPal can be used entirely without Laravel Pennant. This is useful if you prefer a lightweight integration, or need full control beyond Laravel Pennant.

- Resolve features and check flags:

```php
use FlagPal\FlagPal\FlagPal;

$flagPal = app(FlagPal::class);

$features = $flagPal->resolveFeatures();

if (in_array('new-api', $features)) {
    // feature is active
}
```

- Read rich feature values:

```php
$features = $flagPal->resolveFeatures();
$value = $features['checkout-flow'] ?? null; // e.g. 'multi-step'
```

- Provide pre-existing features for context (e.g. from your DB/cache):

```php
$current = [
    'dark-mode' => true,
    'trial-days-remaining' => 14,
];

$features = $flagPal->resolveFeatures($current);
```

- Work with multiple FlagPal projects:

```php
$features = $flagPal->asProject('project_b')->resolveFeatures();
```

This section contains all standalone (non-Pennant) examples in one place.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Rapolas Gruzdys](https://github.com/rapkis)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
