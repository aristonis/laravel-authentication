<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Services;

use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;

/**
 * Default implementation of PasswordValidatorInterface.
 *
 * Validates passwords against configured complexity rules:
 * - Minimum length
 * - Require uppercase letters
 * - Require lowercase letters
 * - Require numbers
 * - Require symbols
 *
 * Extension Point: Replace this class via config or implement
 * PasswordValidatorInterface for custom password validation.
 *
 * @package Aristonis\LaravelAuthentication\Services
 */
class DefaultPasswordValidator implements PasswordValidatorInterface
{
    /**
     * Validate a password against configured rules.
     *
     * @param string $password The password to validate
     * @return array<string> Array of error messages (empty if valid)
     */
    public function validate(string $password): array
    {
        $errors = [];
        $config = config('auth-package.password', []);

        // Minimum length check
        $minLength = $config['min_length'] ?? 8;
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
        }

        // Uppercase letter check
        if (!empty($config['require_uppercase']) && !$this->containsUppercase($password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Lowercase letter check
        if (!empty($config['require_lowercase']) && !$this->containsLowercase($password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Number check
        if (!empty($config['require_numbers']) && !$this->containsNumber($password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Symbol check
        if (!empty($config['require_symbols']) && !$this->containsSymbol($password)) {
            $errors[] = 'Password must contain at least one symbol';
        }

        return $errors;
    }

    /**
     * Check if password contains uppercase letter.
     */
    private function containsUppercase(string $password): bool
    {
        return preg_match('/[A-Z]/', $password) === 1;
    }

    /**
     * Check if password contains lowercase letter.
     */
    private function containsLowercase(string $password): bool
    {
        return preg_match('/[a-z]/', $password) === 1;
    }

    /**
     * Check if password contains number.
     */
    private function containsNumber(string $password): bool
    {
        return preg_match('/[0-9]/', $password) === 1;
    }

    /**
     * Check if password contains symbol (non-alphanumeric).
     */
    private function containsSymbol(string $password): bool
    {
        return preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}
