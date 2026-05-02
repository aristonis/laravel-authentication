<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\Register\ApiRegisterAction;
use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;
use Aristonis\LaravelAuthentication\Actions\Register\Events\UserRegisteredEvent;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException;
use Aristonis\LaravelAuthentication\Exceptions\UserAlreadyExistsException;
use Aristonis\LaravelAuthentication\Exceptions\ValidationException;
use Aristonis\LaravelAuthentication\Services\DefaultPasswordValidator;
use Aristonis\LaravelAuthentication\Services\DefaultUserCreator;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Aristonis\LaravelAuthentication\Services\TokenService;
use Aristonis\LaravelAuthentication\Tests\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createApiRegisterAction(): ApiRegisterAction
{
    return new ApiRegisterAction(
        app(RateLimitService::class),
        app(\Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface::class),
        app(UserCreatorInterface::class),
        app(PasswordValidatorInterface::class)
    );
}

test('registration succeeds with valid data', function () {
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'newuser @example.com',
        password: 'ValidPass123',
        name: 'New User'
    );

    $result = $action($dto);

    expect($result->user)->toBeInstanceOf(User::class);
    expect($result->user->email)->toBe('newuser @example.com');
    expect($result->user->name)->toBe('New User');
    expect($result->isLoggedIn())->toBeTrue();
    expect($result->getToken())->not->toBeEmpty();
    expect($result->getTokenType())->toBe('Bearer');
});

test('registration creates user in database', function () {
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'dbuser @example.com',
        password: 'ValidPass123',
        name: 'DB User'
    );

    $result = $action($dto);

    expect(User::where('email', 'dbuser @example.com')->exists())->toBeTrue();

    $savedUser = User::where('email', 'dbuser @example.com')->first();
    expect($savedUser->name)->toBe('DB User');
    expect(Hash::check('ValidPass123', $savedUser->password))->toBeTrue();
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'existing @example.com']);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'existing @example.com',
        password: 'ValidPass123',
        name: 'Existing User'
    );

    expect(fn () => $action($dto))
        ->toThrow(UserAlreadyExistsException::class);
});

test('registration fails with weak password', function () {
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'weak @example.com',
        password: 'weak',
        name: 'Weak User'
    );

    expect(fn () => $action($dto))
        ->toThrow(ValidationException::class);
});

test('registration fails when rate limited', function () {
    // Simulate rate limit exceeded
    Cache::set('rate_limit:registration:' . md5('ratelimited @example.com'), 10, 60);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'ratelimited @example.com',
        password: 'ValidPass123',
        name: 'Rate Limited User'
    );

    expect(fn () => $action($dto))
        ->toThrow(RateLimitExceededException::class);
});

test('registration dispatches UserRegisteredEvent', function () {
    Event::fake();

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'eventuser @example.com',
        password: 'ValidPass123',
        name: 'Event User'
    );

    $action($dto);

    Event::assertDispatched(
        UserRegisteredEvent::class,
        function ($event) {
            return $event->user->email === 'eventuser @example.com'
                && $event->autoLoggedIn === true;
        }
    );
});

test('registration creates sanctum token', function () {
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'tokenuser @example.com',
        password: 'ValidPass123',
        name: 'Token User'
    );

    $result = $action($dto);

    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($result->getToken());
    expect($token)->not->toBeNull();
    expect($token->tokenable->email)->toBe('tokenuser @example.com');
});

test('registration token has correct abilities', function () {
    config(['auth-package.registration.token.abilities' => ['read', 'write']]);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'abilitiesuser @example.com',
        password: 'ValidPass123',
        name: 'Abilities User'
    );

    $result = $action($dto);

    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($result->getToken());
    expect($token->abilities)->toBe(['read', 'write']);
});

test('registration token has correct name', function () {
    config(['auth-package.registration.token.name' => 'custom_registration_token']);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'nameduser @example.com',
        password: 'ValidPass123',
        name: 'Named User'
    );

    $result = $action($dto);

    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($result->getToken());
    expect($token->name)->toBe('custom_registration_token');
});

test('registration without auto-login', function () {
    config(['auth-package.registration.auto_login' => false]);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'nologinuser @example.com',
        password: 'ValidPass123',
        name: 'No Login User'
    );

    $result = $action($dto);

    expect($result->isLoggedIn())->toBeFalse();
    expect($result->getToken())->toBeNull();
});

test('registration with token expiration', function () {
    config(['auth-package.registration.token.expiration_days' => 30]);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'expiryuser @example.com',
        password: 'ValidPass123',
        name: 'Expiry User'
    );

    $result = $action($dto);

    expect($result->getTokenExpiration())->not->toBeNull();
    expect($result->getTokenExpiration())->toContain(now()->addDays(30)->format('Y-m-d'));
});

test('registration clears rate limit on success', function () {
    $email = 'clearuser @example.com';

    // Set initial rate limit counter
    Cache::set('rate_limit:registration:' . md5($email), 2, 60);

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: $email,
        password: 'ValidPass123',
        name: 'Clear User'
    );

    $action($dto);

    // Rate limit should be cleared
    expect(Cache::get('rate_limit:registration:' . md5($email)))->toBeNull();
});

test('registration records failed attempt on validation error', function () {
    $email = 'faileduser @example.com';

    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: $email,
        password: 'weak',
        name: 'Failed User'
    );

    try {
        $action($dto);
    } catch (ValidationException) {
        // Expected
    }

    // Rate limit counter should be incremented
    expect(Cache::get('rate_limit:registration:' . md5($email)))->toBe(1);
});

test('registration with additional fields', function () {
    // Note: The default User model doesn't have custom fields like age/role
    // This test verifies that additional fields are passed through
    // In production, you'd have a custom User model with these fields
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'additionaluser @example.com',
        password: 'ValidPass123',
        name: 'Additional User',
        additional: ['email_verified_at' => now()]
    );

    $result = $action($dto);

    expect($result->user->email)->toBe('additionaluser @example.com');
    expect($result->user->email_verified_at)->not->toBeNull();
});

test('registration password is hashed', function () {
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'hashuser @example.com',
        password: 'ValidPass123',
        name: 'Hash User'
    );

    $result = $action($dto);

    $savedUser = User::where('email', 'hashuser @example.com')->first();
    expect($savedUser->password)->not->toBe('ValidPass123');
    expect(Hash::check('ValidPass123', $savedUser->password))->toBeTrue();
});

test('registration with invalid email format fails', function () {
    $action = createApiRegisterAction();

    $dto = new RegisterUserDto(
        email: 'invalid-email',
        password: 'ValidPass123',
        name: 'Invalid Email User'
    );

    expect(fn () => $action($dto))
        ->toThrow(ValidationException::class);
});

test('registration event has correct autoLoggedIn flag', function () {
    Event::fake();

    // With auto-login enabled
    config(['auth-package.registration.auto_login' => true]);
    $action = createApiRegisterAction();
    $dto = new RegisterUserDto(
        email: 'eventuser1 @example.com',
        password: 'ValidPass123',
        name: 'Event User 1'
    );
    $action($dto);

    Event::assertDispatched(
        UserRegisteredEvent::class,
        fn ($event) => $event->autoLoggedIn === true
    );

    // With auto-login disabled
    config(['auth-package.registration.auto_login' => false]);
    $action2 = createApiRegisterAction();
    $dto2 = new RegisterUserDto(
        email: 'eventuser2 @example.com',
        password: 'ValidPass123',
        name: 'Event User 2'
    );
    $action2($dto2);

    Event::assertDispatched(
        UserRegisteredEvent::class,
        fn ($event) => $event->autoLoggedIn === false
    );
});

test('api register action extends abstract register action', function () {
    $action = new ApiRegisterAction(
        app(RateLimitService::class),
        app(\Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface::class),
        app(UserCreatorInterface::class),
        app(PasswordValidatorInterface::class)
    );

    expect($action)->toBeInstanceOf(\Aristonis\LaravelAuthentication\Actions\Register\AbstractRegisterAction::class);
});
