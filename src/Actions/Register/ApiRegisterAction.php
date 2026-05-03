<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

use Aristonis\LaravelAuthentication\Contracts\ActionInterface;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API registration action - creates Sanctum token.
 *
 * This is the default registration action for API-based authentication.
 * It creates a new user and optionally generates a Sanctum token for
 * immediate authentication (auto-login).
 *
 * @extends AbstractRegisterAction<RegisterUserResult>
 *
 * @package Aristonis\LaravelAuthentication\Actions\Register
 */
class ApiRegisterAction extends AbstractRegisterAction implements ActionInterface
{
    /**
     * Handle auto-login after registration.
     *
     * Creates a Sanctum token if auto-login is enabled in config.
     *
     * @param Authenticatable $user The registered user
     * @param RegisterUserDto $dto Registration data
     * @return RegisterUserResult Registration result with token if auto-login enabled
     */
    protected function handleAutoLogin(
        Authenticatable $user,
        RegisterUserDto $dto
    ): RegisterUserResult {
        // Check if auto-login is enabled
        if (!config('laravel-authentication.registration.auto_login', true)) {
            return new RegisterUserResult(
                user: $user,
                loggedIn: false,
            );
        }

        // Create Sanctum token
        $token = $this->tokenService->createToken(
            $user,
            config('laravel-authentication.registration.token.name', 'registration_token'),
            config('laravel-authentication.registration.token.abilities', ['*'])
        );

        $meta = [
            'token' => $token,
            'token_type' => 'Bearer',
        ];

        // Add expiration if configured
        $expirationDays = config('laravel-authentication.registration.token.expiration_days', 0);
        if ($expirationDays > 0) {
            $meta['expires_at'] = now()->addDays($expirationDays)->toIso8601String();
        }

        return new RegisterUserResult(
            user: $user,
            meta: $meta,
            loggedIn: true,
        );
    }

    /**
     * Get the action's container binding name.
     *
     * @return string The binding name for dependency injection
     */
    public static function resolveName(): string
    {
        return 'api.register.action';
    }
}
