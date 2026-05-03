<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API reset password action - returns new authentication token.
 *
 * Use case: API clients that need a new token after password reset.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword
 */
class ApiResetPasswordAction extends AbstractResetPasswordAction
{
    /**
     * Handle successful password reset.
     *
     * For API: Creates new Sanctum token and returns it.
     *
     * @param Authenticatable $user The user
     * @param ResetPasswordDto $dto Reset password data
     * @return ResetPasswordResult Result with new token
     */
    protected function handleSuccess(
        Authenticatable $user,
        ResetPasswordDto $dto
    ): ResetPasswordResult {
        // Create new Sanctum token
        $token = $this->tokenService->createToken(
            $user,
            config('laravel-authentication.sanctum.token_name', 'password_reset'),
            config('laravel-authentication.sanctum.abilities', ['*'])
        );

        return new ResetPasswordResult(
            success: true,
            user: $user,
            newToken: $token,
            message: 'Password reset successfully'
        );
    }
}
