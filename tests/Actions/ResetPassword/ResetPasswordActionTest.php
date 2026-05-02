<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\ResetPassword\ApiResetPasswordAction;
use Aristonis\LaravelAuthentication\Actions\ResetPassword\ResetPasswordDto;
use Aristonis\LaravelAuthentication\Tests\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('reset password succeeds with valid token', function () {
    // Arrange
    $user = User::factory()->create();

    // Create reset token in cache
    $rawToken = Str::random(60);
    \Illuminate\Support\Facades\Cache::put(
        'password_reset:' . $user->email,
        $rawToken,
        now()->addMinutes(60)
    );

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: $rawToken,
        email: $user->email,
        newPassword: 'NewPassword123!'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->user->id)->toBe($user->id);
    expect($result->hasNewToken())->toBeTrue();
});

test('reset password fails with invalid token', function () {
    // Arrange
    $user = User::factory()->create();

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: 'invalid-token',
        email: $user->email,
        newPassword: 'NewPassword123!'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});

test('reset password fails with expired token', function () {
    // Arrange
    $user = User::factory()->create();

    // Create expired reset token in cache (already expired)
    $rawToken = Str::random(60);
    \Illuminate\Support\Facades\Cache::put(
        'password_reset:' . $user->email,
        $rawToken,
        now()->subMinutes(5) // Already expired
    );

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: $rawToken,
        email: $user->email,
        newPassword: 'NewPassword123!'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});

test('reset password fails with weak password', function () {
    // Arrange
    $user = User::factory()->create();

    // Create reset token in cache
    $rawToken = Str::random(60);
    \Illuminate\Support\Facades\Cache::put(
        'password_reset:' . $user->email,
        $rawToken,
        now()->addMinutes(60)
    );

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: $rawToken,
        email: $user->email,
        newPassword: 'weak' // Too weak
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\ValidationException::class);
    $action($dto);
});

test('reset password updates user password', function () {
    // Arrange
    $user = User::factory()->create();
    $oldPassword = $user->password;

    // Create reset token in cache
    $rawToken = Str::random(60);
    \Illuminate\Support\Facades\Cache::put(
        'password_reset:' . $user->email,
        $rawToken,
        now()->addMinutes(60)
    );

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: $rawToken,
        email: $user->email,
        newPassword: 'NewPassword123!'
    );

    // Act
    $action($dto);

    // Assert
    $user->refresh();
    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();
    expect($user->password)->not->toBe($oldPassword);
});

test('reset password invalidates used token', function () {
    // Arrange
    $user = User::factory()->create();

    // Create reset token in cache
    $rawToken = Str::random(60);
    \Illuminate\Support\Facades\Cache::put(
        'password_reset:' . $user->email,
        $rawToken,
        now()->addMinutes(60)
    );

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: $rawToken,
        email: $user->email,
        newPassword: 'NewPassword123!'
    );

    // Act
    $action($dto);

    // Assert - token should be deleted from cache
    expect(\Illuminate\Support\Facades\Cache::get('password_reset:' . $user->email))->toBeNull();
});

test('reset password dispatches PasswordResetEvent', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create();

    // Create reset token in cache
    $rawToken = Str::random(60);
    \Illuminate\Support\Facades\Cache::put(
        'password_reset:' . $user->email,
        $rawToken,
        now()->addMinutes(60)
    );

    $action = app(ApiResetPasswordAction::class);
    $dto = new ResetPasswordDto(
        token: $rawToken,
        email: $user->email,
        newPassword: 'NewPassword123!'
    );

    // Act
    $action($dto);

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\ResetPassword\Events\PasswordResetEvent::class,
        function ($event) use ($user) {
            return $event->user->id === $user->id;
        }
    );
});

test('reset password DTO converts to array', function () {
    // Arrange
    $dto = new ResetPasswordDto(
        token: 'token123',
        email: 'test@example.com',
        newPassword: 'NewPassword123!'
    );

    // Act
    $array = $dto->toArray();

    // Assert
    expect($array)->toBe([
        'token' => 'token123',
        'email' => 'test@example.com',
        'new_password' => 'NewPassword123!',
    ]);
});

test('reset password result converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $result = new \Aristonis\LaravelAuthentication\Actions\ResetPassword\ResetPasswordResult(
        success: true,
        user: $user,
        newToken: 'new-token-123',
        message: 'Success'
    );

    // Act
    $array = $result->toArray();

    // Assert
    expect($array)->toBe([
        'success' => true,
        'new_token' => 'new-token-123',
        'message' => 'Success',
    ]);
});
