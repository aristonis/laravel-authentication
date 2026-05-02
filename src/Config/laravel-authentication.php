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
        'forgot_password' => [
            'max_attempts' => 3,
            'decay_minutes' => 10,
        ],
        'reset_password' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
        ],
        'verify_email' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
        ],
        'change_password' => [
            'max_attempts' => 3,
            'decay_minutes' => 10,
        ],
        'logout' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'refresh_token' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
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
    | Registration Configuration
    |--------------------------------------------------------------------------
    |
    | Configure user registration behavior, validation, and extension points.
    |
    |--------------------------------------------------------------------------
    */
    'registration' => [
        // Auto-login after registration (default: true)
        'auto_login' => true,

        // Token configuration for auto-login
        'token' => [
            'name' => 'registration_token',
            'abilities' => ['*'],
            'expiration_days' => 0, // 0 = no expiration
        ],

        // Validation rules for registration fields
        'validation' => [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'name' => ['nullable', 'string', 'max:255'],
        ],

        // Required fields (for UI hints and validation)
        'required_fields' => ['email', 'password'],

        // Extension point: Custom user creator class
        // Set to your class to override default user creation logic
        // Example: \App\Services\CustomUserCreator::class
        'user_creator' => null,

        // Extension point: Custom password validator class
        // Set to your class to override default password validation
        // Example: \App\Services\CustomPasswordValidator::class
        'password_validator' => null,
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
        'password_reset_link_sent' => [],
        'password_reset' => [],
        'email_verified' => [],
        'user_logged_out' => [],
        'password_changed' => [],
        'token_refreshed' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configure password reset token behavior.
    | Tokens are stored in cache (not database) for better performance.
    |
    */
    'password_reset' => [
        // Token expiration in minutes
        'token_expiration' => 60,

        // Cache store to use (default: Laravel's default cache)
        'cache_store' => null,

        // Cache key prefix
        'cache_key_prefix' => 'password_reset_',

        // Token length (characters)
        'token_length' => 64,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification Configuration
    |--------------------------------------------------------------------------
    */
    'email_verification' => [
        // Enable email verification requirement
        'required' => false,

        // Verification token expiration in minutes
        'token_expiration' => 1440, // 24 hours

        // Cache key prefix for verification tokens
        'cache_key_prefix' => 'email_verify_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logout Configuration
    |--------------------------------------------------------------------------
    */
    'logout' => [
        // Revoke all tokens on logout (API/Mobile)
        'revoke_all_tokens' => false,

        // Clear session data on logout (Web)
        'clear_session' => true,

        // Logout event logging
        'log_logout' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Change Password Configuration
    |--------------------------------------------------------------------------
    */
    'change_password' => [
        // Require current password verification
        'require_current_password' => true,

        // Force token regeneration after password change (API)
        'regenerate_token' => false,

        // Minimum time between password changes (minutes, 0 = no limit)
        'min_change_interval' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Refresh Configuration
    |--------------------------------------------------------------------------
    */
    'token_refresh' => [
        // Enable token refresh functionality
        'enabled' => true,

        // Revoke old token when refreshing
        'revoke_old_token' => true,

        // Token expiration for refreshed tokens (days, 0 = same as original)
        'expiration_days' => 0,
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
