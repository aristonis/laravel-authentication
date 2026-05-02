<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Services\DefaultPasswordValidator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('valid password returns empty errors', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('Password1');

    expect($errors)->toBe([]);
});

test('password too short returns error', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => false,
        'require_lowercase' => false,
        'require_numbers' => false,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('short');

    expect($errors)->toContain('Password must be at least 8 characters');
});

test('password without uppercase returns error', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('password1');

    expect($errors)->toContain('Password must contain at least one uppercase letter');
});

test('password without lowercase returns error', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('PASSWORD1');

    expect($errors)->toContain('Password must contain at least one lowercase letter');
});

test('password without number returns error', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('Password');

    expect($errors)->toContain('Password must contain at least one number');
});

test('password without symbol returns error when required', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('Password1');

    expect($errors)->toContain('Password must contain at least one symbol');
});

test('password with symbol passes when required', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('Password @1');

    expect($errors)->toBe([]);
});

test('multiple validation errors are returned', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('short');

    expect($errors)->toHaveCount(3);
    expect($errors)->toContain('Password must be at least 8 characters');
    expect($errors)->toContain('Password must contain at least one uppercase letter');
    expect($errors)->toContain('Password must contain at least one number');
});

test('custom min length is respected', function () {
    config(['auth-package.password' => [
        'min_length' => 12,
        'require_uppercase' => false,
        'require_lowercase' => false,
        'require_numbers' => false,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('shortpass');

    expect($errors)->toContain('Password must be at least 12 characters');
});

test('uppercase requirement can be disabled', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => false,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('password1');

    expect($errors)->not->toContain('Password must contain at least one uppercase letter');
});

test('lowercase requirement can be disabled', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => false,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('PASSWORD1');

    expect($errors)->not->toContain('Password must contain at least one lowercase letter');
});

test('number requirement can be disabled', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => false,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('Password');

    expect($errors)->not->toContain('Password must contain at least one number');
});

test('symbol requirement can be disabled', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('Password1');

    expect($errors)->not->toContain('Password must contain at least one symbol');
});

test('default config is used when not set', function () {
    // Don't set config, use defaults
    $validator = new DefaultPasswordValidator();
    $errors = $validator->validate('weak');

    expect($errors)->not->toBeEmpty();
});
