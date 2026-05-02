<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\ForgotPassword\ApiForgotPasswordAction;
use Aristonis\LaravelAuthentication\Actions\ForgotPassword\ForgotPasswordDto;
use Aristonis\LaravelAuthentication\Tests\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('forgot password succeeds for existing user', function () {
    // Arrange
    $user = User::factory()->create();

    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: $user->email,
        ipAddress: '127.0.0.1'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->user->id)->toBe($user->id);
    expect($result->user->email)->toBe($user->email);
});

test('forgot password returns generic message for non-existent user', function () {
    // Arrange
    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: 'nonexistent@example.com',
        ipAddress: '127.0.0.1'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->user)->toBeNull();
});

test('forgot password dispatches PasswordResetLinkSentEvent', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create();

    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: $user->email,
        ipAddress: '127.0.0.1'
    );

    // Act
    $action($dto);

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\ForgotPassword\Events\PasswordResetLinkSentEvent::class,
        function ($event) use ($user) {
            return $event->user->id === $user->id;
        }
    );
});

test('forgot password is rate limited', function () {
    // Arrange
    $user = User::factory()->create();

    // Simulate rate limit exceeded
    \Illuminate\Support\Facades\Cache::set(
        'rate_limit:forgot_password:' . md5($user->email),
        6,
        60
    );

    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: $user->email,
        ipAddress: '127.0.0.1'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException::class);
    $action($dto);
});

test('forgot password clears rate limit on success', function () {
    // Arrange
    $user = User::factory()->create();

    // Add one failed attempt
    \Illuminate\Support\Facades\Cache::set(
        'rate_limit:forgot_password:' . md5($user->email),
        1,
        60
    );

    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: $user->email,
        ipAddress: '127.0.0.1'
    );

    // Act
    $action($dto);

    // Assert - rate limit should be cleared
    expect(\Illuminate\Support\Facades\Cache::get(
        'rate_limit:forgot_password:' . md5($user->email)
    ))->toBeNull();
});

test('API forgot password returns token when configured', function () {
    // Arrange
    config(['auth-package.api.return_reset_token' => true]);

    $user = User::factory()->create();

    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: $user->email,
        ipAddress: '127.0.0.1'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->hasResetToken())->toBeTrue();
    expect($result->resetToken)->not->toBeEmpty();
});

test('API forgot password does not return token by default', function () {
    // Arrange
    config(['auth-package.api.return_reset_token' => false]);

    $user = User::factory()->create();

    $action = app(ApiForgotPasswordAction::class);
    $dto = new ForgotPasswordDto(
        email: $user->email,
        ipAddress: '127.0.0.1'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->hasResetToken())->toBeFalse();
    expect($result->resetToken)->toBeNull();
});

test('forgot password DTO converts to array', function () {
    // Arrange
    $dto = new ForgotPasswordDto(
        email: 'test@example.com',
        ipAddress: '192.168.1.1'
    );

    // Act
    $array = $dto->toArray();

    // Assert
    expect($array)->toBe([
        'email' => 'test@example.com',
        'ip_address' => '192.168.1.1',
    ]);
});

test('forgot password result converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $result = new \Aristonis\LaravelAuthentication\Actions\ForgotPassword\ForgotPasswordResult(
        success: true,
        resetToken: 'token123',
        message: 'Success',
        user: $user
    );

    // Act
    $array = $result->toArray();

    // Assert
    expect($array)->toBe([
        'success' => true,
        'reset_token' => 'token123',
        'message' => 'Success',
    ]);
});
