<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Web change password action - returns success message.
 *
 * Use case: Traditional web applications using session-based authentication.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword
 */
class WebChangePasswordAction extends AbstractChangePasswordAction
{
    /**
     * Handle successful password change.
     *
     * For Web: Returns success message, session remains valid.
     *
     * @param Authenticatable $user The user
     * @param ChangePasswordDto $dto Change password data
     * @return ChangePasswordResult Result with success message
     */
    protected function handleSuccess(
        Authenticatable $user,
        ChangePasswordDto $dto
    ): ChangePasswordResult {
        // Optionally invalidate other sessions
        if ($this->shouldRegenerateTokens()) {
            // For web, this typically means regenerating the session
            request()->session()->regenerate();
        }

        return new ChangePasswordResult(
            success: true,
            user: $user,
            newToken: null,
            message: 'Password changed successfully'
        );
    }
}
