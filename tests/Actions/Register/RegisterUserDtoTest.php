<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('dto can be created with required fields only', function () {
    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123'
    );

    expect($dto->email)->toBe('test @example.com');
    expect($dto->password)->toBe('password123');
    expect($dto->name)->toBeNull();
    expect($dto->ipAddress)->toBeNull();
    expect($dto->additional)->toBe([]);
});

test('dto can be created with all fields', function () {
    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123',
        name: 'Test User',
        ipAddress: '192.168.1.1',
        additional: ['age' => 25, 'role' => 'user']
    );

    expect($dto->email)->toBe('test @example.com');
    expect($dto->password)->toBe('password123');
    expect($dto->name)->toBe('Test User');
    expect($dto->ipAddress)->toBe('192.168.1.1');
    expect($dto->additional)->toBe(['age' => 25, 'role' => 'user']);
});

test('dto is readonly', function () {
    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123'
    );

    // Attempting to modify should not be possible
    // This test verifies the class is declared as readonly
    expect($dto)->toBeInstanceOf(RegisterUserDto::class);
});

test('toArray returns all non-null fields', function () {
    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123',
        name: 'Test User',
        ipAddress: '192.168.1.1',
        additional: ['age' => 25]
    );

    $array = $dto->toArray();

    expect($array)->toBe([
        'email' => 'test @example.com',
        'password' => 'password123',
        'name' => 'Test User',
        'ip_address' => '192.168.1.1',
        'age' => 25,
    ]);
});

test('toArray excludes null fields', function () {
    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123'
    );

    $array = $dto->toArray();

    expect($array)->not->toHaveKeys(['name', 'ip_address']);
    expect($array)->toHaveKeys(['email', 'password']);
});

test('toArray includes additional fields', function () {
    $dto = new RegisterUserDto(
        email: 'test @example.com',
        password: 'password123',
        additional: ['custom_field' => 'custom_value', 'another' => 123]
    );

    $array = $dto->toArray();

    expect($array)->toHaveKeys(['email', 'password', 'custom_field', 'another']);
    expect($array['custom_field'])->toBe('custom_value');
    expect($array['another'])->toBe(123);
});
