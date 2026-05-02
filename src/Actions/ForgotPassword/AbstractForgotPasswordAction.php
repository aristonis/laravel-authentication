<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword;

use Aristonis\LaravelAuthentication\Actions\ForgotPassword\Events\PasswordResetLinkSentEvent;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Abstract base class for all forgot password actions.
 *
 * Provides 80% shared logic:
 * - Rate limiting
 * - User lookup
 * - Reset token generation
 * - Event dispatching
 *
 * Subclasses implement handleSuccess() for their specific method:
 * - API: Return reset token
 * - Web: Send email, return generic success
 * - Mobile: Send SMS/email, return generic success
 *
 * @package Aristonis\LaravelAuthentication\Actions\ForgotPassword
 */
abstract class AbstractForgotPasswordAction
{
    /**
     * @param RateLimitService $rateLimitService Rate limiting service
     * @param TokenServiceInterface $tokenService Token service for reset token generation
     * @param UserIdentifierInterface $identifier User lookup service
     */
    public function __construct(
        protected readonly RateLimitService $rateLimitService,
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserIdentifierInterface $identifier,
    ) {}

    /**
     * Execute forgot password action.
     *
     * @param ForgotPasswordDto $dto Forgot password data
     * @return ForgotPasswordResult Result with reset token (API) or success message
     *
     * @throws RateLimitExceededException If rate limit exceeded
     */
    public function __invoke(ForgotPasswordDto $dto): ForgotPasswordResult
    {
        // 1. Check rate limit
        $this->checkRateLimit($dto->email);

        try {
            // 2. Find user
            $user = $this->findUser($dto->email);

            // 3. Generate reset token (even if user not found - timing attack prevention)
            $resetToken = $this->generateResetToken();

            // 4. Handle success (DIFFERENT per type - Strategy pattern)
            $result = $this->handleSuccess($user, $resetToken, $dto);

            // 5. Dispatch event (only if user found)
            if ($user) {
                $this->dispatchEvent($user, $resetToken);
            }

            // 6. Clear rate limit on success
            $this->rateLimitService->clear('forgot_password', $dto->email);

            return $result;
        } catch (\Exception $e) {
            // Record failed attempt for rate limiting
            $this->rateLimitService->record('forgot_password', $dto->email);
            throw $e;
        }
    }

    /**
     * Handle successful forgot password request - IMPLEMENT PER TYPE.
     *
     * @param Authenticatable|null $user The user (null if not found)
     * @param string $resetToken The generated reset token
     * @param ForgotPasswordDto $dto Forgot password data
     * @return ForgotPasswordResult Result object
     */
    abstract protected function handleSuccess(
        ?Authenticatable $user,
        string $resetToken,
        ForgotPasswordDto $dto
    ): ForgotPasswordResult;

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
     * Generate password reset token.
     *
     * Override to customize token generation logic.
     *
     * @return string The generated reset token
     */
    protected function generateResetToken(): string
    {
        return Hash::make(Str::random(60));
    }

    /**
     * Check rate limit.
     *
     * Override to customize rate limiting.
     *
     * @param string $email User email
     * @throws RateLimitExceededException If rate limit exceeded
     */
    protected function checkRateLimit(string $email): void
    {
        $maxAttempts = config('auth-package.rate_limits.forgot_password.max_attempts', 5);
        $decayMinutes = config('auth-package.rate_limits.forgot_password.decay_minutes', 60);

        if ($this->rateLimitService->isRateLimited('forgot_password', $email)) {
            throw new RateLimitExceededException('Too many password reset requests');
        }
    }

    /**
     * Dispatch password reset link sent event.
     *
     * Override to customize event dispatching.
     *
     * @param Authenticatable $user The user
     * @param string $resetToken The reset token
     */
    protected function dispatchEvent(Authenticatable $user, string $resetToken): void
    {
        $channel = $this->getNotificationChannel();
        event(new PasswordResetLinkSentEvent($user, $channel, $resetToken));
    }

    /**
     * Get notification channel for this action type.
     *
     * Override to customize notification channel.
     *
     * @return string The channel (email, sms)
     */
    protected function getNotificationChannel(): string
    {
        return 'email';
    }

    /**
     * Store reset token for user.
     *
     * Override to customize token storage (e.g., database, cache).
     * Default implementation uses cache for simplicity.
     *
     * @param Authenticatable $user The user
     * @param string $resetToken The reset token
     * @param int|null $expiresAt Token expiration in minutes
     */
    protected function storeResetToken(
        Authenticatable $user,
        string $resetToken,
        ?int $expiresAt = null
    ): void {
        $expiresAt = $expiresAt ?? config('auth-package.password_reset.token_expiry_minutes', 60);

        // Use cache for token storage (more flexible than database)
        \Illuminate\Support\Facades\Cache::put(
            'password_reset:' . $user->email,
            $resetToken,
            now()->addMinutes($expiresAt)
        );
    }

    /**
     * Verify reset token from storage.
     *
     * Override to customize token verification.
     *
     * @param string $email User email
     * @param string $token Reset token to verify
     * @return bool True if token is valid
     */
    protected function verifyStoredToken(string $email, string $token): bool
    {
        $storedToken = \Illuminate\Support\Facades\Cache::get('password_reset:' . $email);

        if (!$storedToken) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }
}
