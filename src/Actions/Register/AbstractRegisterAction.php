<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

use Aristonis\LaravelAuthentication\Actions\Register\Events\UserRegisteredEvent;
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;
use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;
use Aristonis\LaravelAuthentication\Exceptions\RateLimitExceededException;
use Aristonis\LaravelAuthentication\Exceptions\UserAlreadyExistsException;
use Aristonis\LaravelAuthentication\Exceptions\ValidationException;
use Aristonis\LaravelAuthentication\Services\RateLimitService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Abstract base class for all registration actions.
 *
 * Provides 80% shared logic:
 * - Rate limiting
 * - Input validation
 * - Duplicate user check
 * - User creation
 * - Event dispatching
 *
 * Subclasses implement handleAutoLogin() for their specific method:
 * - API: Create Sanctum token
 * - Web: Start session (optional)
 * - Mobile: Create token with expiration
 *
 * @template T of RegisterUserResult
 *
 * @package Aristonis\LaravelAuthentication\Actions\Register
 */
abstract class AbstractRegisterAction
{
    /**
     * @param RateLimitService $rateLimitService Rate limiting service
     * @param TokenServiceInterface $tokenService Token service
     * @param UserCreatorInterface $userCreator User creation service
     * @param PasswordValidatorInterface $passwordValidator Password validation service
     */
    public function __construct(
        protected readonly RateLimitService $rateLimitService,
        protected readonly TokenServiceInterface $tokenService,
        protected readonly UserCreatorInterface $userCreator,
        protected readonly PasswordValidatorInterface $passwordValidator,
    ) {}

    /**
     * Execute user registration.
     *
     * @param RegisterUserDto $dto Registration data
     * @return RegisterUserResult Registration result
     *
     * @throws ValidationException If validation fails
     * @throws UserAlreadyExistsException If user already exists
     * @throws RateLimitExceededException If rate limit exceeded
     */
    public function __invoke(RegisterUserDto $dto): RegisterUserResult
    {
        // 1. Check rate limit
        $this->checkRateLimit($dto->email);

        try {
            // 2. Validate input
            $this->validate($dto);

            // 3. Check for existing user
            if ($this->userExists($dto->email)) {
                throw new UserAlreadyExistsException('User already exists');
            }

            // 4. Create user
            $user = $this->createUser($dto);

            // 5. Handle auto-login (DIFFERENT per type - Strategy pattern)
            $result = $this->handleAutoLogin($user, $dto);

            // 6. Handle success (clear rate limit)
            $this->handleSuccessfulRegistration($user, $dto);

            // 7. Dispatch event
            event(new UserRegisteredEvent($user, $result->isLoggedIn()));

            return $result;
        } catch (\Exception $e) {
            // Record failed attempt for rate limiting
            $this->rateLimitService->record('registration', $dto->email);
            throw $e;
        }
    }

    /**
     * Handle auto-login after registration - IMPLEMENT PER TYPE.
     *
     * @param Authenticatable $user The registered user
     * @param RegisterUserDto $dto Registration data
     * @return RegisterUserResult Registration result
     */
    abstract protected function handleAutoLogin(
        Authenticatable $user,
        RegisterUserDto $dto
    ): RegisterUserResult;

    /**
     * Validate registration data.
     *
     * Override to customize validation logic.
     *
     * @param RegisterUserDto $dto Registration data
     * @throws ValidationException If validation fails
     */
    protected function validate(RegisterUserDto $dto): void
    {
        // Validate password strength using PasswordValidatorInterface
        $passwordErrors = $this->passwordValidator->validate($dto->password);
        if (!empty($passwordErrors)) {
            throw new ValidationException($passwordErrors, 'Password validation failed');
        }

        // Additional validation using Laravel validator
        $validationRules = config('auth-package.registration.validation', [
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $validator = Validator::make(
            ['email' => $dto->email, 'name' => $dto->name],
            $validationRules
        );

        if ($validator->fails()) {
            throw new ValidationException(
                $validator->errors()->all(),
                'Validation failed'
            );
        }
    }

    /**
     * Check if user exists by email.
     *
     * Override to customize user lookup logic.
     *
     * @param string $email User email
     * @return bool True if user exists
     */
    protected function userExists(string $email): bool
    {
        $modelClass = config('auth.providers.users.model');

        return $modelClass::where('email', $email)->exists();
    }

    /**
     * Create user from DTO.
     *
     * Override to customize user creation logic.
     *
     * @param RegisterUserDto $dto Registration data
     * @return Authenticatable The created user
     */
    protected function createUser(RegisterUserDto $dto): Authenticatable
    {
        $attributes = array_filter([
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'name' => $dto->name,
            ...$dto->additional,
        ], fn ($value) => $value !== null);

        return $this->userCreator->create($attributes);
    }

    /**
     * Check rate limit.
     *
     * Override to customize rate limiting.
     *
     * @param string $email User email
     * @throws RateLimitExceededException If rate limit exceeded
     */
    protected function checkRateLimit(string $email): void
    {
        if ($this->rateLimitService->isRateLimited('registration', $email)) {
            throw new RateLimitExceededException('Too many registration attempts');
        }
    }

    /**
     * Handle successful registration.
     *
     * Override to add custom success logic.
     *
     * @param Authenticatable $user The registered user
     * @param RegisterUserDto $dto Registration data
     */
    protected function handleSuccessfulRegistration(
        Authenticatable $user,
        RegisterUserDto $dto
    ): void {
        // Clear rate limit on success
        $this->rateLimitService->clear('registration', $dto->email);
    }
}
