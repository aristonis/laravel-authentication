<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\Login\ApiLoginAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;
use Aristonis\LaravelAuthentication\Exceptions\InvalidCredentialsException;
use Aristonis\LaravelAuthentication\Tests\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login succeeds with valid credentials', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'password123'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->user->id)->toBe($user->id);
    expect($result->meta['token'])->not->toBeEmpty();
    expect($result->meta['token_type'])->toBe('Bearer');
});

test('login fails with invalid credentials', function () {
    // Arrange
    $user = User::factory()->create();

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'wrongpassword'
    );

    // Act & Assert
    $this->expectException(InvalidCredentialsException::class);
    $action($dto);
});

test('login fails when user not found', function () {
    // Arrange
    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: 'nonexistent@example.com',
        password: 'password123'
    );

    // Act & Assert
    $this->expectException(InvalidCredentialsException::class);
    $action($dto);
});

test('login creates token with correct abilities', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    config(['auth-package.sanctum.abilities' => ['read', 'write']]);

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'password123'
    );

    // Act
    $result = $action($dto);

    // Assert
    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($result->meta['token']);
    expect($token->abilities)->toBe(['read', 'write']);
});

test('login dispatches LoginSuccessEvent', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'password123'
    );

    // Act
    $action($dto);

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\Login\Events\LoginSuccessEvent::class,
        function ($event) use ($user) {
            return $event->user->id === $user->id;
        }
    );
});

test('login dispatches LoginFailedEvent on invalid credentials', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create();

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'wrongpassword'
    );

    // Act & Assert
    try {
        $action($dto);
    } catch (InvalidCredentialsException) {
        // Expected
    }

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\Login\Events\LoginFailedEvent::class,
        function ($event) use ($user) {
            return $event->email === $user->email;
        }
    );
});

test('login records failed attempt on failure', function () {
    // Arrange
    $user = User::factory()->create();

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'wrongpassword'
    );

    // Act & Assert
    try {
        $action($dto);
    } catch (InvalidCredentialsException) {
        // Expected
    }

    // Verify rate limit counter incremented
    expect(Cache::get('rate_limit:login:' . md5($user->email)))->toBe(1);
});

test('login fails when rate limited', function () {
    // Arrange
    $user = User::factory()->create();

    // Simulate rate limit exceeded
    Cache::set('rate_limit:login:' . md5($user->email), 6, 60);

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'password123'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException::class);
    $action($dto);
});

test('login requires two factor when enabled', function () {
    // Arrange
    $user = User::factory()->withTwoFactor()->create([
        'password' => Hash::make('password123'),
    ]);

    $action = app(ApiLoginAction::class);
    $dto = new LoginUserDto(
        identifier: $user->email,
        password: 'password123'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\TwoFactorRequiredException::class);
    $action($dto);
});
