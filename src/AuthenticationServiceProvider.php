<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication;

use Aristonis\LaravelAuthentication\Actions\Login\Events\LoginFailedEvent;
use Aristonis\LaravelAuthentication\Actions\Login\Events\LoginSuccessEvent;
use Aristonis\LaravelAuthentication\Actions\Register\Events\UserRegisteredEvent;
use Aristonis\LaravelAuthentication\Actions\ForgotPassword\Events\PasswordResetLinkSentEvent;
use Aristonis\LaravelAuthentication\Actions\ResetPassword\Events\PasswordResetEvent;
use Aristonis\LaravelAuthentication\Actions\VerifyEmail\Events\EmailVerifiedEvent;
use Aristonis\LaravelAuthentication\Actions\Logout\Events\UserLoggedOutEvent;
use Aristonis\LaravelAuthentication\Actions\ChangePassword\Events\PasswordChangedEvent;
use Aristonis\LaravelAuthentication\Actions\RefreshToken\Events\TokenRefreshedEvent;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Identification\UserIdentifier;
use Aristonis\LaravelAuthentication\Services\DefaultPasswordValidator;
use Aristonis\LaravelAuthentication\Services\DefaultUserCreator;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Aristonis\LaravelAuthentication\Services\TokenService;
use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config with user's published config
        $this->mergeConfigFrom(
            __DIR__ . '/Config/laravel-authentication.php',
            'laravel-authentication'
        );

        // Register core services as singletons
        $this->registerServices();
    }

    public function boot(): void
    {
        // Publish configuration only (no other files)
        $this->publishes([
            __DIR__ . '/Config/laravel-authentication.php' => config_path('laravel-authentication.php'),
        ], 'laravel-authentication-config');

        // Publish migrations
        if (is_dir(__DIR__ . '/../database/migrations')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Register event listeners from config
        $this->registerEventListeners();
    }

    private function registerServices(): void
    {
        // Token Service
        $this->app->singleton(
            TokenServiceInterface::class,
            TokenService::class
        );

        // Rate Limit Service
        $this->app->singleton(
            RateLimitService::class,
            RateLimitService::class
        );

        // User Identifier - supports extension via config
        $this->app->bind(
            UserIdentifierInterface::class,
            function ($app) {
                $customClass = config('laravel-authentication.identification.custom');

                if ($customClass && class_exists($customClass)) {
                    return $app->make($customClass);
                }

                return $app->make(UserIdentifier::class);
            }
        );

        // User Creator - supports extension via config
        $this->app->bind(
            UserCreatorInterface::class,
            function ($app) {
                $customClass = config('laravel-authentication.registration.user_creator');

                if ($customClass && class_exists($customClass)) {
                    return $app->make($customClass);
                }

                return $app->make(DefaultUserCreator::class);
            }
        );

        // Password Validator - supports extension via config
        $this->app->bind(
            PasswordValidatorInterface::class,
            function ($app) {
                $customClass = config('laravel-authentication.registration.password_validator');

                if ($customClass && class_exists($customClass)) {
                    return $app->make($customClass);
                }

                return $app->make(DefaultPasswordValidator::class);
            }
        );
    }

    private function registerEventListeners(): void
    {
        $events = $this->app->make('events');
        $config = $this->app->make('config');

        // UserRegisteredEvent
        foreach ($config->get('laravel-authentication.events.user_registered', []) as $listener) {
            $events->listen(
                UserRegisteredEvent::class,
                $listener
            );
        }

        // LoginSuccessEvent
        foreach ($config->get('laravel-authentication.events.login_success', []) as $listener) {
            $events->listen(
                LoginSuccessEvent::class,
                $listener
            );
        }

        // LoginFailedEvent
        foreach ($config->get('laravel-authentication.events.login_failed', []) as $listener) {
            $events->listen(
                LoginFailedEvent::class,
                $listener
            );
        }

        // PasswordResetLinkSentEvent
        foreach ($config->get('laravel-authentication.events.password_reset_link_sent', []) as $listener) {
            $events->listen(
                PasswordResetLinkSentEvent::class,
                $listener
            );
        }

        // PasswordResetEvent
        foreach ($config->get('laravel-authentication.events.password_reset', []) as $listener) {
            $events->listen(
                PasswordResetEvent::class,
                $listener
            );
        }

        // EmailVerifiedEvent
        foreach ($config->get('laravel-authentication.events.email_verified', []) as $listener) {
            $events->listen(
                EmailVerifiedEvent::class,
                $listener
            );
        }

        // UserLoggedOutEvent
        foreach ($config->get('laravel-authentication.events.user_logged_out', []) as $listener) {
            $events->listen(
                UserLoggedOutEvent::class,
                $listener
            );
        }

        // PasswordChangedEvent
        foreach ($config->get('laravel-authentication.events.password_changed', []) as $listener) {
            $events->listen(
                PasswordChangedEvent::class,
                $listener
            );
        }

        // TokenRefreshedEvent
        foreach ($config->get('laravel-authentication.events.token_refreshed', []) as $listener) {
            $events->listen(
                TokenRefreshedEvent::class,
                $listener
            );
        }
    }
}
