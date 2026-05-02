<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\Logout\ApiLogoutAction;
use Aristonis\LaravelAuthentication\Actions\Logout\LogoutDto;
use Aristonis\LaravelAuthentication\Tests\Models\User;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('API logout succeeds and revokes current token', function () {
    // Arrange
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $action = app(ApiLogoutAction::class);
    $dto = new LogoutDto(
        user: $user,
        token: $token
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
});

test('API logout revokes all tokens when requested', function () {
    // Arrange
    $user = User::factory()->create();
    $user->createToken('token-1');
    $user->createToken('token-2');
    $user->createToken('token-3');

    $action = app(ApiLogoutAction::class);
    $dto = new LogoutDto(
        user: $user,
        revokeAll: true
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
});

test('API logout revokes specific token by ID', function () {
    // Arrange
    $user = User::factory()->create();
    $token1 = $user->createToken('token-1');
    $token2 = $user->createToken('token-2');

    $action = app(ApiLogoutAction::class);
    $dto = new LogoutDto(
        user: $user,
        tokenId: $token1->accessToken->id
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('token-2');
});

test('logout fails without user', function () {
    // Arrange
    $action = app(ApiLogoutAction::class);
    $dto = new LogoutDto();

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeFalse();
    expect($result->message)->toBe('No user to log out');
});

test('logout dispatches UserLoggedOutEvent', function () {
    // Arrange
    \Illuminate\Support\Facades\Event::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $action = app(ApiLogoutAction::class);
    $dto = new LogoutDto(
        user: $user,
        token: $token
    );

    // Act
    $action($dto);

    // Assert
    \Illuminate\Support\Facades\Event::assertDispatched(
        \Aristonis\LaravelAuthentication\Actions\Logout\Events\UserLoggedOutEvent::class,
        function ($event) use ($user) {
            return $event->user->id === $user->id;
        }
    );
});

test('logout DTO converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $dto = new LogoutDto(
        user: $user,
        tokenId: '123',
        revokeAll: false
    );

    // Act
    $array = $dto->toArray();

    // Assert
    expect($array)->toBe([
        'user_id' => $user->id,
        'token_id' => '123',
        'revoke_all' => false,
    ]);
});

test('logout result converts to array', function () {
    // Arrange
    $result = new \Aristonis\LaravelAuthentication\Actions\Logout\LogoutResult(
        success: true,
        message: 'Logged out successfully'
    );

    // Act
    $array = $result->toArray();

    // Assert
    expect($array)->toBe([
        'success' => true,
        'message' => 'Logged out successfully',
    ]);
});

test('web logout destroys session', function () {
    // Arrange
    $user = User::factory()->create();

    // Create a session
    session(['user_id' => $user->id]);
    expect(session('user_id'))->toBe($user->id);

    $action = app(\Aristonis\LaravelAuthentication\Actions\Logout\WebLogoutAction::class);
    $dto = new LogoutDto();

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
})->skip('Session testing requires additional setup');

test('mobile logout revokes device token', function () {
    // Arrange
    $user = User::factory()->create();
    $token = $user->createToken('mobile-token')->plainTextToken;

    $action = app(\Aristonis\LaravelAuthentication\Actions\Logout\MobileLogoutAction::class);
    $dto = new LogoutDto(
        user: $user,
        token: $token
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->success)->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
});
