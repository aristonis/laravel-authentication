<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\VerifyEmail;

use Aristonis\LaravelAuthentication\Actions\VerifyEmail\Events\EmailVerifiedEvent;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Abstract base class for all verify email actions.
 *
 * Provides 80% shared logic:
 * - Token validation
 * - User lookup
 * - Email verification
 * - Event dispatching
 *
 * Subclasses implement handleSuccess() for their specific method:
 * - API: Return verification status
 * - Web: Return success with redirect
 *
 * @package Aristonis\LaravelAuthentication\Actions\VerifyEmail
 */
abstract class AbstractVerifyEmailAction
{
    /**
     * @param UserIdentifierInterface $identifier User lookup service
     */
    public function __construct(
        protected readonly UserIdentifierInterface $identifier,
    ) {}

    /**
     * Execute email verification action.
     *
     * @param VerifyEmailDto $dto Verification data
     * @return VerifyEmailResult Result with verification status
     *
     * @throws InvalidTokenException If token is invalid or expired
     */
    public function __invoke(VerifyEmailDto $dto): VerifyEmailResult
    {
        // 1. Find user
        $user = $this->findUser($dto->userId);

        if (!$user) {
            throw new InvalidTokenException('Invalid verification link');
        }

        // 2. Check if already verified
        if ($this->isAlreadyVerified($user)) {
            return $this->handleAlreadyVerified($user, $dto);
        }

        // 3. Validate token
        $this->validateToken($dto->token, $user);

        // 4. Verify email
        $this->markEmailAsVerified($user);

        // 5. Handle success (DIFFERENT per type - Strategy pattern)
        $result = $this->handleSuccess($user, $dto);

        // 6. Dispatch event
        $this->dispatchEvent($user);

        return $result;
    }

    /**
     * Handle successful email verification - IMPLEMENT PER TYPE.
     *
     * @param Authenticatable $user The user
     * @param VerifyEmailDto $dto Verification data
     * @return VerifyEmailResult Result object
     */
    abstract protected function handleSuccess(
        Authenticatable $user,
        VerifyEmailDto $dto
    ): VerifyEmailResult;

    /**
     * Handle already verified email.
     *
     * Override to customize already verified handling.
     *
     * @param Authenticatable $user The user
     * @param VerifyEmailDto $dto Verification data
     * @return VerifyEmailResult Result object
     */
    protected function handleAlreadyVerified(
        Authenticatable $user,
        VerifyEmailDto $dto
    ): VerifyEmailResult {
        return new VerifyEmailResult(
            success: true,
            verified: false,
            alreadyVerified: true,
            message: 'Email is already verified'
        );
    }

    /**
     * Find user by ID.
     *
     * Override to customize user lookup logic.
     *
     * @param string|int $userId User ID
     * @return Authenticatable|null The user or null if not found
     */
    protected function findUser(string|int $userId): ?Authenticatable
    {
        $modelClass = config('auth.providers.users.model');

        return $modelClass::find($userId);
    }

    /**
     * Check if user's email is already verified.
     *
     * Override to customize verification check logic.
     *
     * @param Authenticatable $user The user
     * @return bool True if already verified
     */
    protected function isAlreadyVerified(Authenticatable $user): bool
    {
        return $user->email_verified_at !== null;
    }

    /**
     * Validate verification token.
     *
     * Override to customize token validation logic.
     *
     * @param string $token Verification token
     * @param Authenticatable $user The user
     * @throws InvalidTokenException If token is invalid or expired
     */
    protected function validateToken(string $token, Authenticatable $user): void
    {
        // Check if token matches expected format
        $expectedSignature = $this->generateSignature($user);

        if (!hash_equals($expectedSignature, $token)) {
            throw new InvalidTokenException('Invalid verification token');
        }

        // Check expiration
        $expiresAt = config('laravel-authentication.email.verification_expiry_hours', 24);
        // Token should be used within expiry period from user creation
        if ($user->created_at && $user->created_at->diffInHours() > $expiresAt) {
            throw new InvalidTokenException('Verification link has expired');
        }
    }

    /**
     * Generate signature for verification.
     *
     * Override to customize signature generation.
     *
     * @param Authenticatable $user The user
     * @return string The generated signature
     */
    protected function generateSignature(Authenticatable $user): string
    {
        $key = config('app.key');
        $userId = $user->getAuthIdentifier();
        $email = $user->email;

        return hash('sha256', "{$key}:{$userId}:{$email}");
    }

    /**
     * Mark user's email as verified.
     *
     * Override to customize verification logic.
     *
     * @param Authenticatable $user The user
     */
    protected function markEmailAsVerified(Authenticatable $user): void
    {
        $modelClass = config('auth.providers.users.model');
        $table = (new $modelClass())->getTable();

        DB::table($table)
            ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
            ->update([
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Dispatch email verified event.
     *
     * Override to customize event dispatching.
     *
     * @param Authenticatable $user The user
     */
    protected function dispatchEvent(Authenticatable $user): void
    {
        event(new EmailVerifiedEvent($user));
    }
}
