<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\ChangePassword\ApiChangePasswordAction;
use Aristonis\LaravelAuthentication\Actions\ChangePassword\ChangePasswordDto;
use Aristonis\LaravelAuthentication\Tests\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('change password succeeds with correct old password', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->user->id)->toBe($user->id);
});

test('change password fails with incorrect old password', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'WrongPassword!',
        newPassword: 'NewPassword456!'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidCredentialsException::class);
    $action($dto);
});

test('change password fails with weak new password', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'weak' // Too weak
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\ValidationException::class);
    $action($dto);
});

test('change password updates password in database', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);
    $oldPassword = $user->password;

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $action($dto);

    // Assert
    $user->refresh();
    expect(Hash::check('NewPassword456!', $user->password))->toBeTrue();
    expect($user->password)->not->toBe($oldPassword);
});

test('change password dispatches PasswordChangedEvent', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $action($dto);

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\ChangePassword\Events\PasswordChangedEvent::class,
        function ($event) use ($user) {
            return $event->user->id === $user->id;
        }
    );
});

test('API change password generates new token when configured', function () {
    // Arrange
    config(['auth-package.security.revoke_tokens_on_password_change' => true]);

    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);
    $user->createToken('old-token');

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->hasNewToken())->toBeTrue();
    expect($result->newToken)->not->toBeEmpty();
});

test('API change password does not generate token by default', function () {
    // Arrange
    config(['auth-package.security.revoke_tokens_on_password_change' => false]);

    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);

    $action = app(ApiChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->hasNewToken())->toBeFalse();
});

test('change password DTO converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $array = $dto->toArray();

    // Assert - passwords should be masked
    expect($array)->toBe([
        'user_id' => $user->id,
        'old_password' => '***',
        'new_password' => '***',
    ]);
});

test('change password result converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $result = new \Aristonis\LaravelAuthentication\Actions\ChangePassword\ChangePasswordResult(
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

test('mobile change password generates new token with expiration', function () {
    // Arrange
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);

    $action = app(\Aristonis\LaravelAuthentication\Actions\ChangePassword\MobileChangePasswordAction::class);
    $dto = new ChangePasswordDto(
        user: $user,
        oldPassword: 'OldPassword123!',
        newPassword: 'NewPassword456!'
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->hasNewToken())->toBeTrue();
});
