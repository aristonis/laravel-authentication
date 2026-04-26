<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Namespace Configuration
    |--------------------------------------------------------------------------
    */
    'namespace' => [
        'base' => 'App\\Packages\\Authentication',
        'actions' => 'Actions',
        'services' => 'Services',
        'dto' => 'Dtos',
        'contracts' => 'Contracts',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Identification
    |--------------------------------------------------------------------------
    |
    | Configure how users are identified during login.
    | The package will search across all specified fields using OR logic.
    |
    | Examples:
    | - ['email'] → WHERE email = ?
    | - ['email', 'username'] → WHERE email = ? OR username = ?
    | - ['email', 'username', 'phone'] → WHERE email = ? OR username = ? OR phone = ?
    |
    | For custom logic (LDAP, OAuth, etc.), set 'custom' to your class.
    |--------------------------------------------------------------------------
    */
    'identification' => [
        // Fields to search for user identification
        'fields' => ['email'],

        // Custom identifier class (overrides 'fields' if set)
        // Example: \App\Identifier\LdapIdentifier::class
        'custom' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanctum Configuration
    |--------------------------------------------------------------------------
    */
    'sanctum' => [
        'token_name' => 'auth_token',
        'abilities' => ['*'],
        'expiration_days' => 0,
        'refresh_on_request' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'registration' => [
            'max_attempts' => 3,
            'decay_minutes' => 5,
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'decay_minutes' => 10,
        ],
        'two_factor' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Rules
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
        'max_breached_level' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        'enabled' => false,
        'required_for_roles' => [],
        'issuer' => env('APP_NAME', 'Laravel'),
        'digits' => 6,
        'period' => 30,
        'backup_codes_count' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events & Listeners
    |--------------------------------------------------------------------------
    */
    'events' => [
        'user_registered' => [],
        'login_success' => [],
        'login_failed' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Discovery
    |--------------------------------------------------------------------------
    */
    'discovery' => [
        'auto_discover' => true,
        'scan_directories' => ['Actions'],
        'manual_bindings' => [],
    ],
];
