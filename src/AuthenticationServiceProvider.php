<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication;

use Aristonis\LaravelAuthentication\Actions\Login\Events\LoginFailedEvent;
use Aristonis\LaravelAuthentication\Actions\Login\Events\LoginSuccessEvent;
use Aristonis\LaravelAuthentication\Actions\Register\Events\UserRegisteredEvent;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Identification\IdentifierFactory;
use Aristonis\LaravelAuthentication\Identification\UserIdentifier;
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
                $factory = new IdentifierFactory($app->make(Repository::class));
                return $factory->create();
            }
        );
    }

    private function registerEventListeners(): void
    {
        $events = $this->app->make('events');
        $config = $this->app->make('config');

        // UserRegisteredEvent
        foreach ($config->get('auth-package.events.user_registered', []) as $listener) {
            $events->listen(
                UserRegisteredEvent::class,
                $listener
            );
        }

        // LoginSuccessEvent
        foreach ($config->get('auth-package.events.login_success', []) as $listener) {
            $events->listen(
                LoginSuccessEvent::class,
                $listener
            );
        }

        // LoginFailedEvent
        foreach ($config->get('auth-package.events.login_failed', []) as $listener) {
            $events->listen(
                LoginFailedEvent::class,
                $listener
            );
        }
    }
}
