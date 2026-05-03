<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword;

use Aristonis\LaravelAuthentication\Actions\ChangePassword\Events\PasswordChangedEvent;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Exceptions\InvalidCredentialsException;
use Aristonis\LaravelAuthentication\Exceptions\ValidationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Abstract base class for all change password actions.
 *
 * Provides 80% shared logic:
 * - Old password verification
 * - New password validation
 * - Password update
 * - Event dispatching
 *
 * Subclasses implement handleSuccess() for their specific method:
 * - API: Return new token if needed
 * - Web: Return success message
 * - Mobile: Return new token if needed
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword
 */
abstract class AbstractChangePasswordAction
{
    /**
     * @param TokenServiceInterface $tokenService Token service
     * @param PasswordValidatorInterface $passwordValidator Password validation service
     */
    public function __construct(
        protected readonly TokenServiceInterface $tokenService,
        protected readonly PasswordValidatorInterface $passwordValidator,
    ) {}

    /**
     * Execute change password action.
     *
     * @param ChangePasswordDto $dto Change password data
     * @return ChangePasswordResult Result with success status
     *
     * @throws InvalidCredentialsException If old password is incorrect
     * @throws ValidationException If new password validation fails
     */
    public function __invoke(ChangePasswordDto $dto): ChangePasswordResult
    {
        // 1. Verify old password
        $this->verifyOldPassword($dto->user, $dto->oldPassword);

        // 2. Validate new password
        $this->validateNewPassword($dto->newPassword);

        // 3. Update password
        $this->updatePassword($dto->user, $dto->newPassword);

        // 4. Handle success (DIFFERENT per type - Strategy pattern)
        $result = $this->handleSuccess($dto->user, $dto);

        // 5. Dispatch event
        $this->dispatchEvent($dto->user);

        return $result;
    }

    /**
     * Handle successful password change - IMPLEMENT PER TYPE.
     *
     * @param Authenticatable $user The user
     * @param ChangePasswordDto $dto Change password data
     * @return ChangePasswordResult Result object
     */
    abstract protected function handleSuccess(
        Authenticatable $user,
        ChangePasswordDto $dto
    ): ChangePasswordResult;

    /**
     * Verify old password matches.
     *
     * Override to customize password verification.
     *
     * @param Authenticatable $user The user
     * @param string $oldPassword Old password to verify
     * @throws InvalidCredentialsException If password doesn't match
     */
    protected function verifyOldPassword(Authenticatable $user, string $oldPassword): void
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw new InvalidCredentialsException('Current password is incorrect');
        }
    }

    /**
     * Validate new password.
     *
     * Override to customize password validation.
     *
     * @param string $password New password
     * @throws ValidationException If password validation fails
     */
    protected function validateNewPassword(string $password): void
    {
        $errors = $this->passwordValidator->validate($password);
        if (!empty($errors)) {
            throw new ValidationException($errors, 'Password validation failed');
        }

        // Additional: ensure new password is different from old
        // (handled by password validator or can be added here)
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
                'updated_at' => now(),
            ]);
    }

    /**
     * Dispatch password changed event.
     *
     * Override to customize event dispatching.
     *
     * @param Authenticatable $user The user
     */
    protected function dispatchEvent(Authenticatable $user): void
    {
        event(new PasswordChangedEvent($user));
    }

    /**
     * Check if tokens should be regenerated after password change.
     *
     * Override to customize token regeneration logic.
     *
     * @return bool True if tokens should be regenerated
     */
    protected function shouldRegenerateTokens(): bool
    {
        return config('laravel-authentication.security.revoke_tokens_on_password_change', false);
    }
}
