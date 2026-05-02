# Laravel Authentication Package - Complete Reference

Complete authentication package for Laravel 12-13 with Sanctum support. Backend-only, maximally extensible.

## Table of Contents

1. [Installation](#installation)
2. [Quick Start](#quick-start)
3. [Authentication Actions](#authentication-actions)
4. [Configuration](#configuration)
5. [Extension Guide](#extension-guide)
6. [API Reference](#api-reference)

---

## Installation

```bash
composer require aristonis/laravel-authentication
```

Publish configuration:
```bash
php artisan vendor:publish --provider="Aristonis\LaravelAuthentication\AuthenticationServiceProvider" --tag=laravel-authentication-config
```

---

## Quick Start

### API Authentication (Sanctum)

```php
use Aristonis\LaravelAuthentication\Actions\Login\ApiLoginAction;
use Aristonis\LaravelAuthentication\Actions\Register\ApiRegisterAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;
use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;

// Register
$registerAction = app(ApiRegisterAction::class);
$result = $registerAction(new RegisterUserDto(
    email: 'user@example.com',
    password: 'SecurePassword123!',
    name: 'John Doe'
));

// Access token
$token = $result->getToken(); // Bearer token

// Login
$loginAction = app(ApiLoginAction::class);
$result = $loginAction(new LoginUserDto(
    identifier: 'user@example.com',
    password: 'SecurePassword123!'
));

$token = $result->meta['token'];
```

### Web Authentication (Session)

```php
use Aristonis\LaravelAuthentication\Actions\Login\WebLoginAction;
use Aristonis\LaravelAuthentication\Actions\Logout\WebLogoutAction;

// Login
$loginAction = app(WebLoginAction::class);
$result = $loginAction(new LoginUserDto(
    identifier: 'user@example.com',
    password: 'SecurePassword123!'
));

// Logout
$logoutAction = app(WebLogoutAction::class);
$logoutAction(new LogoutDto(user: $user));
```

---

## Authentication Actions

### 1. Login Actions

**Purpose**: Authenticate users and establish sessions/tokens.

#### Classes
- `AbstractLoginAction` - Base class with shared logic
- `ApiLoginAction` - Returns Sanctum token
- `WebLoginAction` - Starts session
- `MobileLoginAction` - Returns token with expiration

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\Login\ApiLoginAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;

$loginAction = app(ApiLoginAction::class);
$result = $loginAction(new LoginUserDto(
    identifier: 'user@example.com',
    password: 'password',
    ipAddress: request()->ip()
));

// Result contains:
// - $result->user: Authenticatable user
// - $result->meta['token']: Bearer token
// - $result->meta['token_type']: 'Bearer'
```

#### Exceptions
- `InvalidCredentialsException` - Wrong email/password
- `RateLimitExceededException` - Too many attempts
- `TwoFactorRequiredException` - 2FA required

---

### 2. Register Actions

**Purpose**: Create new user accounts with optional auto-login.

#### Classes
- `AbstractRegisterAction` - Base class with shared logic
- `ApiRegisterAction` - Creates user + Sanctum token
- `WebRegisterAction` - Creates user (optional session)
- `MobileRegisterAction` - Creates user + token with expiration

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\Register\ApiRegisterAction;
use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;

$registerAction = app(ApiRegisterAction::class);
$result = $registerAction(new RegisterUserDto(
    email: 'user@example.com',
    password: 'SecurePassword123!',
    name: 'John Doe',
    ipAddress: request()->ip(),
    additional: ['username' => 'johndoe'] // Custom fields
));

// Result contains:
// - $result->user: New user
// - $result->isLoggedIn(): bool
// - $result->getToken(): Bearer token (if auto-login enabled)
```

#### Configuration
```php
// config/laravel-authentication.php
'registration' => [
    'auto_login' => true,
    'token' => [
        'name' => 'registration_token',
        'abilities' => ['*'],
        'expiration_days' => 0,
    ],
]
```

---

### 3. ForgotPassword Actions

**Purpose**: Send password reset links to users.

#### Classes
- `AbstractForgotPasswordAction` - Base class
- `ApiForgotPasswordAction` - Returns reset token
- `WebForgotPasswordAction` - Sends reset email
- `MobileForgotPasswordAction` - Sends reset email/SMS

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\ForgotPassword\ApiForgotPasswordAction;
use Aristonis\LaravelAuthentication\Actions\ForgotPassword\ForgotPasswordDto;

$action = app(ApiForgotPasswordAction::class);
$result = $action(new ForgotPasswordDto(
    email: 'user@example.com',
    ipAddress: request()->ip()
));

// API Result contains:
// - $result->success: bool
// - $result->resetToken: string (for API only)
// - $result->message: string
// - $result->expiresAt: Carbon (expiration time)

// Web/Mobile: Just sends email, returns success message
```

#### Configuration
```php
'forgot_password' => [
    'token_expiration' => 60, // minutes
    'cache_key_prefix' => 'password_reset_',
]
```

---

### 4. ResetPassword Actions

**Purpose**: Reset user password with valid token.

#### Classes
- `AbstractResetPasswordAction` - Base class
- `ApiResetPasswordAction` - Resets password + returns new token
- `WebResetPasswordAction` - Resets password (session-based)
- `MobileResetPasswordAction` - Resets password + new token

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\ResetPassword\ApiResetPasswordAction;
use Aristonis\LaravelAuthentication\Actions\ResetPassword\ResetPasswordDto;

$action = app(ApiResetPasswordAction::class);
$result = $action(new ResetPasswordDto(
    token: 'reset-token-from-email',
    email: 'user@example.com',
    newPassword: 'NewSecurePassword123!'
));

// Result contains:
// - $result->success: bool
// - $result->user: User model
// - $result->newToken: string (API only, if auto-login enabled)
```

---

### 5. VerifyEmail Actions

**Purpose**: Verify user email addresses.

#### Classes
- `AbstractVerifyEmailAction` - Base class
- `ApiVerifyEmailAction` - Verifies email (API)
- `WebVerifyEmailAction` - Verifies email + redirect

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\VerifyEmail\ApiVerifyEmailAction;
use Aristonis\LaravelAuthentication\Actions\VerifyEmail\VerifyEmailDto;

$action = app(ApiVerifyEmailAction::class);
$result = $action(new VerifyEmailDto(
    userId: 1,
    token: 'verification-token',
    email: 'user@example.com'
));

// Result contains:
// - $result->success: bool
// - $result->verified: bool (true if just verified)
// - $result->alreadyVerified: bool (true if already verified)
```

---

### 6. Logout Actions

**Purpose**: Revoke tokens and end sessions.

#### Classes
- `AbstractLogoutAction` - Base class
- `ApiLogoutAction` - Revokes Sanctum tokens
- `WebLogoutAction` - Destroys session
- `MobileLogoutAction` - Revokes specific device token

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\Logout\ApiLogoutAction;
use Aristonis\LaravelAuthentication\Actions\Logout\LogoutDto;

$action = app(ApiLogoutAction::class);
$result = $action(new LogoutDto(
    user: $user,
    tokenId: $tokenId, // Optional: revoke specific token
    revokeAll: false   // Optional: revoke all tokens
));

// Result contains:
// - $result->success: bool
// - $result->message: string
```

#### Configuration
```php
'logout' => [
    'revoke_all_tokens' => false,
    'clear_session' => true,
]
```

---

### 7. ChangePassword Actions

**Purpose**: Change authenticated user's password.

#### Classes
- `AbstractChangePasswordAction` - Base class
- `ApiChangePasswordAction` - Changes password (API)
- `WebChangePasswordAction` - Changes password (session)
- `MobileChangePasswordAction` - Changes password + new token

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\ChangePassword\ApiChangePasswordAction;
use Aristonis\LaravelAuthentication\Actions\ChangePassword\ChangePasswordDto;

$action = app(ApiChangePasswordAction::class);
$result = $action(new ChangePasswordDto(
    user: $user,
    oldPassword: 'OldPassword123!',
    newPassword: 'NewSecurePassword456!'
));

// Result contains:
// - $result->success: bool
// - $result->newToken: string (if regenerate_token enabled)
```

#### Configuration
```php
'change_password' => [
    'require_current_password' => true,
    'regenerate_token' => false,
    'min_change_interval' => 0, // minutes
]
```

---

### 8. RefreshToken Actions

**Purpose**: Refresh expired/expiring Sanctum tokens.

#### Classes
- `AbstractRefreshTokenAction` - Base class
- `ApiRefreshTokenAction` - Refreshes token (standard)
- `MobileRefreshTokenAction` - Refreshes token (mobile-specific)

#### Usage
```php
use Aristonis\LaravelAuthentication\Actions\RefreshToken\ApiRefreshTokenAction;
use Aristonis\LaravelAuthentication\Actions\RefreshToken\RefreshTokenDto;

$action = app(ApiRefreshTokenAction::class);
$result = $action(new RefreshTokenDto(
    user: $user,
    oldToken: $currentToken,
    revokeOld: true
));

// Result contains:
// - $result->newToken: string
// - $result->expiresAt: Carbon
// - $result->tokenType: 'Bearer'
```

#### Configuration
```php
'token_refresh' => [
    'enabled' => true,
    'revoke_old_token' => true,
    'expiration_days' => 0,
]
```

---

## Configuration

Full configuration reference:

```php
return [
    // User Identification
    'identification' => [
        'fields' => ['email'], // Can add 'username', 'phone'
        'custom' => null, // Custom identifier class
    ],

    // Sanctum
    'sanctum' => [
        'token_name' => 'auth_token',
        'abilities' => ['*'],
        'expiration_days' => 0,
    ],

    // Rate Limits
    'rate_limits' => [
        'login' => ['max_attempts' => 5, 'decay_minutes' => 1],
        'registration' => ['max_attempts' => 3, 'decay_minutes' => 5],
        'forgot_password' => ['max_attempts' => 3, 'decay_minutes' => 10],
        'reset_password' => ['max_attempts' => 5, 'decay_minutes' => 5],
        'verify_email' => ['max_attempts' => 5, 'decay_minutes' => 5],
        'change_password' => ['max_attempts' => 3, 'decay_minutes' => 10],
        'logout' => ['max_attempts' => 10, 'decay_minutes' => 1],
        'refresh_token' => ['max_attempts' => 5, 'decay_minutes' => 5],
    ],

    // Password Rules
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ],

    // Registration
    'registration' => [
        'auto_login' => true,
        'token' => [
            'name' => 'registration_token',
            'abilities' => ['*'],
            'expiration_days' => 0,
        ],
        'validation' => [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'name' => ['nullable', 'string', 'max:255'],
        ],
    ],

    // Password Reset
    'password_reset' => [
        'token_expiration' => 60,
        'cache_key_prefix' => 'password_reset_',
    ],

    // Email Verification
    'email_verification' => [
        'required' => false,
        'token_expiration' => 1440,
        'cache_key_prefix' => 'email_verify_',
    ],

    // Logout
    'logout' => [
        'revoke_all_tokens' => false,
        'clear_session' => true,
    ],

    // Change Password
    'change_password' => [
        'require_current_password' => true,
        'regenerate_token' => false,
    ],

    // Token Refresh
    'token_refresh' => [
        'enabled' => true,
        'revoke_old_token' => true,
    ],

    // Events
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
];
```

---

## Extension Guide

### Override User Identification

```php
namespace App\Identifier;

use Aristonis\LaravelAuthentication\Identification\UserIdentifier;

class CustomUserIdentifier extends UserIdentifier
{
    public function findUser(string $identifier): ?Authenticatable
    {
        // Custom logic: LDAP, OAuth, etc.
        return User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('phone', $identifier)
            ->first();
    }
}

// config/laravel-authentication.php
'identification' => [
    'custom' => \App\Identifier\CustomUserIdentifier::class,
]
```

### Custom Password Validator

```php
namespace App\Services;

use Aristonis\LaravelAuthentication\Services\DefaultPasswordValidator;

class CustomPasswordValidator extends DefaultPasswordValidator
{
    public function validate(string $password): array
    {
        $errors = parent::validate($password);
        
        // Add custom rules
        if (str_contains($password, 'password')) {
            $errors[] = 'Password cannot contain the word "password"';
        }
        
        return $errors;
    }
}

// config/laravel-authentication.php
'registration' => [
    'password_validator' => \App\Services\CustomPasswordValidator::class,
]
```

### Extend Action Classes

```php
namespace App\Actions;

use Aristonis\LaravelAuthentication\Actions\Login\AbstractLoginAction;

class CustomLoginAction extends AbstractLoginAction
{
    protected function handleSuccessfulLogin(
        Authenticatable $user,
        LoginUserDto $dto
    ): void {
        parent::handleSuccessfulLogin($user, $dto);
        
        // Add custom logic: audit logging, analytics, etc.
        Log::info('User logged in', ['user_id' => $user->id]);
    }
}
```

---

## API Reference

### DTOs (Data Transfer Objects)

All DTOs are immutable (`readonly`):

| DTO | Properties |
|-----|-----------|
| `LoginUserDto` | `identifier`, `password`, `ipAddress` |
| `RegisterUserDto` | `email`, `password`, `name`, `ipAddress`, `additional` |
| `ForgotPasswordDto` | `email`, `ipAddress` |
| `ResetPasswordDto` | `token`, `email`, `newPassword` |
| `VerifyEmailDto` | `userId`, `token`, `email` |
| `LogoutDto` | `user`, `tokenId`, `revokeAll` |
| `ChangePasswordDto` | `user`, `oldPassword`, `newPassword` |
| `RefreshTokenDto` | `user`, `oldToken`, `revokeOld` |

### Result Classes

| Result | Properties |
|--------|-----------|
| `LoginUserResult` | `user`, `meta` |
| `RegisterUserResult` | `user`, `meta`, `loggedIn` |
| `ForgotPasswordResult` | `success`, `resetToken`, `message`, `expiresAt` |
| `ResetPasswordResult` | `success`, `user`, `newToken` |
| `VerifyEmailResult` | `success`, `verified`, `alreadyVerified` |
| `LogoutResult` | `success`, `message` |
| `ChangePasswordResult` | `success`, `newToken` |
| `RefreshTokenResult` | `newToken`, `expiresAt`, `tokenType` |

### Events

| Event | Data |
|-------|------|
| `UserRegisteredEvent` | `user`, `loggedIn` |
| `LoginSuccessEvent` | `user` |
| `LoginFailedEvent` | `identifier`, `reason` |
| `PasswordResetLinkSentEvent` | `user`, `channel` |
| `PasswordResetEvent` | `user` |
| `EmailVerifiedEvent` | `user` |
| `UserLoggedOutEvent` | `user`, `channel` |
| `PasswordChangedEvent` | `user` |
| `TokenRefreshedEvent` | `user`, `newToken` |

### Exceptions

| Exception | When |
|-----------|------|
| `InvalidCredentialsException` | Wrong email/password |
| `RateLimitExceededException` | Too many attempts |
| `TwoFactorRequiredException` | 2FA required |
| `UserAlreadyExistsException` | Email already registered |
| `ValidationException` | Validation failed |
| `InvalidTokenException` | Token invalid/expired |

---

## Testing

Run tests:
```bash
vendor/bin/pest
```

Test coverage: 80%+ minimum

Example test:
```php
it('can register a new user', function () {
    $registerAction = app(ApiRegisterAction::class);
    
    $result = $registerAction(new RegisterUserDto(
        email: 'test@example.com',
        password: 'SecurePassword123!',
        name: 'Test User'
    ));
    
    expect($result->user)->toBeInstanceOf(User::class);
    expect($result->isLoggedIn())->toBeTrue();
    expect($result->getToken())->not->toBeNull();
});
```

---

## Security Features

- **Rate Limiting**: All auth endpoints rate-limited
- **Immutable Data**: All DTOs/results use readonly properties
- **Token Storage**: Reset tokens in cache (not database)
- **Password Hashing**: Laravel's Hash facade (bcrypt)
- **Event Auditing**: All auth events dispatched for logging

---

## License

MIT License
