<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout;

/**
 * Mobile logout action - revokes specific token.
 *
 * Use case: Mobile applications that need to revoke a specific device token.
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout
 */
class MobileLogoutAction extends AbstractLogoutAction
{
    /**
     * Handle logout for Mobile.
     *
     * For Mobile: Revokes the specific token used for the request.
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
        $message = 'Logout failed';

        // Revoke specific token provided in request
        if ($dto->token) {
            $success = $this->revokeCurrentToken($dto->user, $dto->token);
            $message = $success ? 'Device logged out successfully' : 'Token not found';
        } elseif ($dto->tokenId) {
            // Revoke by token ID
            $success = $this->revokeToken($dto->user, $dto->tokenId);
            $message = $success ? 'Device logged out successfully' : 'Token not found';
        } else {
            // Default: revoke current token from request
            $currentToken = request()->bearerToken();
            if ($currentToken) {
                $success = $this->revokeCurrentToken($dto->user, $currentToken);
                $message = $success ? 'Device logged out successfully' : 'Logout failed';
            }
        }

        return new LogoutResult(
            success: $success,
            message: $message
        );
    }
}
