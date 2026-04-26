<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LoginFailedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly string $email,
        public readonly ?string $reason = null,
    ) {}
}
