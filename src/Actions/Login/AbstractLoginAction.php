<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;

use Aristonis\LaravelAuthentication\Actions\Login\Events\LoginFailedEvent;
use Aristonis\LaravelAuthentication\Actions\Login\Events\LoginSuccessEvent;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Exceptions\InvalidCredentialsException;
use Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException;
use Aristonis\LaravelAuthentication\Exceptions\TwoFactorRequiredException;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Abstract base class for all login actions.
 *
 * Provides 80% shared logic:
 * - Rate limiting
 * - User lookup
 * - Credential validation
 * - 2FA checking
 *
 * Subclasses implement authenticate() for their specific method:
 * - API: Create Sanctum token
 * - Web: Start session
 * - Mobile: Create token with expiration
 *
 * @template T of LoginUserResult
 */
abstract class AbstractLoginAction
{
    public function __construct(
        protected readonly RateLimitService $rateLimitService,
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserIdentifierInterface $identifier,
    ) {}

    /**
     * Execute login action.
     *
     * @param LoginUserDto $dto Login data
     * @return LoginUserResult Authentication result
     * @throws InvalidCredentialsException
     * @throws RateLimitExceededException
     * @throws TwoFactorRequiredException
     */
    public function __invoke(LoginUserDto $dto): LoginUserResult
    {
        // 1. Check rate limit
        $this->checkRateLimit($dto->identifier);

        // 2. Find user
        $user = $this->findUser($dto->identifier);

        // 3. Validate credentials
        if (!$user || !Hash::check($dto->password, $user->password)) {
            $this->handleFailedLogin($dto);
            throw new InvalidCredentialsException('Invalid credentials');
        }

        // 4. Check 2FA
        if ($this->requiresTwoFactor($user)) {
            throw new TwoFactorRequiredException('Two-factor authentication required');
        }

        // 5. Authenticate (DIFFERENT per type - Strategy pattern)
        $result = $this->authenticate($user, $dto);

        // 6. Handle success
        $this->handleSuccessfulLogin($user, $dto);

        return $result;
    }

    /**
     * Authenticate user - IMPLEMENT PER LOGIN TYPE.
     *
     * @param Authenticatable $user The authenticated user
     * @param LoginUserDto $dto Login data
     * @return LoginUserResult Authentication result
     */
    abstract protected function authenticate(
        Authenticatable $user,
        LoginUserDto $dto
    ): LoginUserResult;

    /**
     * Find user by identifier.
     *
     * Override to customize user lookup logic.
     */
    protected function findUser(string $identifier): ?Authenticatable
    {
        return $this->identifier->findUser($identifier);
    }

    /**
     * Check rate limit.
     *
     * Override to customize rate limiting.
     */
    protected function checkRateLimit(string $identifier): void
    {
        if ($this->rateLimitService->isRateLimited('login', $identifier)) {
            throw new RateLimitExceededException('Too many login attempts');
        }
    }

    /**
     * Handle failed login attempt.
     *
     * Override to add custom logging, notifications, etc.
     */
    protected function handleFailedLogin(LoginUserDto $dto): void
    {
        $this->rateLimitService->record('login', $dto->identifier);

        event(new LoginFailedEvent($dto->identifier, 'invalid_credentials'));
    }

    /**
     * Handle successful login.
     *
     * Override to add custom success logic.
     */
    protected function handleSuccessfulLogin(
        Authenticatable $user,
        LoginUserDto $dto
    ): void {
        $this->rateLimitService->clear('login', $dto->identifier);

        event(new LoginSuccessEvent($user));
    }

    /**
     * Check if user requires 2FA.
     *
     * Override to customize 2FA logic.
     */
    protected function requiresTwoFactor(Authenticatable $user): bool
    {
        return $user->two_factor_secret !== null;
    }
}
