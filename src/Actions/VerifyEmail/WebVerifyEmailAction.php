<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\VerifyEmail;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Web verify email action - returns success with redirect info.
 *
 * Use case: Traditional web applications where user is redirected after verification.
 *
 * @package Aristonis\LaravelAuthentication\Actions\VerifyEmail
 */
class WebVerifyEmailAction extends AbstractVerifyEmailAction
{
    /**
     * Handle successful email verification.
     *
     * For Web: Returns success message with optional redirect URL.
     *
     * @param Authenticatable $user The user
     * @param VerifyEmailDto $dto Verification data
     * @return VerifyEmailResult Result with redirect info
     */
    protected function handleSuccess(
        Authenticatable $user,
        VerifyEmailDto $dto
    ): VerifyEmailResult {
        $redirectUrl = config('auth-package.email.verified_redirect_url', '/dashboard');

        return new VerifyEmailResult(
            success: true,
            verified: true,
            alreadyVerified: false,
            message: 'Email verified successfully. You can now access all features.',
            user: $user
        );
    }

    /**
     * Handle already verified email.
     */
    protected function handleAlreadyVerified(
        Authenticatable $user,
        VerifyEmailDto $dto
    ): VerifyEmailResult {
        return new VerifyEmailResult(
            success: true,
            verified: false,
            alreadyVerified: true,
            message: 'Your email is already verified.',
            user: $user
        );
    }
}
