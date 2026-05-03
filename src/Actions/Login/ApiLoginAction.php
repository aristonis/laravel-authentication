<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;
use Aristonis\LaravelAuthentication\Contracts\ActionInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API login action - creates Sanctum token.
 */
class ApiLoginAction extends AbstractLoginAction implements ActionInterface
{
    protected function authenticate(
        Authenticatable $user,
        LoginUserDto $dto
    ): LoginUserResult {
        // Create Sanctum token
        $token = $this->tokenService->createToken(
            $user,
            config('laravel-authentication.sanctum.token_name'),
            config('laravel-authentication.sanctum.abilities')
        );

        return new LoginUserResult(
            user: $user,
            meta: [
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        );
    }

    /**
     * Get the action's container binding name.
     *
     * @return string The binding name for dependency injection
     */
    public static function resolveName(): string
    {
        return 'api.login.action';
    }
}
