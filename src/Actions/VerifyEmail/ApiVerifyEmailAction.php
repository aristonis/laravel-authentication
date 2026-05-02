<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\VerifyEmail;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API verify email action - returns verification status.
 *
 * Use case: API clients that need verification status for UI updates.
 *
 * @package Aristonis\LaravelAuthentication\Actions\VerifyEmail
 */
class ApiVerifyEmailAction extends AbstractVerifyEmailAction
{
    /**
     * Handle successful email verification.
     *
     * For API: Returns verification status and user data.
     *
     * @param Authenticatable $user The user
     * @param VerifyEmailDto $dto Verification data
     * @return VerifyEmailResult Result with verification status
     */
    protected function handleSuccess(
        Authenticatable $user,
        VerifyEmailDto $dto
    ): VerifyEmailResult {
        return new VerifyEmailResult(
            success: true,
            verified: true,
            alreadyVerified: false,
            message: 'Email verified successfully',
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
            message: 'Email is already verified',
            user: $user
        );
    }
}
