<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;

use Aristonis\LaravelAuthentication\Actions\Login\AbstractLoginAction;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserDto;
use Aristonis\LaravelAuthentication\Actions\Login\LoginUserResult;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Illuminate\Support\Facades\Auth;

/**
 * Web login action - starts session (NO token).
 */
class WebLoginAction extends AbstractLoginAction
{
    public function __construct(
        protected readonly RateLimitService $rateLimitService,
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserIdentifierInterface $identifier,
        private readonly bool $rememberByDefault = false,
    ) {
        parent::__construct($rateLimitService, $tokenService, $identifier);
    }

    protected function authenticate(
        \Illuminate\Contracts\Auth\Authenticatable $user,
        LoginUserDto $dto
    ): LoginUserResult {
        // Start session (NO token)
        Auth::login($user, $dto->remember ?? $this->rememberByDefault);

        // Regenerate session
        session()->regenerate();

        return new LoginUserResult(
            user: $user,
            meta: [
                'session_id' => session()->getId(),
            ]
        );
    }

    protected function handleSuccessfulLogin(
        \Illuminate\Contracts\Auth\Authenticatable $user,
        LoginUserDto $dto
    ): void {
        parent::handleSuccessfulLogin($user, $dto);

        // Fire Laravel's login event
        event(new \Illuminate\Auth\Events\Login(
            $user->guard_name ?? 'web',
            $user,
            true
        ));
    }
}
