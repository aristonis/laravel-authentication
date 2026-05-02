<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\RefreshToken;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Mobile refresh token action - returns new authentication token with mobile-specific expiration.
 *
 * Use case: Mobile applications that need to refresh their authentication token.
 *
 * @package Aristonis\LaravelAuthentication\Actions\RefreshToken
 */
class MobileRefreshTokenAction extends AbstractRefreshTokenAction
{
    /**
     * Handle token refresh for Mobile.
     *
     * For Mobile: Creates new token with mobile-specific expiration.
     *
     * @param Authenticatable $user The user
     * @param RefreshTokenDto $dto Refresh token data
     * @return RefreshTokenResult Result with new token
     */
    protected function handleRefresh(
        Authenticatable $user,
        RefreshTokenDto $dto
    ): RefreshTokenResult {
        // Calculate expiration (mobile-specific)
        $expiresInMinutes = config('auth-package.mobile.token_expiry_minutes', 525600); // 1 year default
        $expiresAt = now()->addMinutes($expiresInMinutes);

        // Create new token with mobile-specific settings
        $newToken = $this->tokenService->createToken(
            $user,
            config('auth-package.mobile.token_name', 'mobile'),
            config('auth-package.mobile.abilities', ['*']),
            $expiresAt
        );

        return new RefreshTokenResult(
            user: $user,
            newToken: $newToken,
            expiresAt: $expiresAt
        );
    }

    /**
     * Get token expiration duration in minutes for mobile.
     */
    protected function getTokenExpiryMinutes(): int
    {
        return config('auth-package.mobile.token_expiry_minutes', 525600);
    }
}
