<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Rules\PasswordRule;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Services\DefaultPasswordValidator;
use Illuminate\Support\Facades\Validator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('password rule passes for valid password', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = Validator::make(
        ['password' => 'ValidPass1'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeFalse();
});

test('password rule fails for short password', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => false,
        'require_lowercase' => false,
        'require_numbers' => false,
        'require_symbols' => false,
    ]]);

    $validator = Validator::make(
        ['password' => 'short'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toContain('at least 8 characters');
});

test('password rule fails for password without uppercase', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = Validator::make(
        ['password' => 'lowercase1'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toContain('uppercase');
});

test('password rule fails for password without number', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = Validator::make(
        ['password' => 'NoNumbers'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toContain('number');
});

test('password rule fails for multiple violations', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
    ]]);

    $validator = Validator::make(
        ['password' => 'short'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->count())->toBeGreaterThan(1);
});

test('password rule uses PasswordValidatorInterface from container', function () {
    // Bind a custom validator
    $customValidator = new class implements PasswordValidatorInterface {
        public function validate(string $password): array {
            return ['Custom validation error'];
        }
    };

    app()->bind(PasswordValidatorInterface::class, fn () => $customValidator);

    $validator = Validator::make(
        ['password' => 'any'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toBe('Custom validation error');
});

test('password rule passes with symbol when required', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ]]);

    $validator = Validator::make(
        ['password' => 'Valid @Pass1'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeFalse();
});

test('password rule fails without symbol when required', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ]]);

    $validator = Validator::make(
        ['password' => 'ValidPass1'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toContain('symbol');
});

test('password rule handles non-string value', function () {
    config(['auth-package.password' => [
        'min_length' => 8,
    ]]);

    $validator = Validator::make(
        ['password' => 12345],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('password'))->toContain('must be a string');
});

test('password rule with default config', function () {
    // Use default config
    $validator = Validator::make(
        ['password' => 'ValidPass1'],
        ['password' => ['required', new PasswordRule()]]
    );

    expect($validator->fails())->toBeFalse();
});

test('password rule implements ValidationRule interface', function () {
    $rule = new PasswordRule();

    expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
});

test('password rule implements DataAwareRule interface', function () {
    $rule = new PasswordRule();

    expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\DataAwareRule::class);
});

test('setData returns self for fluent interface', function () {
    $rule = new PasswordRule();
    $result = $rule->setData(['password' => 'test']);

    expect($result)->toBe($rule);
});
