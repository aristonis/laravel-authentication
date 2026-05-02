<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout;

/**
 * Web logout action - destroys session.
 *
 * Use case: Traditional web applications using session-based authentication.
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout
 */
class WebLogoutAction extends AbstractLogoutAction
{
    /**
     * Handle logout for Web.
     *
     * For Web: Destroys the session and logs out the user.
     *
     * @param LogoutDto $dto Logout data
     * @return LogoutResult Result with success status
     */
    protected function handleLogout(LogoutDto $dto): LogoutResult
    {
        $success = $this->destroySession();

        return new LogoutResult(
            success: $success,
            message: $success ? 'Logged out successfully' : 'Logout failed'
        );
    }
}
