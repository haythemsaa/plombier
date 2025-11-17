<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry DSN (Data Source Name)
    |--------------------------------------------------------------------------
    |
    | The DSN tells the SDK where to send the events. Get this from your
    | Sentry project settings.
    |
    */
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Breadcrumbs are a trail of events that happened prior to an error.
    | They can be very helpful in understanding the context of an error.
    |
    */
    'breadcrumbs' => [
        // Capture SQL queries as breadcrumbs
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES_ENABLED', true),

        // Capture SQL bindings (parameters) in breadcrumbs
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS_ENABLED', false),

        // Capture queue job information
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE_INFO_ENABLED', true),

        // Capture command information
        'command_info' => env('SENTRY_BREADCRUMBS_COMMAND_INFO_ENABLED', true),

        // Capture HTTP client requests
        'http_client_requests' => env('SENTRY_BREADCRUMBS_HTTP_CLIENT_REQUESTS_ENABLED', true),

        // Capture logs as breadcrumbs
        'logs' => env('SENTRY_BREADCRUMBS_LOGS_ENABLED', true),

        // Capture cache events
        'cache' => env('SENTRY_BREADCRUMBS_CACHE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    |
    | Performance monitoring with tracing.
    |
    */
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    // Send additional default PII (Personally Identifiable Information)
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | Set the version of your application. This is used to track which
    | release an error occurred in.
    |
    */
    'release' => env('SENTRY_RELEASE', null),

    /*
    |--------------------------------------------------------------------------
    | Server Name
    |--------------------------------------------------------------------------
    */
    'server_name' => env('SENTRY_SERVER_NAME', gethostname()),

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | List of exceptions that should not be sent to Sentry.
    |
    */
    'ignore_exceptions' => [
        Illuminate\Auth\AuthenticationException::class,
        Illuminate\Http\Exceptions\ThrottleRequestsException::class,
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Transactions
    |--------------------------------------------------------------------------
    |
    | Transactions that match these patterns will not be sent to Sentry.
    |
    */
    'ignore_transactions' => [
        // Health check endpoints
        'GET /health',
        'GET /api/health',

        // Heartbeat endpoints
        'GET /ping',
        'GET /api/ping',
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before sending any event to Sentry.
    | You can modify the event or return null to prevent it from being sent.
    |
    */
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Don't send events in local development
        if (app()->environment('local', 'testing')) {
            return null;
        }

        return $event;
    },

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Configure which integrations should be enabled.
    |
    */
    'integrations' => [
        // Enable or disable all default integrations
        'default' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Lines
    |--------------------------------------------------------------------------
    |
    | The number of lines of code context to capture around each stack frame.
    |
    */
    'context_lines' => 5,

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for error events (0.0 to 1.0).
    | 1.0 = 100% of errors are sent, 0.5 = 50% of errors are sent.
    |
    */
    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),
];
