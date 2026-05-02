<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * API forgot password action - returns reset token directly.
 *
 * Use case: API clients that need the reset token to display to user
 * or handle reset flow client-side.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ForgotPassword
 */
class ApiForgotPasswordAction extends AbstractForgotPasswordAction
{
    /**
     * Handle successful forgot password request.
     *
     * For API: Returns the reset token directly for client-side handling.
     *
     * @param Authenticatable|null $user The user (null if not found)
     * @param string $resetToken The generated reset token
     * @param ForgotPasswordDto $dto Forgot password data
     * @return ForgotPasswordResult Result with reset token
     */
    protected function handleSuccess(
        ?Authenticatable $user,
        string $resetToken,
        ForgotPasswordDto $dto
    ): ForgotPasswordResult {
        // Store token for verification later
        if ($user) {
            $this->storeResetToken($user, $resetToken);
        }

        // For API, return token directly (allows client to handle reset flow)
        // In production, you might want to send email anyway and return generic message
        $returnToken = config('auth-package.api.return_reset_token', false);

        return new ForgotPasswordResult(
            success: true,
            resetToken: $returnToken ? $resetToken : null,
            message: $returnToken
                ? 'Password reset token generated'
                : 'Password reset link sent to your email',
            user: $user
        );
    }

    /**
     * Get notification channel for API.
     */
    protected function getNotificationChannel(): string
    {
        return 'email';
    }
}
