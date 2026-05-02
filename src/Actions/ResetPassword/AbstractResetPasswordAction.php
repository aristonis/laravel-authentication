<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword;

use Aristonis\LaravelAuthentication\Actions\ResetPassword\Events\PasswordResetEvent;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Exceptions\InvalidTokenException;
use Aristonis\LaravelAuthentication\Exceptions\ValidationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Abstract base class for all reset password actions.
 *
 * Provides 80% shared logic:
 * - Token validation
 * - User lookup
 * - Password validation
 * - Password update
 * - Event dispatching
 *
 * Subclasses implement handleSuccess() for their specific method:
 * - API: Return new auth token
 * - Web: Return success message
 * - Mobile: Return new auth token with expiration
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword
 */
abstract class AbstractResetPasswordAction
{
    /**
     * @param TokenServiceInterface $tokenService Token service
     * @param UserIdentifierInterface $identifier User lookup service
     * @param PasswordValidatorInterface $passwordValidator Password validation service
     */
    public function __construct(
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserIdentifierInterface $identifier,
        protected readonly PasswordValidatorInterface $passwordValidator,
    ) {}

    /**
     * Execute reset password action.
     *
     * @param ResetPasswordDto $dto Reset password data
     * @return ResetPasswordResult Result with success status
     *
     * @throws InvalidTokenException If token is invalid or expired
     * @throws ValidationException If password validation fails
     */
    public function __invoke(ResetPasswordDto $dto): ResetPasswordResult
    {
        // 1. Validate token
        $this->validateToken($dto->token, $dto->email);

        // 2. Find user
        $user = $this->findUser($dto->email);

        if (!$user) {
            throw new InvalidTokenException('Invalid reset token');
        }

        // 3. Validate new password
        $this->validatePassword($dto->newPassword);

        // 4. Update password
        $this->updatePassword($user, $dto->newPassword);

        // 5. Handle success (DIFFERENT per type - Strategy pattern)
        $result = $this->handleSuccess($user, $dto);

        // 6. Dispatch event
        $this->dispatchEvent($user);

        // 7. Invalidate used token
        $this->invalidateToken($dto->token, $dto->email);

        return $result;
    }

    /**
     * Handle successful password reset - IMPLEMENT PER TYPE.
     *
     * @param Authenticatable $user The user
     * @param ResetPasswordDto $dto Reset password data
     * @return ResetPasswordResult Result object
     */
    abstract protected function handleSuccess(
        Authenticatable $user,
        ResetPasswordDto $dto
    ): ResetPasswordResult;

    /**
     * Find user by email.
     *
     * Override to customize user lookup logic.
     *
     * @param string $email User email
     * @return Authenticatable|null The user or null if not found
     */
    protected function findUser(string $email): ?Authenticatable
    {
        return $this->identifier->findUser($email);
    }

    /**
     * Validate reset token.
     *
     * Override to customize token validation logic.
     * Default implementation uses cache for token storage.
     *
     * @param string $token Reset token
     * @param string $email User email
     * @throws InvalidTokenException If token is invalid or expired
     */
    protected function validateToken(string $token, string $email): void
    {
        // Check if token exists in cache
        $storedToken = \Illuminate\Support\Facades\Cache::get('password_reset:' . $email);

        if (!$storedToken) {
            throw new InvalidTokenException('Invalid reset token');
        }

        // Verify token matches (use hash_equals for timing-safe comparison)
        if (!hash_equals($storedToken, $token)) {
            throw new InvalidTokenException('Invalid reset token');
        }

        // Note: Cache expiration handles token expiry automatically
    }

    /**
     * Validate new password.
     *
     * Override to customize password validation.
     *
     * @param string $password New password
     * @throws ValidationException If password validation fails
     */
    protected function validatePassword(string $password): void
    {
        $errors = $this->passwordValidator->validate($password);
        if (!empty($errors)) {
            throw new ValidationException($errors, 'Password validation failed');
        }
    }

    /**
     * Update user password.
     *
     * Override to customize password update logic.
     *
     * @param Authenticatable $user The user
     * @param string $password New password
     */
    protected function updatePassword(Authenticatable $user, string $password): void
    {
        $modelClass = config('auth.providers.users.model');
        $table = (new $modelClass())->getTable();

        DB::table($table)
            ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
            ->update([
                'password' => Hash::make($password),
                'remember_token' => null, // Invalidate all remember tokens
                'updated_at' => now(),
            ]);
    }

    /**
     * Invalidate used reset token.
     *
     * Override to customize token invalidation.
     * Default implementation removes token from cache.
     *
     * @param string $token Reset token
     * @param string $email User email
     */
    protected function invalidateToken(string $token, string $email): void
    {
        \Illuminate\Support\Facades\Cache::forget('password_reset:' . $email);
    }

    /**
     * Dispatch password reset event.
     *
     * Override to customize event dispatching.
     *
     * @param Authenticatable $user The user
     */
    protected function dispatchEvent(Authenticatable $user): void
    {
        event(new PasswordResetEvent($user));
    }
}
