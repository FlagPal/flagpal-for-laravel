<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conductor API base URL
    |--------------------------------------------------------------------------
    |
    | This URL is used to send HTTP requests to the Condcutor API.
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
    | specified when using the Condcutor class.
    |
    */
    'default_project' => env('CONDUCTOR_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | Conductor Projects
    |--------------------------------------------------------------------------
    |
    | Below are all of the projects used by your application to interact with
    | Condcutor. An example project is provided below.
    | You can find project details in your Condcutor dashboard.
    |
    */
    'projects' => [
        'My Project' => [
            'name' => 'My Project',
            'bearer_token' => env('CONDUCTOR_MY_PROJECT_TOKEN'),
        ],
    ],
];
