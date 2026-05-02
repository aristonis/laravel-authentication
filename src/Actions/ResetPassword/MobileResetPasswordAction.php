<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Mobile reset password action - returns new authentication token with expiration.
 *
 * Use case: Mobile applications that need a new token after password reset.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword
 */
class MobileResetPasswordAction extends AbstractResetPasswordAction
{
    /**
     * Handle successful password reset.
     *
     * For Mobile: Creates new token with expiration.
     *
     * @param Authenticatable $user The user
     * @param ResetPasswordDto $dto Reset password data
     * @return ResetPasswordResult Result with new token and expiration
     */
    protected function handleSuccess(
        Authenticatable $user,
        ResetPasswordDto $dto
    ): ResetPasswordResult {
        // Calculate expiration
        $expiresInMinutes = config('auth-package.mobile.token_expiry_minutes', 525600); // 1 year default
        $expiresAt = now()->addMinutes($expiresInMinutes);

        // Create new token with expiration
        $token = $this->tokenService->createToken(
            $user,
            config('auth-package.mobile.token_name', 'mobile'),
            config('auth-package.mobile.abilities', ['*']),
            $expiresAt
        );

        return new ResetPasswordResult(
            success: true,
            user: $user,
            newToken: $token,
            message: 'Password reset successfully'
        );
    }
}
