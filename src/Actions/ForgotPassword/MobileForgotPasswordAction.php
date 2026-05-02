<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * Mobile forgot password action - sends reset email or SMS.
 *
 * Use case: Mobile applications where reset link can be sent via email or SMS.
 *
 * @package Aristonis\LaravelAuthentication\Actions\ForgotPassword
 */
class MobileForgotPasswordAction extends AbstractForgotPasswordAction
{
    /**
     * Handle successful forgot password request.
     *
     * For Mobile: Sends reset link via configured channel (email or SMS).
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
        // Send reset notification if user found
        if ($user) {
            $channel = $this->getNotificationChannel();

            if ($channel === 'sms' && method_exists($user, 'phone')) {
                $this->sendResetSms($user, $resetToken);
            } else {
                $this->sendResetEmail($user, $resetToken);
            }
        }

        // Always return generic message (security: don't reveal if user exists)
        return new ForgotPasswordResult(
            success: true,
            resetToken: null,
            message: 'If your account exists, you will receive password reset instructions shortly.',
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

        // Use custom mobile mailable or default
        $mailableClass = config('auth-package.email.mobile_forgot_password_mailable');

        if ($mailableClass && class_exists($mailableClass)) {
            Mail::to($user->email)->send(new $mailableClass($user, $resetToken, $resetUrl));
        } else {
            $user->notify(new \Illuminate\Auth\Notifications\ResetPassword($resetToken, $resetUrl));
        }
    }

    /**
     * Send password reset SMS.
     *
     * @param Authenticatable $user The user
     * @param string $resetToken The reset token
     */
    protected function sendResetSms(Authenticatable $user, string $resetToken): void
    {
        $phone = $user->phone ?? null;
        if (!$phone) {
            return;
        }

        $smsService = config('auth-package.sms.service');
        $message = config(
            'auth-package.sms.forgot_password_template',
            'Your password reset code is: {token}'
        );
        $message = str_replace('{token}', substr($resetToken, 0, 6), $message);

        // Send via configured SMS service
        if ($smsService && class_exists($smsService)) {
            $service = app($smsService);
            $service->send($phone, $message);
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
        $baseUrl = config('auth-package.password_reset.mobile_url', config('app.url'));
        $route = config('auth-package.password_reset.mobile_route', '/mobile/password-reset');

        return sprintf(
            '%s%s?token=%s&email=%s',
            rtrim($baseUrl, '/'),
            $route,
            urlencode($token),
            urlencode($email)
        );
    }

    /**
     * Get notification channel for Mobile.
     */
    protected function getNotificationChannel(): string
    {
        return config('auth-package.mobile.password_reset_channel', 'email');
    }
}
