<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface TokenServiceInterface
{
    /**
     * Create a new token for the user.
     */
    public function createToken(
        Authenticatable $user,
        ?string $name = null,
        ?array $abilities = null,
        ?\DateTimeInterface $expiresAt = null
    ): string;

    /**
     * Revoke a token.
     */
    public function revokeToken(string $token): bool;

    /**
     * Find a token by its raw token string.
     */
    public function findToken(string $token): ?\Laravel\Sanctum\PersonalAccessToken;
}
