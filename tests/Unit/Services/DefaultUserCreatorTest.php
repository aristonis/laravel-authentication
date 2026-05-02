<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Services\DefaultUserCreator;
use Aristonis\LaravelAuthentication\Tests\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('create user with email and password', function () {
    $creator = new DefaultUserCreator();

    $user = $creator->create([
        'email' => 'test @example.com',
        'password' => 'hashed-password-123',
        'name' => 'Test User',
    ]);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('test @example.com');
    // Password is hashed by the model's 'hashed' cast
    expect($user->password)->not->toBe('hashed-password-123');
    expect(\Illuminate\Support\Facades\Hash::check('hashed-password-123', $user->password))->toBeTrue();
});

test('create user with additional attributes', function () {
    $creator = new DefaultUserCreator();

    $user = $creator->create([
        'email' => 'test @example.com',
        'password' => 'hashed-password-123',
        'name' => 'Test User',
    ]);

    expect($user->email)->toBe('test @example.com');
    expect($user->name)->toBe('Test User');
});

test('create user returns Authenticatable', function () {
    $creator = new DefaultUserCreator();

    $user = $creator->create([
        'email' => 'test @example.com',
        'password' => 'hashed-password-123',
        'name' => 'Test User',
    ]);

    expect($user)->toBeInstanceOf(\Illuminate\Contracts\Auth\Authenticatable::class);
});

test('create user uses configured model', function () {
    config(['auth.providers.users.model' => User::class]);

    $creator = new DefaultUserCreator();

    $user = $creator->create([
        'email' => 'test @example.com',
        'password' => 'hashed-password-123',
        'name' => 'Test User',
    ]);

    expect($user)->toBeInstanceOf(User::class);
});

test('create user with multiple attributes', function () {
    $creator = new DefaultUserCreator();

    $user = $creator->create([
        'email' => 'test @example.com',
        'password' => 'hashed-password-123',
        'name' => 'Test User',
        'email_verified_at' => now(),
    ]);

    expect($user->email)->toBe('test @example.com');
    expect($user->name)->toBe('Test User');
    expect($user->email_verified_at)->not->toBeNull();
});

test('create user with unique email', function () {
    $creator = new DefaultUserCreator();

    $user1 = $creator->create([
        'email' => 'unique1 @example.com',
        'password' => 'hashed-password-123',
        'name' => 'User One',
    ]);

    $user2 = $creator->create([
        'email' => 'unique2 @example.com',
        'password' => 'hashed-password-123',
        'name' => 'User Two',
    ]);

    expect($user1->email)->toBe('unique1 @example.com');
    expect($user2->email)->toBe('unique2 @example.com');
    expect($user1->id)->not->toBe($user2->id);
});

test('create user throws on duplicate email', function () {
    // Create first user
    User::factory()->create(['email' => 'duplicate @example.com']);

    $creator = new DefaultUserCreator();

    // Database unique constraint should handle this
    expect(fn () => $creator->create([
        'email' => 'duplicate @example.com',
        'password' => 'hashed-password-123',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
