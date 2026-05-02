<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Mobile registration action - creates Sanctum token with expiration.
 *
 * This registration action is designed for mobile applications where
 * tokens should expire after a configurable period for security.
 *
 * @extends AbstractRegisterAction<RegisterUserResult>
 *
 * @package Aristonis\LaravelAuthentication\Actions\Register
 */
class MobileRegisterAction extends AbstractRegisterAction
{
    /**
     * @param int $tokenExpirationDays Token expiration in days (default: 30)
     */
    public function __construct(
        protected readonly RateLimitService $rateLimitService,
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserCreatorInterface $userCreator,
        protected readonly PasswordValidatorInterface $passwordValidator,
        private readonly int $tokenExpirationDays = 30,
    ) {
        parent::__construct(
            $rateLimitService,
            $tokenService,
            $userCreator,
            $passwordValidator
        );
    }

    /**
     * Handle auto-login after registration.
     *
     * Creates a Sanctum token with expiration for mobile security.
     *
     * @param Authenticatable $user The registered user
     * @param RegisterUserDto $dto Registration data
     * @return RegisterUserResult Registration result with expiring token
     */
    protected function handleAutoLogin(
        Authenticatable $user,
        RegisterUserDto $dto
    ): RegisterUserResult {
        $expiresAt = now()->addDays($this->tokenExpirationDays);

        $token = $this->tokenService->createToken(
            $user,
            'mobile-registration',
            ['mobile'],
            $expiresAt
        );

        return new RegisterUserResult(
            user: $user,
            meta: [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->tokenExpirationDays * 24 * 60 * 60,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            loggedIn: true,
        );
    }
}
