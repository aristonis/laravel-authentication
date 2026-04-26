<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginSuccessEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
    ) {}
}
