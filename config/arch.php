<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Items Per Page
    |--------------------------------------------------------------------------
    |
    | This value determines the default number of items per page when using
    | paginated queries in repositories. You can override this on a per-query
    | basis by passing the itemsPerPage parameter.
    |
    */

    'default_items_per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Exception Classes
    |--------------------------------------------------------------------------
    |
    | Configure which exception classes should be used by the package.
    | You can override these to use your own custom exception classes.
    |
    */

    'exceptions' => [
        'should_not_happen' => \RuntimeException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for actions. You can specify a custom logging channel
    | that should be used for action logging. If not specified, the default
    | Laravel logging stack will be used.
    |
    */

    'action_logging' => [
        'channel' => env('ARCH_ACTION_LOG_CHANNEL', 'stack'),
        'enabled' => env('ARCH_ACTION_LOGGING_ENABLED', true),
    ],
];
