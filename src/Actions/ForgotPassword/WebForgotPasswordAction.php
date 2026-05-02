<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * Web forgot password action - sends reset email.
 *
 * Use case: Traditional web applications where reset link is sent via email.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ForgotPassword
 */
class WebForgotPasswordAction extends AbstractForgotPasswordAction
{
    /**
     * Handle successful forgot password request.
     *
     * For Web: Sends password reset email and returns generic success message.
     *
     * @param Authenticatable|null $user The user (null if not found)
     * @param string $resetToken The generated reset token
     * @param ForgotPasswordDto $dto Forgot password data
     * @return ForgotPasswordResult Result with success message
     */
    protected function handleSuccess(
        ?Authenticatable $user,
        string $resetToken,
        ForgotPasswordDto $dto
    ): ForgotPasswordResult {
        // Send reset email if user found
        if ($user) {
            $this->sendResetEmail($user, $resetToken);
        }

        // Always return generic message (security: don't reveal if email exists)
        return new ForgotPasswordResult(
            success: true,
            resetToken: null,
            message: 'If your email is registered, you will receive a password reset link shortly.',
            user: null
        );
    }

    /**
     * Send password reset email.
     *
     * @param Authenticatable $user The user
     * @param string $resetToken The reset token
     */
    protected function sendResetEmail(Authenticatable $user, string $resetToken): void
    {
        $resetUrl = $this->generateResetUrl($resetToken, $user->email);

        // Use Laravel's built-in password reset notification or custom mailable
        $mailableClass = config('auth-package.email.forgot_password_mailable');

        if ($mailableClass && class_exists($mailableClass)) {
            Mail::to($user->email)->send(new $mailableClass($user, $resetToken, $resetUrl));
        } else {
            // Fallback to Laravel's default password reset notification
            $user->notify(new \Illuminate\Auth\Notifications\ResetPassword($resetToken, $resetUrl));
        }
    }

    /**
     * Generate password reset URL.
     *
     * @param string $token The reset token
     * @param string $email User email
     * @return string The reset URL
     */
    protected function generateResetUrl(string $token, string $email): string
    {
        $baseUrl = config('auth-package.password_reset.frontend_url', config('app.url'));
        $route = config('auth-package.password_reset.route', '/password-reset');

        return sprintf(
            '%s%s?token=%s&email=%s',
            rtrim($baseUrl, '/'),
            $route,
            urlencode($token),
            urlencode($email)
        );
    }

    /**
     * Get notification channel for Web.
     */
    protected function getNotificationChannel(): string
    {
        return 'email';
    }
}
