<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout;

use Aristonis\LaravelAuthentication\Actions\Logout\Events\UserLoggedOutEvent;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * Abstract base class for all logout actions.
 *
 * Provides 80% shared logic:
 * - Token/session invalidation
 * - Event dispatching
 *
 * Subclasses implement handleLogout() for their specific method:
 * - API: Revoke Sanctum tokens
 * - Web: Destroy session
 * - Mobile: Revoke specific token
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout
 */
abstract class AbstractLogoutAction
{
    /**
     * @param TokenServiceInterface $tokenService Token service
     */
    public function __construct(
        protected readonly TokenServiceInterface $tokenService,
    ) {}

    /**
     * Execute logout action.
     *
     * @param LogoutDto $dto Logout data
     * @return LogoutResult Result with success status
     */
    public function __invoke(LogoutDto $dto): LogoutResult
    {
        // 1. Handle logout (DIFFERENT per type - Strategy pattern)
        $result = $this->handleLogout($dto);

        // 2. Dispatch event
        if ($result->success && $dto->user) {
            $this->dispatchEvent($dto->user);
        }

        return $result;
    }

    /**
     * Handle logout - IMPLEMENT PER TYPE.
     *
     * @param LogoutDto $dto Logout data
     * @return LogoutResult Result object
     */
    abstract protected function handleLogout(LogoutDto $dto): LogoutResult;

    /**
     * Revoke all tokens for user.
     *
     * Override to customize token revocation logic.
     *
     * @param Authenticatable $user The user
     * @return bool Success status
     */
    protected function revokeAllTokens(Authenticatable $user): bool
    {
        $user->tokens()->delete();
        return true;
    }

    /**
     * Revoke specific token by ID.
     *
     * Override to customize selective token revocation.
     *
     * @param Authenticatable $user The user
     * @param string|int $tokenId Token ID to revoke
     * @return bool Success status
     */
    protected function revokeToken(Authenticatable $user, string|int $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }

    /**
     * Revoke current token.
     *
     * Override to customize current token revocation.
     *
     * @param Authenticatable $user The user
     * @param string $token Raw token string
     * @return bool Success status
     */
    protected function revokeCurrentToken(Authenticatable $user, string $token): bool
    {
        $accessToken = $this->tokenService->findToken($token);
        if ($accessToken) {
            return $this->tokenService->revokeToken($token);
        }
        return false;
    }

    /**
     * Destroy web session.
     *
     * Override to customize session destruction.
     *
     * @return bool Success status
     */
    protected function destroySession(): bool
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return true;
    }

    /**
     * Dispatch user logged out event.
     *
     * Override to customize event dispatching.
     *
     * @param Authenticatable $user The user
     */
    protected function dispatchEvent(Authenticatable $user): void
    {
        event(new UserLoggedOutEvent($user));
    }
}
