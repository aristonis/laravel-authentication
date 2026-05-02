<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\RefreshToken;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Refresh Token result - EXTENDABLE by users.
 *
 * Contains new token and expiration information.
 *
 * @property string $newToken New authentication token
 * @property \DateTimeInterface|null $expiresAt Token expiration time
 * @property int|null $expiresIn Seconds until expiration
 * @property Authenticatable $user The user
 *
 * @package Aristonis\LaravelAuthentication\Actions\RefreshToken
 */
class RefreshTokenResult
{
    /**
     * @param Authenticatable $user The user
     * @param string $newToken New authentication token
     * @param \DateTimeInterface|null $expiresAt Token expiration time
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $newToken,
        public readonly ?\DateTimeInterface $expiresAt = null,
    ) {}

    /**
     * Get expiration time in seconds from now.
     */
    public function getExpiresIn(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }

        return (int) now()->diffInSeconds($this->expiresAt);
    }

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'new_token' => $this->newToken,
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'expires_in' => $this->getExpiresIn(),
            'user_id' => $this->user->getAuthIdentifier(),
        ], fn ($value) => $value !== null);
    }
}
