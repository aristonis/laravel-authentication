<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Aristonis\LaravelAuthentication\AuthenticationServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load Sanctum migrations
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/sanctum/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuthenticationServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup test database
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        // App key for encryption (needed for 2FA)
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Sanctum configuration
        $app['config']->set('auth.guards.sanctum.driver', 'sanctum');
        $app['config']->set('auth.providers.users.model', \Aristonis\LaravelAuthentication\Tests\Models\User::class);

        // Package configuration defaults
        $app['config']->set('auth-package.namespace.base', 'App\\Packages\\Authentication');
        $app['config']->set('auth-package.sanctum.token_name', 'auth_token');
        $app['config']->set('auth-package.sanctum.abilities', ['*']);
        $app['config']->set('auth-package.sanctum.expiration_days', 0);
        $app['config']->set('auth-package.rate_limits.login.max_attempts', 5);
        $app['config']->set('auth-package.rate_limits.login.decay_minutes', 1);
    }

    protected function defineDatabase($app): void
    {
        // Create users table for tests
        $app['config']->set('database.default', 'testbench');
    }
}
