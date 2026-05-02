# RegisterAction Implementation Quick Reference

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     RegisterAction Flow                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Request → Validate → Check Rate Limit → Check Duplicates      │
│             ↓                                                  │
│  Create User → Hash Password → Save to DB                      │
│             ↓                                                  │
│  [Optional: Auto-Login → Create Token]                         │
│             ↓                                                  │
│  Dispatch Event → Return Result                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## File Structure to Create

```
src/Actions/Register/
├── AbstractRegisterAction.php      # Base class (like AbstractLoginAction)
├── ApiRegisterAction.php           # Creates Sanctum token
├── RegisterUserDto.php             # Input: email, password, name, ip
├── RegisterUserResult.php          # Output: user, meta[token, etc.]
└── Events/
    └── UserRegisteredEvent.php     # Fires on success

src/Contracts/
├── UserCreatorInterface.php        # Extension: custom user creation
└── PasswordValidatorInterface.php  # Extension: custom password rules

src/Rules/
└── PasswordRule.php                # Laravel validation rule for passwords
```

## Key Interfaces

### UserCreatorInterface

```php
namespace Aristonis\LaravelAuthentication\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserCreatorInterface
{
    /**
     * Create a new user.
     *
     * @param array<string, mixed> $attributes
     * @return Authenticatable
     */
    public function create(array $attributes): Authenticatable;
}
```

### PasswordValidatorInterface

```php
namespace Aristonis\LaravelAuthentication\Contracts;

interface PasswordValidatorInterface
{
    /**
     * Validate password.
     *
     * @param string $password
     * @return array<string> Array of error messages (empty if valid)
     */
    public function validate(string $password): array;
}
```

## AbstractRegisterAction Skeleton

```php
<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Aristonis\LaravelAuthentication\Actions\Register\Events\UserRegisteredEvent;
use Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException;
use Aristonis\LaravelAuthentication\Exceptions\UserAlreadyExistsException;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @template T of RegisterUserResult
 */
abstract class AbstractRegisterAction
{
    public function __construct(
        protected readonly RateLimitService $rateLimitService,
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserCreatorInterface $userCreator,
        protected readonly PasswordValidatorInterface $passwordValidator,
    ) {}

    /**
     * Execute registration.
     */
    public function __invoke(RegisterUserDto $dto): RegisterUserResult
    {
        // 1. Check rate limit
        $this->checkRateLimit($dto->email);

        // 2. Validate input
        $this->validate($dto);

        // 3. Check for existing user
        if ($this->userExists($dto->email)) {
            throw new UserAlreadyExistsException('User already exists');
        }

        // 4. Create user
        $user = $this->createUser($dto);

        // 5. Auto-login if enabled
        $result = $this->handleAutoLogin($user, $dto);

        // 6. Dispatch event
        event(new UserRegisteredEvent($user, $result->isLoggedIn()));

        return $result;
    }

    /**
     * Validate registration data.
     */
    protected function validate(RegisterUserDto $dto): void
    {
        // Validate password strength
        $passwordErrors = $this->passwordValidator->validate($dto->password);
        if (!empty($passwordErrors)) {
            throw new ValidationException($passwordErrors);
        }

        // Additional validation...
    }

    /**
     * Check if user exists.
     */
    protected function userExists(string $email): bool
    {
        $modelClass = config('auth.providers.users.model');
        return $modelClass::where('email', $email)->exists();
    }

    /**
     * Create user.
     */
    protected function createUser(RegisterUserDto $dto): Authenticatable
    {
        return $this->userCreator->create([
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'name' => $dto->name,
        ]);
    }

    /**
     * Handle auto-login - IMPLEMENT PER TYPE.
     */
    abstract protected function handleAutoLogin(
        Authenticatable $user,
        RegisterUserDto $dto
    ): RegisterUserResult;

    /**
     * Check rate limit.
     */
    protected function checkRateLimit(string $email): void
    {
        if ($this->rateLimitService->isRateLimited('registration', $email)) {
            throw new RateLimitExceededException('Too many registration attempts');
        }
    }
}
```

## DTO Structure

```php
<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

/**
 * @property string $email
 * @property string $password
 * @property string|null $name
 * @property string|null $ipAddress
 * @property array<string, mixed> $additional
 */
final readonly class RegisterUserDto
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $name = null,
        public ?string $ipAddress = null,
        public array $additional = [],
    ) {}
}
```

## Result Structure

```php
<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

use Illuminate\Contracts\Auth\Authenticatable;

class RegisterUserResult
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly array $meta = [],
        private readonly bool $loggedIn = false,
    ) {}

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    public function getToken(): ?string
    {
        return $this->meta['token'] ?? null;
    }
}
```

## Event Structure

```php
<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly bool $autoLoggedIn = false,
    ) {}
}
```

## Configuration to Add

Add to `config/laravel-authentication.php`:

```php
'registration' => [
    // Auto-login after registration
    'auto_login' => true,

    // Token settings for auto-login
    'token' => [
        'name' => 'registration_token',
        'abilities' => ['*'],
        'expiration_days' => 0,
    ],

    // Validation rules
    'validation' => [
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8'],
        'name' => ['nullable', 'string', 'max:255'],
    ],

    // Required fields
    'required_fields' => ['email', 'password'],

    // Extension points
    'user_creator' => null,
    'password_validator' => null,
],
```

## Service Provider Updates

If needed, bind new interfaces:

```php
// In register() method
$this->app->bind(
    UserCreatorInterface::class,
    DefaultUserCreator::class
);

$this->app->bind(
    PasswordValidatorInterface::class,
    DefaultPasswordValidator::class
);
```

## Default Implementations

### DefaultUserCreator

```php
<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Services;

use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class DefaultUserCreator implements UserCreatorInterface
{
    public function create(array $attributes): Authenticatable
    {
        $modelClass = config('auth.providers.users.model');
        return $modelClass::create($attributes);
    }
}
```

### DefaultPasswordValidator

```php
<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Services;

use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;

class DefaultPasswordValidator implements PasswordValidatorInterface
{
    public function validate(string $password): array
    {
        $errors = [];
        $config = config('auth-package.password', []);

        // Min length
        $minLength = $config['min_length'] ?? 8;
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }

        // Uppercase
        if (!empty($config['require_uppercase']) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Lowercase
        if (!empty($config['require_lowercase']) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Numbers
        if (!empty($config['require_numbers']) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Symbols
        if (!empty($config['require_symbols']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one symbol';
        }

        return $errors;
    }
}
```

## Action Variants

### ApiRegisterAction

```php
class ApiRegisterAction extends AbstractRegisterAction
{
    protected function handleAutoLogin(
        Authenticatable $user,
        RegisterUserDto $dto
    ): RegisterUserResult {
        if (!config('auth-package.registration.auto_login', true)) {
            return new RegisterUserResult(user: $user, loggedIn: false);
        }

        $token = $this->tokenService->createToken(
            $user,
            config('auth-package.registration.token.name'),
            config('auth-package.registration.token.abilities')
        );

        return new RegisterUserResult(
            user: $user,
            meta: [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            loggedIn: true,
        );
    }
}
```

### MobileRegisterAction

```php
class MobileRegisterAction extends AbstractRegisterAction
{
    public function __construct(
        RateLimitService $rateLimitService,
        TokenServiceInterface $tokenService,
        UserCreatorInterface $userCreator,
        PasswordValidatorInterface $passwordValidator,
        private readonly int $tokenExpirationDays = 30,
    ) {
        parent::__construct($rateLimitService, $tokenService, $userCreator, $passwordValidator);
    }

    protected function handleAutoLogin(
        Authenticatable $user,
        RegisterUserDto $dto
    ): RegisterUserResult {
        $expiresAt = now()->addDays($this->tokenExpirationDays);

        $token = $this->tokenService->createToken(
            $user,
            'mobile-registration',
            ['mobile'],
            $expiresAt
        );

        return new RegisterUserResult(
            user: $user,
            meta: [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->tokenExpirationDays * 24 * 60 * 60,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            loggedIn: true,
        );
    }
}
```

## Test Structure

```
tests/Actions/Register/
├── AbstractRegisterActionTest.php
├── ApiRegisterActionTest.php
├── RegisterUserDtoTest.php
├── RegisterUserResultTest.php
└── Events/
    └── UserRegisteredEventTest.php
```

## Test Example

```php
<?php

use Aristonis\LaravelAuthentication\Actions\Register\ApiRegisterAction;
use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;
use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Aristonis\LaravelAuthentication\Services\TokenService;

test('registers user successfully', function () {
    $rateLimitService = mock(RateLimitService::class);
    $tokenService = mock(TokenService::class);
    $userCreator = mock(UserCreatorInterface::class);
    $passwordValidator = mock(PasswordValidatorInterface::class);

    $passwordValidator->expects('validate')->with('password123')->andReturn([]);
    $rateLimitService->expects('isRateLimited')->andReturn(false);

    $user = new User(['email' => 'test @example.com', 'name' => 'Test']);
    $userCreator->expects('create')->andReturn($user);

    $tokenService->expects('createToken')->andReturn('plain-text-token');

    $action = new ApiRegisterAction(
        $rateLimitService,
        $tokenService,
        $userCreator,
        $passwordValidator
    );

    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123',
        name: 'Test'
    );

    $result = $action($dto);

    expect($result->user)->toBe($user);
    expect($result->getToken())->toBe('plain-text-token');
    expect($result->isLoggedIn())->toBeTrue();
});
```

## Common Pitfalls

| Pitfall | Solution |
|---------|----------|
| Mutating DTO | DTO is `readonly` - create new instance if needed |
| Exposing password hash | Never include in response, use model hiding |
| Forgetting rate limit recording | Call `$rateLimitService->record()` on failures |
| Hardcoded validation | Use config, make overridable |
| Not regenerating session (web) | Call `session()->regenerate()` |
| Token in logs | Document secure logging practices |
| Race condition on duplicate check | Use DB unique constraint as final guard |

## Implementation Order

1. **Day 1**: Core classes (DTO, Result, Event, AbstractRegisterAction)
2. **Day 2**: Contracts (UserCreatorInterface, PasswordValidatorInterface) + defaults
3. **Day 3**: Action variants (Api, Web, Mobile)
4. **Day 4**: Configuration + Service Provider updates
5. **Day 5**: Tests (unit + integration)
6. **Day 6**: Documentation + examples

## Success Checklist

- [ ] All classes follow immutability pattern
- [ ] All public methods have PHPDoc
- [ ] Configuration is fully documented
- [ ] Extension points are tested
- [ ] 80%+ test coverage
- [ ] No breaking changes to existing code
- [ ] Security review passed
- [ ] README updated with examples

---

**Quick Start**: Copy the skeletons above, then customize based on the full requirements document.
