<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;

use Aristonis\LaravelAuthentication\Actions\Login\AbstractLoginAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserResult;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Mobile login action - creates token with expiration.
 */
class MobileLoginAction extends AbstractLoginAction
{
    public function __construct(
        protected readonly RateLimitService                                                   $rateLimitService,
        protected readonly TokenServiceInterface   $tokenService,
        protected readonly UserIdentifierInterface $identifier,
        private readonly int                                                                  $tokenExpirationDays = 30,
    ) {
        parent::__construct($rateLimitService, $tokenService, $identifier);
    }

    protected function authenticate(
        Authenticatable $user,
        LoginUserDto $dto
    ): LoginUserResult {
        $expiresAt = now()->addDays($this->tokenExpirationDays);

        $token = $this->tokenService->createToken(
            $user,
            'mobile-token',
            ['mobile'],
            $expiresAt
        );

        return new LoginUserResult(
            user: $user,
            meta: [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->tokenExpirationDays * 24 * 60 * 60,
                'expires_at' => $expiresAt->toIso8601String(),
            ]
        );
    }
}
