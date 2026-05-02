<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\RefreshToken;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API refresh token action - returns new authentication token.
 *
 * Use case: API clients that need to refresh their authentication token.
 *
 * @package Aristonis\LaravelAuthentication\Actions\RefreshToken
 */
class ApiRefreshTokenAction extends AbstractRefreshTokenAction
{
    /**
     * Handle token refresh for API.
     *
     * For API: Creates new Sanctum token with standard expiration.
     *
     * @param Authenticatable $user The user
     * @param RefreshTokenDto $dto Refresh token data
     * @return RefreshTokenResult Result with new token
     */
    protected function handleRefresh(
        Authenticatable $user,
        RefreshTokenDto $dto
    ): RefreshTokenResult {
        // Calculate expiration
        $expiresInMinutes = $this->getTokenExpiryMinutes();
        $expiresAt = now()->addMinutes($expiresInMinutes);

        // Create new token
        $newToken = $this->tokenService->createToken(
            $user,
            config('auth-package.sanctum.token_name', 'api'),
            config('auth-package.sanctum.abilities', ['*']),
            $expiresAt
        );

        return new RefreshTokenResult(
            user: $user,
            newToken: $newToken,
            expiresAt: $expiresAt
        );
    }
}
