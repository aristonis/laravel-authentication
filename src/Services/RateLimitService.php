<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Services;

use Illuminate\Support\Facades\Cache;

class RateLimitService
{
    /**
     * Check if the given key is rate limited.
     */
    public function isRateLimited(string $type, string $identifier): bool
    {
        $key = $this->getRateLimitKey($type, $identifier);
        $maxAttempts = $this->getMaxAttempts($type);
        
        $attempts = Cache::get($key, 0);
        
        return $attempts >= $maxAttempts;
    }

    /**
     * Record a failed attempt for rate limiting.
     */
    public function record(string $type, string $identifier): void
    {
        $key = $this->getRateLimitKey($type, $identifier);
        $decayMinutes = $this->getDecayMinutes($type);
        
        Cache::remember($key, $decayMinutes * 60, function () {
            return 0;
        });
        
        Cache::increment($key);
    }

    /**
     * Clear rate limit for the given key.
     */
    public function clear(string $type, string $identifier): void
    {
        $key = $this->getRateLimitKey($type, $identifier);
        Cache::forget($key);
    }

    /**
     * Get the cache key for rate limiting.
     */
    private function getRateLimitKey(string $type, string $identifier): string
    {
        return 'rate_limit:' . $type . ':' . md5($identifier);
    }

    /**
     * Get max attempts for the given type.
     */
    private function getMaxAttempts(string $type): int
    {
        return config("auth-package.rate_limits.{$type}.max_attempts", 5);
    }

    /**
     * Get decay minutes for the given type.
     */
    private function getDecayMinutes(string $type): int
    {
        return config("auth-package.rate_limits.{$type}.decay_minutes", 1);
    }
}
