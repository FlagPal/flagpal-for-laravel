<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conductor API base URL
    |--------------------------------------------------------------------------
    |
    | This URL is used to send HTTP requests to the Conductor API.
    |
    */
    'base_url' => env('CONDUCTOR_URL'),

    /*
    |--------------------------------------------------------------------------
    | Default Project
    |--------------------------------------------------------------------------
    |
    | This option controls the default project that will be used by the SDK.
    | This project is utilized if another isn't explicitly
    | specified when using the Conductor class.
    |
    */
    'default_project' => env('CONDUCTOR_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | Conductor Projects
    |--------------------------------------------------------------------------
    |
    | Below are all of the projects used by your application to interact with
    | Conductor. An example project is provided below.
    | You can find project details in your Conductor dashboard.
    |
    */
    'projects' => [
        'My Project' => [
            'name' => 'My Project',
            'bearer_token' => env('CONDUCTOR_MY_PROJECT_TOKEN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching options
    |--------------------------------------------------------------------------
    |
    | Cache is used by default to speed up performance of your application.
    |
    | You may customise the cache driver by using any of the
    | `store` drivers listed in the cache.php config.
    | Using 'default' here means to use the `default` set in cache.php.
    |
    | If no value is provided for the driver, Conductor defaults
    | to an `array` cache driver internally to serve as temporary cache
    | within the request's lifetime.
    |
    | The ttl variable defines for how many seconds the cache is kept.
    |
    */
    'cache' => [
        'driver' => 'default',
        'ttl' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging options
    |--------------------------------------------------------------------------
    |
    | Logs are used to report any errors when performing operations
    | and are optional but recommended.
    |
    | You may customise the log driver by using any of the
    | `store` drivers listed in the logging.php config.
    | Using 'default' here means to use the `default` set in logging.php.
    | To turn off logging, you may set the driver to `null` or
    | delete the option completely.
    |
    */
    'log' => [
        'driver' => 'default',
    ],
];
