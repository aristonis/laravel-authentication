<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Mobile change password action - returns new authentication token.
 *
 * Use case: Mobile applications that need a new token after password change.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword
 */
class MobileChangePasswordAction extends AbstractChangePasswordAction
{
    /**
     * Handle successful password change.
     *
     * For Mobile: Creates new token with expiration.
     *
     * @param Authenticatable $user The user
     * @param ChangePasswordDto $dto Change password data
     * @return ChangePasswordResult Result with new token
     */
    protected function handleSuccess(
        Authenticatable $user,
        ChangePasswordDto $dto
    ): ChangePasswordResult {
        // For mobile, always regenerate token for security
        $expiresInMinutes = config('laravel-authentication.mobile.token_expiry_minutes', 525600); // 1 year default
        $expiresAt = now()->addMinutes($expiresInMinutes);

        // Revoke all existing tokens for this device/user
        if (config('laravel-authentication.mobile.revoke_all_on_password_change', true)) {
            $user->tokens()->delete();
        }

        // Create new token with expiration
        $newToken = $this->tokenService->createToken(
            $user,
            config('laravel-authentication.mobile.token_name', 'mobile'),
            config('laravel-authentication.mobile.abilities', ['*']),
            $expiresAt
        );

        return new ChangePasswordResult(
            success: true,
            user: $user,
            newToken: $newToken,
            message: 'Password changed successfully. New token generated.'
        );
    }
}
