<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface for creating new users during registration.
 *
 * Extension Point: Implement this interface to customize user creation logic.
 *
 * Use Cases:
 * - Custom user model with additional fields
 * - Multi-tenant user creation
 * - LDAP/Active Directory user sync
 * - OAuth user registration flow
 *
 * @package Aristonis\LaravelAuthentication\Contracts
 */
interface UserCreatorInterface
{
    /**
     * Create a new user.
     *
     * @param array<string, mixed> $attributes User attributes (email, password, name, etc.)
     * @return Authenticatable The created user instance
     *
     * @throws \InvalidArgumentException If attributes are invalid
     * @throws \RuntimeException If user creation fails
     */
    public function create(array $attributes): Authenticatable;
}
