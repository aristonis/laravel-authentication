<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout;

/**
 * API logout action - revokes Sanctum tokens.
 *
 * Use case: API clients that need to revoke authentication tokens.
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout
 */
class ApiLogoutAction extends AbstractLogoutAction
{
    /**
     * Handle logout for API.
     *
     * For API: Revokes current token or all tokens based on DTO.
     *
     * @param LogoutDto $dto Logout data
     * @return LogoutResult Result with success status
     */
    protected function handleLogout(LogoutDto $dto): LogoutResult
    {
        if (!$dto->user) {
            return new LogoutResult(
                success: false,
                message: 'No user to log out'
            );
        }

        $success = false;

        // Revoke all tokens if requested
        if ($dto->revokeAll) {
            $success = $this->revokeAllTokens($dto->user);
            $message = 'All tokens revoked successfully';
        } elseif ($dto->tokenId) {
            // Revoke specific token by ID
            $success = $this->revokeToken($dto->user, $dto->tokenId);
            $message = $success ? 'Token revoked successfully' : 'Token not found';
        } elseif ($dto->token) {
            // Revoke current token
            $success = $this->revokeCurrentToken($dto->user, $dto->token);
            $message = $success ? 'Token revoked successfully' : 'Token not found';
        } else {
            // Default: revoke current token from request
            $currentToken = request()->bearerToken();
            if ($currentToken) {
                $success = $this->revokeCurrentToken($dto->user, $currentToken);
            }
            $message = $success ? 'Logged out successfully' : 'Logout failed';
        }

        return new LogoutResult(
            success: $success,
            message: $message
        );
    }
}
