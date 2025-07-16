# FlagPal for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rapkis/flagpal-for-laravel.svg?style=flat-square)](https://packagist.org/packages/rapkis/flagpal-for-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/rapkis/flagpal-for-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/rapkis/flagpal-for-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/rapkis/flagpal-for-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/rapkis/flagpal-for-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rapkis/flagpal-for-laravel.svg?style=flat-square)](https://packagist.org/packages/rapkis/flagpal-for-laravel)

FlagPal is a Laravel package for resolving feature flags provided via flagpal.com API. It allows you to incrementally roll out new features, perform A/B testing, and manage feature access across your application with ease.

## Features

- Resolve feature flags from a remote API
- Support for multiple projects with different configurations
- Local resolution and built-in caching for improved performance
- Metric recording for feature usage
- Actor management for user-specific features
- Comprehensive logging

## Installation

You can install the package via composer:

```bash
composer require rapkis/flagpal-for-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="flagpal-for-laravel-config"
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

### Caching Options

```php
'cache' => [
    'driver' => 'default', // Use any cache driver from your cache.php config
    'ttl' => 60, // Cache TTL in seconds
],
```

### Logging Options

```php
'log' => [
    'driver' => 'default', // Use any log driver from your logging.php config
],
```

## Usage

FlagPal is designed in a way to adapt to your application needs. You can use as many or as little features as you'd like.
At the core, it consists of three basic concepts:
- Feature flags. They're the building blocks of your logic. For example, a feature flag called `new-api`.
- Experiments and Experiences (also known as Funnels). They target your currently provided feature flags and resolve new ones, by the rules defined in the FlagPal dashboard. The only difference between an Experiment or an Experience is that Experiments can have multiple variants, but an Experience only has one.
- Metrics. You may define the metrics you'd like to track for a specific Experiment to make business decisions. A Metric, for example, can be a `conversion`, `revenue`, `interaction`, or anything else. Be as abstract or specific as you need to be. 

With this concept in mind, you can start resolving your features by chaining Experiments or Experiences one after another, and passing your features every time.

### Basic Feature Resolution

```php
use Rapkis\FlagPal\FlagPal;

// Create a FlagPal instance or use dependency injection
$flagPal = app(FlagPal::class);

// Resolve features (returns an array of active features)
$features = $flagPal->resolveFeatures();

// Check if a specific feature is active. You define these features in FlagPal's dashboard
if (in_array('new-api', $features)) {
    // Use the new API
} else {
    // Use the legacy API
}

// Check a value of a specific feature
if ($features['checkout-flow'] === 'multi-step') {
    // Render a multi-step checkout flow
} else {
    // Render the checkout within a single page
}
```

### Rich Feature Values

You can define your features not only in binary states (active/inactive), but store rich values as well.
There are multiple value types available, that should cover most of your needs: boolean, string, integer, array, date

```php
use Rapkis\FlagPal\FlagPal;

// Create a FlagPal instance or use dependency injection
$flagPal = app(FlagPal::class);

// Resolve features (returns an array of active features)
$features = $flagPal->resolveFeatures();

// Check a value of a specific feature
if ($features['checkout-flow'] === 'multi-step') {
    // Render a multi-step checkout flow
} else {
    // Render the checkout within a single page
}
```

### Resolving with pre-existing features

```php
use Rapkis\FlagPal\FlagPal;

// Create a FlagPal instance or use dependency injection
$flagPal = app(FlagPal::class);

// Flags and values can be anything defined in your application
// and have the same names defined in FlagPal's dashboard
$currentFeatures = [
    'dark-mode' => true,
    'checkout-flow' => 'single-page',
    'trial-days-remaining' => 14,
];

$features = $flagPal->resolveFeatures($currentFeatures);

// Check if a specific feature is active
if (in_array('show-trial-reminder', $features)) {
    // Trigger some promotional message
}
```

### Working with Multiple Projects

```php
use Rapkis\FlagPal\FlagPal;

// Create a FlagPal instance or use dependency injection
$flagPal = app(FlagPal::class);

// Switch to a specific project
$features = $flagPal->asProject('Project B')->resolveFeatures();
```

[//]: # TODO()
### Recording Metrics

```php
use Rapkis\FlagPal\FlagPal;
use Rapkis\FlagPal\Resources\Metric;
use Rapkis\FlagPal\Resources\FeatureSet;

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
use Rapkis\FlagPal\FlagPal;

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
use Rapkis\FlagPal\FlagPal;

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
