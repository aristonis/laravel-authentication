<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\RefreshToken;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Refresh Token Data Transfer Object.
 *
 * Immutable data container for token refresh input.
 * All properties are readonly to ensure immutability.
 *
 * @property Authenticatable $user The user requesting refresh
 * @property string $oldToken Current/old token to refresh
 * @property bool $revokeOld Whether to revoke the old token
 *
 * @package Aristonis\LaravelAuthentication\Actions\RefreshToken
 */
final readonly class RefreshTokenDto
{
    /**
     * @param Authenticatable $user The user requesting refresh
     * @param string $oldToken Current/old token to refresh
     * @param bool $revokeOld Whether to revoke the old token (default: true)
     */
    public function __construct(
        public Authenticatable $user,
        public string $oldToken,
        public bool $revokeOld = true,
    ) {}

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user->getAuthIdentifier(),
            'old_token' => '***', // Never return actual token
            'revoke_old' => $this->revokeOld,
        ];
    }
}
