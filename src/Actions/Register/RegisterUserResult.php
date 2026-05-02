<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Registration result - EXTENDABLE by users.
 *
 * Contains user, metadata (token, etc.), and login status.
 * Each registration type can add its own meta fields.
 *
 * @property Authenticatable $user The registered user
 * @property array $meta Additional data (token, token_type, expires_at, etc.)
 * @property bool $loggedIn Whether user was automatically logged in
 *
 * @package Aristonis\LaravelAuthentication\Actions\Register
 *
 * @example
 * // API: meta = ['token' => '...', 'token_type' => 'Bearer']
 * // Web: meta = ['session_id' => '...', 'redirect_url' => '...']
 * // Mobile: meta = ['access_token' => '...', 'expires_in' => 3600, 'expires_at' => '...']
 */
class RegisterUserResult
{
    /**
     * @param Authenticatable $user The registered user
     * @param array<string, mixed> $meta Additional metadata
     * @param bool $loggedIn Whether user was automatically logged in
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly array $meta = [],
        private readonly bool $loggedIn = false,
    ) {}

    /**
     * Check if user was automatically logged in.
     */
    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    /**
     * Get the authentication token if present.
     */
    public function getToken(): ?string
    {
        return $this->meta['token'] ?? null;
    }

    /**
     * Get the token type if present.
     */
    public function getTokenType(): ?string
    {
        return $this->meta['token_type'] ?? null;
    }

    /**
     * Get token expiration if present.
     */
    public function getTokenExpiration(): ?string
    {
        return $this->meta['expires_at'] ?? null;
    }
}
