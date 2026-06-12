# Laravel Authentication

A backend-only, highly extensible authentication package for Laravel 12/13 with Laravel Sanctum. It ships small, single-purpose **invokable action classes** for every auth flow — login, registration, password reset, email verification, logout, password change, and token refresh — each available in **API**, **Web**, and **Mobile** variants where it makes sense.

The package provides no routes, controllers, or views. You wire the actions into your own routes/controllers and keep full control over your HTTP layer, responses, and UI.

- **License:** MIT
- **PHP:** `^8.3`
- **Laravel:** `^12.0 | ^13.0`
- **Sanctum:** `^4.0`

> For the exhaustive per-action API, DTO/result tables, and configuration reference, see [`docs/AUTHENTICATION_ACTIONS_REFERENCE.md`](docs/AUTHENTICATION_ACTIONS_REFERENCE.md).

## Why this package

- **One action, one responsibility.** Each flow is an invokable class (`$action($dto)`) returning an immutable result.
- **Channel-aware.** API (Sanctum token), Web (session), and Mobile (token + expiry) variants share an abstract base, so ~80% of the logic is reused and only the authentication strategy differs.
- **Open for extension, closed for modification.** Swap user lookup, user creation, and password validation through config — no edits to the package core. Subclass any abstract action to add audit logging, analytics, etc.
- **Immutable by design.** All DTOs and results use `readonly` properties.
- **Secure defaults.** Per-flow rate limiting, configurable password policy, cache-backed reset/verification tokens, and an event for every flow for auditing.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Laravel Sanctum 4 (for API/Mobile token flows)

## Installation

```bash
composer require aristonis/laravel-authentication
```

If you install from a private repository, add the VCS source to your app's `composer.json` first:

```json
{
    "repositories": [
        { "type": "vcs", "url": "git@github.com:aristonis/laravel-authentication.git" }
    ]
}
```

The service provider is auto-discovered. Publish the configuration:

```bash
php artisan vendor:publish \
  --provider="Aristonis\LaravelAuthentication\AuthenticationServiceProvider" \
  --tag=laravel-authentication-config
```

The package auto-loads a guarded `users` migration (it only creates the table if one does not already exist), so a standard Laravel app keeps its own users table. Make sure your `users` table has the columns the actions rely on:

```
name, email, email_verified_at, password, remember_token,
two_factor_secret (nullable), two_factor_recovery_codes (nullable)
```

Then run:

```bash
php artisan migrate
```

## User model setup

Your authenticatable model must use Sanctum's `HasApiTokens` trait so token-based actions work:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'email_verified_at', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_recovery_codes' => 'array',
        ];
    }
}
```

## Quick start

Actions are resolved from the container and invoked with a DTO.

### API (Sanctum token)

```php
use Aristonis\LaravelAuthentication\Actions\Register\ApiRegisterAction;
use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;
use Aristonis\LaravelAuthentication\Actions\Login\ApiLoginAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;

// Register (auto-login enabled by default)
$result = app(ApiRegisterAction::class)(new RegisterUserDto(
    email: 'user@example.com',
    password: 'SecurePassword123!',
    name: 'John Doe',
    ipAddress: request()->ip(),
    additional: ['username' => 'johndoe'], // extra columns
));

$result->user;          // the new user
$result->isLoggedIn();  // bool
$result->getToken();    // Bearer token (when auto-login is on)

// Login
$result = app(ApiLoginAction::class)(new LoginUserDto(
    identifier: 'user@example.com',
    password: 'SecurePassword123!',
    ipAddress: request()->ip(),
));

$token = $result->meta['token'];      // Bearer token
$type  = $result->meta['token_type']; // 'Bearer'
```

### Web (session)

```php
use Aristonis\LaravelAuthentication\Actions\Login\WebLoginAction;
use Aristonis\LaravelAuthentication\Actions\Logout\WebLogoutAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;
use Aristonis\LaravelAuthentication\Actions\Logout\LogoutDto;

app(WebLoginAction::class)(new LoginUserDto(
    identifier: 'user@example.com',
    password: 'SecurePassword123!',
));

app(WebLogoutAction::class)(new LogoutDto(user: $request->user()));
```

### Handling failures

Actions throw typed exceptions instead of returning error states — catch them in your controller or a Laravel exception handler:

```php
use Aristonis\LaravelAuthentication\Exceptions\InvalidCredentialsException;
use Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException;
use Aristonis\LaravelAuthentication\Exceptions\TwoFactorRequiredException;

try {
    $result = app(ApiLoginAction::class)($dto);
} catch (InvalidCredentialsException $e) {
    return response()->json(['message' => 'Invalid credentials'], 401);
} catch (RateLimitExceededException $e) {
    return response()->json(['message' => 'Too many attempts'], 429);
} catch (TwoFactorRequiredException $e) {
    return response()->json(['message' => '2FA required'], 403);
}
```

## Action catalog

Every flow has an `Abstract*Action` base plus the channel variants below. Invoke with the matching `*Dto`; you get back the matching `*Result`.

| Flow | API | Web | Mobile | DTO |
|------|:---:|:---:|:------:|-----|
| Login | ✅ | ✅ | ✅ | `LoginUserDto` |
| Register | ✅ | — | ✅ | `RegisterUserDto` |
| ForgotPassword | ✅ | ✅ | ✅ | `ForgotPasswordDto` |
| ResetPassword | ✅ | ✅ | ✅ | `ResetPasswordDto` |
| VerifyEmail | ✅ | ✅ | — | `VerifyEmailDto` |
| Logout | ✅ | ✅ | ✅ | `LogoutDto` |
| ChangePassword | ✅ | ✅ | ✅ | `ChangePasswordDto` |
| RefreshToken | ✅ | — | ✅ | `RefreshTokenDto` |

Class names follow the pattern `Aristonis\LaravelAuthentication\Actions\<Flow>\{Api|Web|Mobile}<Flow>Action`.

## Configuration

Published to `config/laravel-authentication.php`. Highlights (see the file for the full set):

| Section | Purpose |
|---------|---------|
| `identification.fields` | Columns to match a user against on login (e.g. `['email', 'username', 'phone']`, OR logic). |
| `identification.custom` | Class to fully replace user lookup (LDAP, OAuth, …). |
| `sanctum` | Token name, abilities, expiration for API tokens. |
| `rate_limits` | Per-flow `max_attempts` / `decay_minutes`. |
| `password` | Min length and character-class requirements. |
| `registration` | Auto-login, token settings, validation rules, custom user creator / password validator. |
| `password_reset` / `email_verification` | Cache-backed token expiry and key prefixes. |
| `logout` / `change_password` / `token_refresh` | Per-flow behavior toggles. |
| `events` | Map each auth event to your listener classes. |
| `two_factor` | 2FA enforcement settings (see note below). |

## Extension points (no core edits)

Bind your own implementations via config — the service provider resolves them automatically:

```php
// config/laravel-authentication.php
'identification' => [
    'custom' => \App\Auth\LdapUserIdentifier::class,        // implements UserIdentifierInterface
],
'registration' => [
    'user_creator'       => \App\Auth\CustomUserCreator::class,       // UserCreatorInterface
    'password_validator' => \App\Auth\CustomPasswordValidator::class, // PasswordValidatorInterface
],
```

You can also subclass any abstract action to hook into its lifecycle (e.g. `handleSuccessfulLogin`) for audit logging or analytics. See `src/Examples/CustomUserIdentifier.php` and the extension guide in the reference doc.

## Events

One event per flow is dispatched for auditing/side effects. Register listeners through the `events` config key:

```php
'events' => [
    'login_success'   => [\App\Listeners\LogSuccessfulLogin::class],
    'user_registered' => [\App\Listeners\SendWelcomeEmail::class],
    // login_failed, password_reset_link_sent, password_reset, email_verified,
    // user_logged_out, password_changed, token_refreshed
],
```

## Exceptions

| Exception | Thrown when |
|-----------|-------------|
| `InvalidCredentialsException` | Identifier/password mismatch |
| `RateLimitExceededException` | Flow rate limit exceeded |
| `TwoFactorRequiredException` | User has 2FA enabled and it must be satisfied |
| `UserAlreadyExistsException` | Registering an already-registered identifier |
| `ValidationException` | Input/password validation failed |
| `InvalidTokenException` | Reset/verification token invalid or expired |

All extend `AuthenticationException`.

## Two-factor authentication

The package **detects** 2FA: if a user has a `two_factor_secret`, login throws `TwoFactorRequiredException` so you can branch into your own challenge flow. TOTP generation/verification and challenge endpoints are left to your application (or a dedicated 2FA package). The `two_factor` config block holds issuer/digits/period settings for that integration.

## Testing

The package is tested with Pest + Orchestra Testbench:

```bash
composer install
vendor/bin/pest
```

## Security

- Per-flow rate limiting on every action.
- Configurable password policy (length + character classes).
- Reset and verification tokens stored in the cache, not the database.
- Passwords hashed via Laravel's `Hash` facade.
- An event per flow for centralized audit logging.

If you discover a security issue, please report it privately to the maintainer rather than opening a public issue.

## License

MIT. See [LICENSE](LICENSE).