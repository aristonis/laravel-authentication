<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Rules;

use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Laravel validation rule for password complexity.
 *
 * Integrates with PasswordValidatorInterface to validate
 * password strength during registration.
 *
 * Usage:
 * ```php
 * Validator::make($data, [
 *     'password' => ['required', new PasswordRule()],
 * ]);
 * ```
 *
 * @package Aristonis\LaravelAuthentication\Rules
 */
class PasswordRule implements ValidationRule, DataAwareRule
{
    /**
     * The data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string): void $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The password must be a string.');

            return;
        }

        $validator = app(PasswordValidatorInterface::class);
        $errors = $validator->validate($value);

        foreach ($errors as $error) {
            $fail($error);
        }
    }
}
