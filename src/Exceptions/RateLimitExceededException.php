<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Exceptions;

class RateLimitExceededException extends AuthenticationException
{
    public function __construct(string $message = 'Too many attempts')
    {
        parent::__construct($message, 429);
    }
}
