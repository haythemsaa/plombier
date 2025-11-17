<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IP Whitelist
    |--------------------------------------------------------------------------
    |
    | List of IP addresses that are allowed to access protected endpoints.
    | Leave empty to disable IP whitelisting.
    |
    */
    'ip_whitelist' => array_filter(explode(',', env('IP_WHITELIST', ''))),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting behavior for different endpoint types.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),

        'auth' => [
            'max_attempts' => env('RATE_LIMIT_AUTH_MAX', 5),
            'decay_minutes' => env('RATE_LIMIT_AUTH_DECAY', 1),
        ],

        'api' => [
            'max_attempts' => env('RATE_LIMIT_API_MAX', 60),
            'decay_minutes' => env('RATE_LIMIT_API_DECAY', 1),
        ],

        'heavy' => [
            'max_attempts' => env('RATE_LIMIT_HEAVY_MAX', 10),
            'decay_minutes' => env('RATE_LIMIT_HEAVY_DECAY', 1),
        ],

        'public' => [
            'max_attempts' => env('RATE_LIMIT_PUBLIC_MAX', 100),
            'decay_minutes' => env('RATE_LIMIT_PUBLIC_DECAY', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Logging
    |--------------------------------------------------------------------------
    |
    | Log all API requests for security auditing.
    |
    */
    'log_requests' => env('LOG_REQUESTS', false),
    'log_responses' => env('LOG_RESPONSES', false),

    /*
    |--------------------------------------------------------------------------
    | CORS Security
    |--------------------------------------------------------------------------
    */
    'cors' => [
        'strict_mode' => env('CORS_STRICT_MODE', true),
        'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security Headers
    |--------------------------------------------------------------------------
    |
    | Security headers to add to all API responses.
    |
    */
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Activity Detection
    |--------------------------------------------------------------------------
    |
    | Automatically block IPs with suspicious activity patterns.
    |
    */
    'suspicious_activity' => [
        'enabled' => env('SUSPICIOUS_ACTIVITY_DETECTION', true),
        'max_failed_attempts' => env('MAX_FAILED_LOGIN_ATTEMPTS', 5),
        'block_duration_minutes' => env('BLOCK_DURATION_MINUTES', 30),
    ],
];
