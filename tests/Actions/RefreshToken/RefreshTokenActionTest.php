<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\RefreshToken\ApiRefreshTokenAction;
use Aristonis\LaravelAuthentication\Actions\RefreshToken\RefreshTokenDto;
use Aristonis\LaravelAuthentication\Tests\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('refresh token succeeds with valid token', function () {
    // Arrange
    $user = User::factory()->create();
    $oldToken = $user->createToken('test-token')->plainTextToken;

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $oldToken
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->newToken)->not->toBeEmpty();
    expect($result->user->id)->toBe($user->id);
    expect($result->expiresAt)->not->toBeNull();
});

test('refresh token fails with invalid token', function () {
    // Arrange
    $user = User::factory()->create();

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: 'invalid-token'
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});

test('refresh token revokes old token when requested', function () {
    // Arrange
    $user = User::factory()->create();
    $oldToken = $user->createToken('test-token')->plainTextToken;

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $oldToken,
        revokeOld: true
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->newToken)->not->toBe($oldToken);
    expect($user->fresh()->tokens()->count())->toBe(1);
});

test('refresh token keeps old token when revokeOld is false', function () {
    // Arrange
    $user = User::factory()->create();
    $oldToken = $user->createToken('test-token')->plainTextToken;

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $oldToken,
        revokeOld: false
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->newToken)->not->toBe($oldToken);
    expect($user->fresh()->tokens()->count())->toBe(2);
});

test('refresh token fails with expired token', function () {
    // Arrange
    $user = User::factory()->create();
    $token = $user->createToken('test-token', ['*'], now()->subHour());

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $token->plainTextToken
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});

test('refresh token returns expiration time', function () {
    // Arrange
    $user = User::factory()->create();
    $oldToken = $user->createToken('test-token')->plainTextToken;

    config(['auth-package.sanctum.token_expiry_minutes' => 60]);

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $oldToken
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->expiresAt)->not->toBeNull();
    expect($result->getExpiresIn())->toBeGreaterThan(0);
    expect($result->getExpiresIn())->toBeLessThanOrEqual(3600); // 60 minutes in seconds
});

test('refresh token DTO converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: 'secret-token',
        revokeOld: true
    );

    // Act
    $array = $dto->toArray();

    // Assert - token should be masked
    expect($array)->toBe([
        'user_id' => $user->id,
        'old_token' => '***',
        'revoke_old' => true,
    ]);
});

test('refresh token result converts to array', function () {
    // Arrange
    $user = User::factory()->create();
    $expiresAt = now()->addHour();
    $result = new \Aristonis\LaravelAuthentication\Actions\RefreshToken\RefreshTokenResult(
        user: $user,
        newToken: 'new-token-123',
        expiresAt: $expiresAt
    );

    // Act
    $array = $result->toArray();

    // Assert
    expect($array['new_token'])->toBe('new-token-123');
    expect($array['expires_at'])->toBe($expiresAt->toIso8601String());
    expect($array['user_id'])->toBe($user->id);
    expect($array['expires_in'])->toBeGreaterThan(0);
});

test('mobile refresh token uses mobile-specific expiration', function () {
    // Arrange
    $user = User::factory()->create();
    $oldToken = $user->createToken('mobile-token')->plainTextToken;

    config(['auth-package.mobile.token_expiry_minutes' => 525600]); // 1 year

    $action = app(\Aristonis\LaravelAuthentication\Actions\RefreshToken\MobileRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $oldToken
    );

    // Act
    $result = $action($dto);

    // Assert
    expect($result->newToken)->not->toBeEmpty();
    expect($result->expiresAt)->not->toBeNull();
    expect($result->getExpiresIn())->toBeGreaterThan(31535000); // ~1 year in seconds (with margin)
});

test('refresh token fails for revoked token', function () {
    // Arrange
    $user = User::factory()->create();
    $tokenModel = $user->createToken('test-token');
    $plainTextToken = $tokenModel->plainTextToken;

    // Revoke the token
    $tokenModel->accessToken->delete();

    $action = app(ApiRefreshTokenAction::class);
    $dto = new RefreshTokenDto(
        user: $user,
        oldToken: $plainTextToken
    );

    // Act & Assert
    $this->expectException(\Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException::class);
    $action($dto);
});
