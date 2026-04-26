<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Contracts;

interface UserIdentifierInterface
{
    /**
     * Find user by identifier value.
     *
     * @param string $identifier The identifier value (email, username, phone, etc.)
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function findUser(string $identifier): ?\Illuminate\Contracts\Auth\Authenticatable;

    /**
     * Get a unique key for rate limiting based on identifier.
     *
     * @param string $identifier The identifier value
     * @return string
     */
    public function getRateLimitKey(string $identifier): string;
}
