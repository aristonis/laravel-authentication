<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Contracts;

/**
 * Interface for validating passwords during registration.
 *
 * Extension Point: Implement this interface to customize password validation rules.
 *
 * Use Cases:
 * - Organization-specific password policies
 * - Integration with HaveIBeenPwned API
 * - Custom complexity requirements
 * - Dictionary word checks
 *
 * @package Aristonis\LaravelAuthentication\Contracts
 */
interface PasswordValidatorInterface
{
    /**
     * Validate a password against configured rules.
     *
     * @param string $password The password to validate
     * @return array<string> Array of error messages (empty if valid)
     *
     * @example
     * // Invalid password returns:
     * [
     *     'Password must be at least 8 characters',
     *     'Password must contain at least one uppercase letter'
     * ]
     *
     * // Valid password returns:
     * []
     */
    public function validate(string $password): array;
}
