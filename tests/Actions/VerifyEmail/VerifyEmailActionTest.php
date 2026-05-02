<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\VerifyEmail\ApiVerifyEmailAction;
use Aristonis\LaravelAuthentication\Actions\VerifyEmail\VerifyEmailDto;
use Aristonis\LaravelAuthentication\Tests\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('verify email succeeds with valid token', function () {
    // Arrange
    $user = User::factory()->create(['email_verified_at' => null]);

    // Generate valid signature
    $token = hash('sha256', config('app.key') . ':' . $user->id . ':' . $user->email);

    $action = app(ApiVerifyEmailAction::class);
    $dto = new VerifyEmailDto(
        userId: $user->id,
        token: $token,
        email: $user->email
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->wasNewlyVerified())->toBeTrue();
    expect($result->user->id)->toBe($user->id);
});

test('verify email fails with invalid token', function () {
    // Arrange
    $user = User::factory()->create(['email_verified_at' => null]);

    $action = app(ApiVerifyEmailAction::class);
    $dto = new VerifyEmailDto(
        userId: $user->id,
        token: 'invalid-token',
        email: $user->email
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});

test('verify email handles already verified email', function () {
    // Arrange
    $user = User::factory()->create(['email_verified_at' => now()]);

    $action = app(ApiVerifyEmailAction::class);
    $dto = new VerifyEmailDto(
        userId: $user->id,
        token: 'any-token',
        email: $user->email
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($result->alreadyVerified)->toBeTrue();
    expect($result->wasNewlyVerified())->toBeFalse();
});

test('verify email fails for non-existent user', function () {
    // Arrange
    $action = app(ApiVerifyEmailAction::class);
    $dto = new VerifyEmailDto(
        userId: 99999,
        token: 'any-token',
        email: 'nonexistent@example.com'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});

test('verify email updates email_verified_at', function () {
    // Arrange
    $user = User::factory()->create(['email_verified_at' => null]);

    // Generate valid signature
    $token = hash('sha256', config('app.key') . ':' . $user->id . ':' . $user->email);

    $action = app(ApiVerifyEmailAction::class);
    $dto = new VerifyEmailDto(
        userId: $user->id,
        token: $token,
        email: $user->email
    );

    // Act
    $action($dto);

    // Assert
    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('verify email dispatches EmailVerifiedEvent', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    // Generate valid signature
    $token = hash('sha256', config('app.key') . ':' . $user->id . ':' . $user->email);

    $action = app(ApiVerifyEmailAction::class);
    $dto = new VerifyEmailDto(
        userId: $user->id,
        token: $token,
        email: $user->email
    );

    // Act
    $action($dto);

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\VerifyEmail\Events\EmailVerifiedEvent::class,
        function ($event) use ($user) {
            return $event->user->id === $user->id;
        }
    );
});

test('verify email DTO converts to array', function () {
    // Arrange
    $dto = new VerifyEmailDto(
        userId: 123,
        token: 'token123',
        email: 'test@example.com'
    );

    // Act
    $array = $dto->toArray();

    // Assert
    expect($array)->toBe([
        'user_id' => 123,
        'token' => 'token123',
        'email' => 'test@example.com',
    ]);
});

test('verify email result converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $result = new \Aristonis\LaravelAuthentication\Actions\VerifyEmail\VerifyEmailResult(
        success: true,
        verified: true,
        alreadyVerified: false,
        message: 'Success',
        user: $user
    );

    // Act
    $array = $result->toArray();

    // Assert
    expect($array)->toBe([
        'success' => true,
        'verified' => true,
        'already_verified' => false,
        'message' => 'Success',
    ]);
});

test('verify email result wasNewlyVerified helper', function () {
    // Arrange - newly verified
    $user = User::factory()->create();
    $newResult = new \Aristonis\LaravelAuthentication\Actions\VerifyEmail\VerifyEmailResult(
        success: true,
        verified: true,
        alreadyVerified: false,
        message: 'Success',
        user: $user
    );

    // Arrange - already verified
    $existingResult = new \Aristonis\LaravelAuthentication\Actions\VerifyEmail\VerifyEmailResult(
        success: true,
        verified: false,
        alreadyVerified: true,
        message: 'Already verified',
        user: $user
    );

    // Assert
    expect($newResult->wasNewlyVerified())->toBeTrue();
    expect($existingResult->wasNewlyVerified())->toBeFalse();
});
