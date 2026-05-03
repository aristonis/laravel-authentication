<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API change password action - returns new authentication token if configured.
 *
 * Use case: API clients that may need a new token after password change.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword
 */
class ApiChangePasswordAction extends AbstractChangePasswordAction
{
    /**
     * Handle successful password change.
     *
     * For API: Optionally creates new token if configured to revoke on password change.
     *
     * @param Authenticatable $user The user
     * @param ChangePasswordDto $dto Change password data
     * @return ChangePasswordResult Result with optional new token
     */
    protected function handleSuccess(
        Authenticatable $user,
        ChangePasswordDto $dto
    ): ChangePasswordResult {
        $newToken = null;

        // Optionally regenerate tokens after password change
        if ($this->shouldRegenerateTokens()) {
            // Revoke all existing tokens
            $user->tokens()->delete();

            // Create new token
            $newToken = $this->tokenService->createToken(
                $user,
                config('laravel-authentication.sanctum.token_name', 'password_change'),
                config('laravel-authentication.sanctum.abilities', ['*'])
            );
        }

        return new ChangePasswordResult(
            success: true,
            user: $user,
            newToken: $newToken,
            message: $newToken ? 'Password changed successfully. New token generated.' : 'Password changed successfully'
        );
    }
}
