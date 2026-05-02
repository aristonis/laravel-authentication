<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Services;

use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Default implementation of UserCreatorInterface.
 *
 * Creates users using the configured Eloquent model.
 *
 * Extension Point: Replace this class via config or implement
 * UserCreatorInterface for custom user creation logic.
 *
 * @package Aristonis\LaravelAuthentication\Services
 */
class DefaultUserCreator implements UserCreatorInterface
{
    /**
     * Create a new user using the configured model.
     *
     * @param array<string, mixed> $attributes User attributes
     * @return Authenticatable The created user
     *
     * @throws \RuntimeException If user creation fails
     */
    public function create(array $attributes): Authenticatable
    {
        $modelClass = config('auth.providers.users.model');

        /** @var Authenticatable $user */
        $user = $modelClass::create($attributes);

        return $user;
    }
}
