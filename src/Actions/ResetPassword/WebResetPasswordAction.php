<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Web reset password action - returns success message.
 *
 * Use case: Traditional web applications where user is redirected to login.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword
 */
class WebResetPasswordAction extends AbstractResetPasswordAction
{
    /**
     * Handle successful password reset.
     *
     * For Web: Returns success message, user should be redirected to login.
     *
     * @param Authenticatable $user The user
     * @param ResetPasswordDto $dto Reset password data
     * @return ResetPasswordResult Result with success message
     */
    protected function handleSuccess(
        Authenticatable $user,
        ResetPasswordDto $dto
    ): ResetPasswordResult {
        return new ResetPasswordResult(
            success: true,
            user: $user,
            newToken: null,
            message: 'Password reset successfully. You can now log in with your new password.'
        );
    }
}
