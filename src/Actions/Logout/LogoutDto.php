<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Logout Data Transfer Object.
 *
 * Immutable data container for logout input.
 * All properties are readonly to ensure immutability.
 *
 * @property Authenticatable|null $user The user to log out
 * @property string|null $token Token to revoke (for selective revocation)
 * @property string|null $tokenId Token ID to revoke (for selective revocation)
 * @property bool $revokeAll Whether to revoke all tokens
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout
 */
final readonly class LogoutDto
{
    /**
     * @param Authenticatable|null $user The user to log out
     * @param string|null $token Token string to revoke (optional)
     * @param string|int|null $tokenId Token ID to revoke (optional)
     * @param bool $revokeAll Whether to revoke all tokens (default: false)
     */
    public function __construct(
        public ?Authenticatable $user = null,
        public ?string $token = null,
        public string|int|null $tokenId = null,
        public bool $revokeAll = false,
    ) {}

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->user?->getAuthIdentifier(),
            'token' => $this->token,
            'token_id' => $this->tokenId,
            'revoke_all' => $this->revokeAll,
        ], fn ($value) => $value !== null);
    }
}
