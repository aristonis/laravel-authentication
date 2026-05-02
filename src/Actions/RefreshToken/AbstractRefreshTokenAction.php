<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\RefreshToken;

use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Abstract base class for all refresh token actions.
 *
 * Provides 80% shared logic:
 * - Token validation
 * - User lookup
 * - Token refresh
 *
 * Subclasses implement handleRefresh() for their specific method:
 * - API: Return new token with standard expiration
 * - Mobile: Return new token with mobile-specific expiration
 *
 * Note: Web sessions don't use token refresh - they use session regeneration.
 *
 * @package Aristonis\LaravelAuthentication\Actions\RefreshToken
 */
abstract class AbstractRefreshTokenAction
{
    /**
     * @param TokenServiceInterface $tokenService Token service
     * @param UserIdentifierInterface $identifier User lookup service
     */
    public function __construct(
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserIdentifierInterface $identifier,
    ) {}

    /**
     * Execute token refresh action.
     *
     * @param RefreshTokenDto $dto Refresh token data
     * @return RefreshTokenResult Result with new token
     *
     * @throws InvalidTokenException If token is invalid or expired
     */
    public function __invoke(RefreshTokenDto $dto): RefreshTokenResult
    {
        // 1. Find and validate token
        $accessToken = $this->validateToken($dto->oldToken);

        // 2. Find user
        $user = $this->findUser($accessToken->tokenable_id);

        if (!$user) {
            throw new InvalidTokenException('User not found');
        }

        // 3. Check if token is still valid (not revoked)
        if ($this->isTokenRevoked($accessToken)) {
            throw new InvalidTokenException('Token has been revoked');
        }

        // 4. Revoke old token
        $this->revokeOldToken($accessToken, $dto->revokeOld);

        // 5. Handle refresh (DIFFERENT per type - Strategy pattern)
        $result = $this->handleRefresh($user, $dto);

        return $result;
    }

    /**
     * Handle token refresh - IMPLEMENT PER TYPE.
     *
     * @param Authenticatable $user The user
     * @param RefreshTokenDto $dto Refresh token data
     * @return RefreshTokenResult Result with new token
     */
    abstract protected function handleRefresh(
        Authenticatable $user,
        RefreshTokenDto $dto
    ): RefreshTokenResult;

    /**
     * Find user by ID.
     *
     * Override to customize user lookup logic.
     *
     * @param string|int $userId User ID
     * @return Authenticatable|null The user or null if not found
     */
    protected function findUser(string|int $userId): ?Authenticatable
    {
        $modelClass = config('auth.providers.users.model');

        return $modelClass::find($userId);
    }

    /**
     * Validate token and return PersonalAccessToken.
     *
     * Override to customize token validation.
     *
     * @param string $token Raw token string
     * @return PersonalAccessToken The validated token
     * @throws InvalidTokenException If token is invalid
     */
    protected function validateToken(string $token): PersonalAccessToken
    {
        $accessToken = $this->tokenService->findToken($token);

        if (!$accessToken) {
            throw new InvalidTokenException('Invalid token');
        }

        // Check expiration if set
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            throw new InvalidTokenException('Token has expired');
        }

        return $accessToken;
    }

    /**
     * Check if token is revoked.
     *
     * Override to customize revocation check.
     *
     * @param PersonalAccessToken $token The token
     * @return bool True if revoked
     */
    protected function isTokenRevoked(PersonalAccessToken $token): bool
    {
        return $token->deleted_at !== null;
    }

    /**
     * Revoke old token.
     *
     * Override to customize token revocation logic.
     *
     * @param PersonalAccessToken $token The token to revoke
     * @param bool $revokeOld Whether to revoke the old token
     */
    protected function revokeOldToken(PersonalAccessToken $token, bool $revokeOld): void
    {
        if ($revokeOld) {
            // Delete the token directly (we already have the model)
            $token->delete();
        }
    }

    /**
     * Get token expiration duration in minutes.
     *
     * Override to customize expiration.
     *
     * @return int Minutes until expiration
     */
    protected function getTokenExpiryMinutes(): int
    {
        return config('auth-package.sanctum.token_expiry_minutes', 525600); // 1 year default
    }
}
