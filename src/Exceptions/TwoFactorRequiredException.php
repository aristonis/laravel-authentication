<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Exceptions;

class TwoFactorRequiredException extends AuthenticationException
{
    public function __construct(string $message = 'Two-factor authentication required')
    {
        parent::__construct($message, 403);
    }
}
