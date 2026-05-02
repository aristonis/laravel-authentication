<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserResult;
use Aristonis\LaravelAuthentication\Tests\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('result can be created with user only', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user
    );

    expect($result->user)->toBe($user);
    expect($result->meta)->toBe([]);
    expect($result->isLoggedIn())->toBeFalse();
});

test('result can be created with meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['token' => 'test-token', 'token_type' => 'Bearer']
    );

    expect($result->user)->toBe($user);
    expect($result->meta)->toBe(['token' => 'test-token', 'token_type' => 'Bearer']);
});

test('result can be created with loggedIn flag', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['token' => 'test-token'],
        loggedIn: true
    );

    expect($result->user)->toBe($user);
    expect($result->isLoggedIn())->toBeTrue();
});

test('getToken returns token from meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['token' => 'test-token-123']
    );

    expect($result->getToken())->toBe('test-token-123');
});

test('getToken returns null when token not in meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['other' => 'value']
    );

    expect($result->getToken())->toBeNull();
});

test('getTokenType returns token_type from meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['token_type' => 'Bearer']
    );

    expect($result->getTokenType())->toBe('Bearer');
});

test('getTokenType returns null when not in meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user
    );

    expect($result->getTokenType())->toBeNull();
});

test('getTokenExpiration returns expires_at from meta', function () {
    $user = User::factory()->make();
    $expiresAt = now()->addDays(30)->toIso8601String();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['expires_at' => $expiresAt]
    );

    expect($result->getTokenExpiration())->toBe($expiresAt);
});

test('getTokenExpiration returns null when not in meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user
    );

    expect($result->getTokenExpiration())->toBeNull();
});

test('result is immutable', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: ['token' => 'test-token'],
        loggedIn: true
    );

    // Verify properties are readonly
    expect($result->user)->toBe($user);
    expect($result->meta)->toBe(['token' => 'test-token']);
    expect($result->isLoggedIn())->toBeTrue();
});

test('result with full api registration meta', function () {
    $user = User::factory()->make();

    $result = new RegisterUserResult(
        user: $user,
        meta: [
            'token' => 'plain-text-token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ],
        loggedIn: true
    );

    expect($result->getToken())->toBe('plain-text-token');
    expect($result->getTokenType())->toBe('Bearer');
    expect($result->getTokenExpiration())->not->toBeNull();
    expect($result->isLoggedIn())->toBeTrue();
});

test('result with mobile registration meta', function () {
    $user = User::factory()->make();
    $expiresAt = now()->addDays(30);

    $result = new RegisterUserResult(
        user: $user,
        meta: [
            'access_token' => 'mobile-token',
            'token_type' => 'Bearer',
            'expires_in' => 30 * 24 * 60 * 60,
            'expires_at' => $expiresAt->toIso8601String(),
        ],
        loggedIn: true
    );

    expect($result->meta['access_token'])->toBe('mobile-token');
    expect($result->meta['expires_in'])->toBe(30 * 24 * 60 * 60);
    expect($result->getTokenExpiration())->toBe($expiresAt->toIso8601String());
});
