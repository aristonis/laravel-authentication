# RegisterAction Requirements Specification

## Metadata
- **Project**: Laravel Authentication Package (aristonis/laravel-authentication)
- **Version**: 1.0.0
- **Date**: 2026-04-28
- **Analyst**: Requirements Analysis Agent
- **Status**: Ready for Implementation

---

## 1. Executive Summary

This document specifies the requirements for implementing a `RegisterAction` in the Laravel authentication package. The RegisterAction will follow the same action-based architecture pattern as the existing `LoginAction` implementation, providing a flexible, extensible user registration system with support for validation, user creation, token generation, and event dispatching.

### Key Design Principles
- **Action-based architecture**: Consistent with LoginAction pattern
- **Maximum extensibility**: Every component can be overridden
- **Backend-only**: No views or routes in package
- **Test-driven**: Tests in `tests/` directory at package root
- **Laravel 12-13 compatible**: Uses Sanctum for token management

---

## 2. Requirements Table

| ID | Category | Description | Priority | Source | Status |
|----|----------|-------------|----------|--------|--------|
| REQ-001 | Functional | RegisterAction must validate user input (email, password, optional fields) | High | Core | Pending |
| REQ-002 | Functional | RegisterAction must create new user records in the database | High | Core | Pending |
| REQ-003 | Functional | RegisterAction must support automatic login after registration (optional) | Medium | Core | Pending |
| REQ-004 | Functional | RegisterAction must generate Sanctum tokens for API registration | High | Core | Pending |
| REQ-005 | Functional | RegisterAction must dispatch UserRegisteredEvent on success | High | Core | Pending |
| REQ-006 | Functional | RegisterAction must prevent duplicate user registration | High | Core | Pending |
| REQ-007 | Non-Functional | RegisterAction must be rate-limited (configurable) | High | Security | Pending |
| REQ-008 | Non-Functional | RegisterAction must follow immutable data patterns | High | Architecture | Pending |
| REQ-009 | Extensibility | Validation rules must be configurable/overridable | High | Architecture | Pending |
| REQ-010 | Extensibility | User creation logic must be overridable via interface | High | Architecture | Pending |
| REQ-011 | Extensibility | Password validation must support custom rules | Medium | Architecture | Pending |
| REQ-012 | Integration | RegisterAction must integrate with existing TokenServiceInterface | High | Core | Pending |
| REQ-013 | Integration | RegisterAction must integrate with existing RateLimitService | High | Core | Pending |
| REQ-014 | Security | Password must meet configurable complexity requirements | High | Security | Pending |
| REQ-015 | Security | Sensitive data must not be exposed in response | High | Security | Pending |

---

## 3. Detailed Requirements

### 3.1 Functional Requirements

#### REQ-001: Input Validation
**Description**: RegisterAction must validate user input before processing registration.

**Validation Requirements**:
- **Email**: Required, valid email format, unique in database
- **Password**: Required, must meet complexity rules from config (REQ-014)
- **Name**: Optional by default, configurable requirement
- **Additional fields**: Support for custom fields via configuration

**Acceptance Criteria**:
- Invalid email format throws validation exception
- Duplicate email throws UserAlreadyExistsException (409)
- Weak password throws validation exception with specific rule violations
- All validation errors are descriptive but do not leak sensitive information

---

#### REQ-002: User Creation
**Description**: RegisterAction must create new user records in the database.

**Requirements**:
- Use Laravel's Eloquent ORM for user creation
- Hash password using Laravel's Hash facade (bcrypt/argon2id)
- Support custom user model from config (`auth.providers.users.model`)
- Set default values for optional fields
- Support mass assignment protection via fillable/guarded

**Acceptance Criteria**:
- User is persisted to database with hashed password
- User model is returned in result object
- Timestamps (created_at, updated_at) are set correctly

---

#### REQ-003: Automatic Login (Optional)
**Description**: RegisterAction may automatically log in the user after successful registration.

**Configuration**:
```php
'registration' => [
    'auto_login' => true, // Default: true
    'token_name' => 'registration_token',
]
```

**Acceptance Criteria**:
- When enabled, user receives authentication token immediately
- When disabled, user must login separately
- Behavior is configurable per deployment

---

#### REQ-004: Token Generation
**Description**: RegisterAction must generate Sanctum tokens for API registration flows.

**Requirements**:
- Use existing `TokenServiceInterface` (REQ-012)
- Token name configurable via config
- Token abilities configurable via config
- Optional expiration support (for mobile flows)

**Token Response Structure**:
```php
[
    'token' => 'plain_text_token',
    'token_type' => 'Bearer',
    'expires_at' => '2026-05-28T00:00:00Z', // Optional
]
```

---

#### REQ-005: Event Dispatching
**Description**: RegisterAction must dispatch `UserRegisteredEvent` on successful registration.

**Event Structure**:
```php
namespace Aristonis\LaravelAuthentication\Actions\Register\Events;

class UserRegisteredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly bool $autoLoggedIn = false,
    ) {}
}
```

**Configurable Listeners** (from existing config):
```php
'events' => [
    'user_registered' => [
        \App\Listeners\SendWelcomeEmail::class,
        \App\Listeners\TrackRegistration::class,
    ],
],
```

---

#### REQ-006: Duplicate Prevention
**Description**: RegisterAction must prevent duplicate user registration.

**Requirements**:
- Check for existing user by email (or configurable fields)
- Throw `UserAlreadyExistsException` (HTTP 409) if user exists
- Exception message should not leak whether email is registered

**Acceptance Criteria**:
- Attempting to register existing email throws exception
- Exception code is 409 (Conflict)
- Message is generic: "User already exists"

---

#### REQ-007: Rate Limiting
**Description**: RegisterAction must be rate-limited to prevent abuse.

**Configuration** (already exists in config):
```php
'rate_limits' => [
    'registration' => [
        'max_attempts' => 3,
        'decay_minutes' => 5,
    ],
],
```

**Requirements**:
- Use existing `RateLimitService` (REQ-013)
- Rate limit key based on email or IP address
- Throw `RateLimitExceededException` when limit exceeded

---

### 3.2 Non-Functional Requirements

#### REQ-008: Immutability
**Description**: All data objects must follow immutable patterns.

**Requirements**:
- DTOs must be `readonly` classes
- Result objects must have `readonly` properties
- No mutation of input parameters
- Return new instances for any modifications

**Rationale**: Consistent with existing LoginAction pattern and coding style rules.

---

#### REQ-009: Configurable Validation
**Description**: Validation rules must be configurable without code changes.

**Configuration Structure**:
```php
'registration' => [
    'validation' => [
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8'],
        'name' => ['nullable', 'string', 'max:255'],
    ],
    'custom_rules' => [
        // Custom validation rules can be injected here
    ],
],
```

**Extension Points**:
- Override validation rules via config
- Provide custom validation rule classes
- Hook into validation process via events

---

#### REQ-010: Extensible User Creation
**Description**: User creation logic must be overridable.

**Contract**: Create `UserCreatorInterface` for custom user creation logic.

**Use Cases**:
- Custom user model with additional fields
- Multi-tenant user creation
- LDAP/Active Directory user sync
- OAuth user registration flow

---

#### REQ-011: Custom Password Rules
**Description**: Password validation must support custom rules.

**Existing Config** (from login config, apply to registration):
```php
'password' => [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => false,
    'max_breached_level' => 0,
],
```

**Extension Points**:
- Custom password rule classes
- Integration with HaveIBeenPwned API
- Organization-specific password policies

---

#### REQ-012: TokenService Integration
**Description**: RegisterAction must use existing TokenServiceInterface.

**Dependency Injection**:
```php
public function __construct(
    protected readonly TokenServiceInterface $tokenService,
    // ...
)
```

**Rationale**: Consistency with LoginAction, allows token service swapping.

---

#### REQ-013: RateLimitService Integration
**Description**: RegisterAction must use existing RateLimitService.

**Dependency Injection**:
```php
public function __construct(
    protected readonly RateLimitService $rateLimitService,
    // ...
)
```

**Usage**:
- Check rate limit before processing
- Record failed attempts
- Clear on success (optional)

---

#### REQ-014: Password Complexity
**Description**: Password must meet configurable complexity requirements.

**Default Rules**:
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- Symbols optional by default

**Implementation**:
- Create `PasswordRule` class in `src/Rules/`
- Apply via validator
- Error messages must indicate which rules failed

---

#### REQ-015: Data Exposure Prevention
**Description**: Sensitive data must not be exposed in responses.

**Requirements**:
- Never return password hash in response
- Token returned only once (at creation)
- User object in response should exclude sensitive fields
- Error messages should not leak user existence

**Acceptable Response**:
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john @example.com"
    },
    "token": "plain_text_token",
    "token_type": "Bearer"
}
```

---

## 4. Architecture & Design Patterns

### 4.1 Action Pattern (Strategy Pattern)

The RegisterAction follows the same pattern as LoginAction:

```
AbstractRegisterAction (abstract base with 80% shared logic)
├── ApiRegisterAction (creates Sanctum token)
├── WebRegisterAction (may start session, no token)
└── MobileRegisterAction (token with expiration)
```

### 4.2 Class Structure

```
src/Actions/Register/
├── AbstractRegisterAction.php      # Base class with shared logic
├── ApiRegisterAction.php           # API registration (token)
├── WebRegisterAction.php           # Web registration (session)
├── MobileRegisterAction.php        # Mobile registration (expiring token)
├── RegisterUserDto.php             # Input DTO
├── RegisterUserResult.php          # Output result
└── Events/
    └── UserRegisteredEvent.php     # Registration success event
```

### 4.3 Contracts/Interfaces to Create

| Interface | Purpose | Extensibility |
|-----------|---------|---------------|
| `UserCreatorInterface` | Custom user creation logic | High - swap entire creation flow |
| `PasswordValidatorInterface` | Custom password validation | Medium - customize password rules |
| `RegistrationValidatorInterface` | Full registration validation | High - custom validation flows |

### 4.4 Extension Points

```
Extension Point                    | How to Extend
-----------------------------------|----------------------------------------
User creation                      | Implement UserCreatorInterface
Password validation                | Implement PasswordValidatorInterface
Input validation                   | Extend AbstractRegisterAction::validate()
Token generation                   | Already via TokenServiceInterface
Rate limiting                      | Already via RateLimitService
Event listeners                    | Config: events.user_registered
User identifier                    | Already via UserIdentifierInterface
```

---

## 5. Differences from LoginAction

| Aspect | LoginAction | RegisterAction | Notes |
|--------|-------------|----------------|-------|
| **Primary Goal** | Authenticate existing user | Create new user | Different business logic |
| **User Lookup** | Required (find by identifier) | Not applicable (creating new) | No UserIdentifierInterface needed |
| **Password Check** | Hash::check() | Hash::make() | Opposite operations |
| **Duplicate Handling** | Expected (normal flow) | Error (UserAlreadyExistsException) | Different exception flow |
| **Validation** | Minimal (identifier + password) | Extensive (email, password, name, etc.) | More validation rules |
| **Rate Limit Key** | Identifier (email/username) | Email OR IP address | Different rate limiting strategy |
| **Auto-login** | Always (by definition) | Optional (configurable) | New configuration option |
| **Events** | LoginSuccess/LoginFailed | UserRegistered | Different event types |
| **2FA Check** | Required before login | Not applicable (new user) | No 2FA setup yet |

---

## 6. Configuration Options

### 6.1 New Configuration Section

Add to `config/laravel-authentication.php`:

```php
'registration' => [
    // Auto-login after registration
    'auto_login' => true,
    
    // Token configuration for auto-login
    'token' => [
        'name' => 'registration_token',
        'abilities' => ['*'],
        'expiration_days' => 0, // 0 = no expiration
    ],
    
    // Validation rules
    'validation' => [
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8'],
        'name' => ['nullable', 'string', 'max:255'],
    ],
    
    // Required fields (for UI hints)
    'required_fields' => ['email', 'password'],
    
    // User creator class (optional)
    'user_creator' => null, // \App\Services\CustomUserCreator::class
    
    // Password validator class (optional)
    'password_validator' => null, // \App\Services\CustomPasswordValidator::class
],
```

### 6.2 Existing Configuration (Reuse)

These existing config sections apply to registration:

```php
// Password complexity rules (already exists)
'password' => [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => false,
    'max_breached_level' => 0,
],

// Rate limiting (already exists)
'rate_limits' => [
    'registration' => [
        'max_attempts' => 3,
        'decay_minutes' => 5,
    ],
],

// Events (already exists)
'events' => [
    'user_registered' => [],
],
```

---

## 7. Dependency Mapping Matrix

| REQ-ID | Depends On | Blocked By | Related To | Impact Level |
|--------|------------|------------|------------|--------------|
| REQ-001 | - | - | REQ-009, REQ-011 | High |
| REQ-002 | REQ-001 | - | REQ-010 | High |
| REQ-003 | REQ-002, REQ-004 | - | REQ-006 | Medium |
| REQ-004 | REQ-012 | - | REQ-003 | High |
| REQ-005 | REQ-002 | - | - | High |
| REQ-006 | REQ-001 | - | REQ-002 | High |
| REQ-007 | REQ-013 | - | REQ-001 | High |
| REQ-008 | - | - | - | High |
| REQ-009 | - | - | REQ-001 | High |
| REQ-010 | - | - | REQ-002 | High |
| REQ-011 | - | - | REQ-001, REQ-014 | Medium |
| REQ-012 | - | - | REQ-004 | High |
| REQ-013 | - | - | REQ-007 | High |
| REQ-014 | - | - | REQ-011 | High |
| REQ-015 | - | - | REQ-002 | High |

---

## 8. Risk Register

| RISK-ID | Description | Likelihood | Impact | Mitigation | Owner | Status |
|---------|-------------|------------|--------|------------|-------|--------|
| RISK-001 | Password validation too strict, blocks legitimate users | Medium | Medium | Provide clear error messages, make rules configurable | Implementer | Open |
| RISK-002 | Rate limiting too aggressive for high-traffic apps | Low | High | Make rate limits highly configurable, document tuning | Implementer | Open |
| RISK-003 | Custom user creator interface too complex | Medium | Low | Provide clear examples, keep interface minimal | Implementer | Open |
| RISK-004 | Auto-login token exposure in logs | Low | High | Document token handling, use secure logging practices | Implementer | Open |
| RISK-005 | Email uniqueness check race condition | Low | Medium | Use database unique constraint as final guard | Implementer | Open |
| RISK-006 | Breaking changes to existing config structure | Medium | High | Add new config section, don't modify existing | Implementer | Open |

---

## 9. Traceability Table

| REQ-ID | Constraints | Assumptions | Risks | Ambiguities | Verification Method |
|--------|-------------|-------------|-------|-------------|---------------------|
| REQ-001 | Laravel validation rules | User provides valid input | RISK-001 | AMBIGUITY-001 | Unit tests, integration tests |
| REQ-002 | Eloquent ORM, Hash facade | User model is Eloquent | RISK-005 | - | Unit tests, database tests |
| REQ-003 | Sanctum token service | Auto-login is desired | - | AMBIGUITY-002 | Unit tests, config tests |
| REQ-004 | TokenServiceInterface | Sanctum is installed | RISK-004 | - | Integration tests |
| REQ-005 | Laravel event system | Listeners are registered | - | - | Event listener tests |
| REQ-006 | UserAlreadyExistsException | Email is unique key | RISK-005 | - | Exception tests |
| REQ-007 | RateLimitService, Cache | Cache driver configured | RISK-002 | - | Rate limit tests |
| REQ-008 | PHP 8.3 readonly classes | Team follows immutability | - | - | Code review, static analysis |
| REQ-009 | Laravel validation | Config is published | RISK-006 | AMBIGUITY-003 | Config tests |
| REQ-010 | UserCreatorInterface | Users need custom creation | RISK-003 | - | Extension tests |
| REQ-011 | PasswordValidatorInterface | Custom rules needed | RISK-001 | - | Validation tests |
| REQ-012 | TokenService exists | TokenService is bound | - | - | Integration tests |
| REQ-013 | RateLimitService exists | RateLimitService is bound | - | - | Integration tests |
| REQ-014 | Password config exists | Rules are reasonable | RISK-001 | AMBIGUITY-004 | Validation tests |
| REQ-015 | Laravel model hiding | Sensitive fields marked hidden | - | - | Response tests |

---

## 10. Ambiguity Flags

### AMBIGUITY-001
- **Requirement**: REQ-001 (Input Validation)
- **Issue**: What additional fields beyond email/password should be validated by default?
- **Impact**: Could lead to over-validation or under-validation in default implementation
- **Resolution Options**:
  1. Minimal: Only email and password (simplest, most flexible)
  2. Common: Email, password, name (matches most use cases)
  3. Extensible: Email, password only, but provide easy extension mechanism
- **Priority**: Medium
- **Recommendation**: Option 3 - minimal defaults with clear extension path

---

### AMBIGUITY-002
- **Requirement**: REQ-003 (Automatic Login)
- **Issue**: Should auto-login be enabled by default?
- **Impact**: Affects user experience and security posture
- **Resolution Options**:
  1. Enabled by default (better UX, common in modern apps)
  2. Disabled by default (more secure, explicit opt-in)
  3. Config-only (no default, must be set explicitly)
- **Priority**: Low
- **Recommendation**: Option 1 - enabled by default, matches modern expectations

---

### AMBIGUITY-003
- **Requirement**: REQ-009 (Configurable Validation)
- **Issue**: How should custom validation rules merge with defaults?
- **Impact**: Could cause confusion about which rules apply
- **Resolution Options**:
  1. Replace: Custom rules completely replace defaults
  2. Merge: Custom rules add to defaults (union)
  3. Override: Custom rules override specific fields, others use defaults
- **Priority**: Medium
- **Recommendation**: Option 3 - field-level override provides best balance

---

### AMBIGUITY-004
- **Requirement**: REQ-014 (Password Complexity)
- **Issue**: Should password rules apply to registration only or login too?
- **Impact**: Affects scope of implementation
- **Impact**: If login also validates, could break existing user logins
- **Resolution Options**:
  1. Registration only (simpler, safer for existing users)
  2. Both registration and password reset (logical consistency)
  3. All password operations (most consistent, highest risk)
- **Priority**: Medium
- **Recommendation**: Option 1 - registration only, avoid breaking existing flows

---

## 11. Implementation Checklist

### Phase 1: Core Implementation
- [ ] Create `AbstractRegisterAction` base class
- [ ] Create `RegisterUserDto` input DTO
- [ ] Create `RegisterUserResult` result class
- [ ] Create `UserRegisteredEvent` event class
- [ ] Implement validation logic
- [ ] Implement user creation logic
- [ ] Implement rate limiting integration

### Phase 2: Action Variants
- [ ] Create `ApiRegisterAction` (token-based)
- [ ] Create `WebRegisterAction` (session-based, optional)
- [ ] Create `MobileRegisterAction` (expiring token)

### Phase 3: Contracts & Extension
- [ ] Create `UserCreatorInterface`
- [ ] Create `PasswordValidatorInterface`
- [ ] Create default implementations
- [ ] Add config for extension points

### Phase 4: Configuration
- [ ] Add registration config section
- [ ] Document all configuration options
- [ ] Update service provider if needed

### Phase 5: Testing
- [ ] Unit tests for AbstractRegisterAction
- [ ] Unit tests for each action variant
- [ ] Integration tests with database
- [ ] Rate limit tests
- [ ] Event dispatching tests
- [ ] Extension point tests

### Phase 6: Documentation
- [ ] Update README with registration usage
- [ ] Add examples for common use cases
- [ ] Document extension points
- [ ] Add migration guide if needed

---

## 12. Feasibility Assessment

### Technical Feasibility: **HIGH**

**Rationale**:
- All required infrastructure exists (TokenService, RateLimitService, events)
- Pattern is proven (LoginAction implementation)
- Laravel provides all necessary primitives (validation, hashing, Eloquent)
- No external dependencies beyond existing requirements

### Knowledge Gaps: **LOW**

**Known Unknowns**:
- Exact validation rules desired by package users
- Common custom user creation scenarios
- Typical rate limit requirements for registration

**Recommendations**:
- Start with conservative defaults
- Make all rules highly configurable
- Document extension patterns clearly

### Proof-of-Concept Needed: **NO**

The LoginAction implementation proves the pattern works. No additional POC required.

---

## 13. Assumptions

### ASSUMPTION-001
**Assumption**: The User model uses Eloquent ORM and is configurable via `auth.providers.users.model`.

**Impact**: If users use non-Eloquent models, custom UserCreatorInterface implementation required.

**Validation**: Check existing LoginAction usage - already assumes Eloquent.

---

### ASSUMPTION-002
**Assumption**: Sanctum is installed and configured (already a package requirement).

**Impact**: Token generation depends on Sanctum being available.

**Validation**: Confirmed in composer.json requirement.

---

### ASSUMPTION-003
**Assumption**: Email is the primary unique identifier for users.

**Impact**: Duplicate check focuses on email; other fields require custom implementation.

**Validation**: Matches existing identification.fields config pattern.

---

### ASSUMPTION-004
**Assumption**: Users want to customize validation rules via config, not code.

**Impact**: Invest in flexible validation configuration system.

**Validation**: Matches package's extensibility philosophy.

---

## 14. Verification Methods

### Unit Tests
- Test validation logic in isolation
- Test user creation with mocked database
- Test event dispatching with mocked events
- Test rate limiting with mocked cache

### Integration Tests
- Test full registration flow with test database
- Test token generation with Sanctum
- Test duplicate user prevention
- Test rate limiting with real cache driver

### Extension Tests
- Test custom UserCreatorInterface implementation
- Test custom PasswordValidatorInterface implementation
- Test custom validation rules
- Test config overrides

### Security Tests
- Test password hashing
- Test token exposure prevention
- Test rate limit bypass attempts
- Test SQL injection in validation

---

## 15. Success Criteria

### Functional Completeness
- [ ] All REQ-001 through REQ-015 implemented
- [ ] All ambiguities resolved or documented
- [ ] All risks mitigated or accepted

### Code Quality
- [ ] Follows existing code style (immutability, naming)
- [ ] All public methods documented with PHPDoc
- [ ] All exceptions properly typed
- [ ] No mutation of input data

### Test Coverage
- [ ] Minimum 80% code coverage
- [ ] All critical paths tested
- [ ] Extension points tested
- [ ] Security tests pass

### Documentation
- [ ] README updated with registration examples
- [ ] Configuration options documented
- [ ] Extension points documented
- [ ] Migration guide (if needed)

### Backward Compatibility
- [ ] No breaking changes to existing config
- [ ] No breaking changes to existing interfaces
- [ ] Existing tests still pass
- [ ] Service provider still works

---

## 16. Handoff Notes for Implementer

### Start Here
1. Read `src/Actions/Login/AbstractLoginAction.php` to understand the pattern
2. Review `config/laravel-authentication.php` for existing config structure
3. Check `src/Exceptions/UserAlreadyExistsException.php` (already exists)

### Key Files to Create
```
src/Actions/Register/
├── AbstractRegisterAction.php
├── ApiRegisterAction.php
├── RegisterUserDto.php
├── RegisterUserResult.php
└── Events/
    └── UserRegisteredEvent.php
```

### Key Interfaces to Create
```
src/Contracts/
├── UserCreatorInterface.php
└── PasswordValidatorInterface.php
```

### Configuration to Add
Add `registration` section to `config/laravel-authentication.php`

### Testing Strategy
1. Write failing tests first (TDD)
2. Start with AbstractRegisterAction
3. Test extension points early
4. Ensure 80%+ coverage before merge

### Common Pitfalls to Avoid
- Don't mutate DTO input parameters
- Don't expose password hash in responses
- Don't forget to regenerate session on web registration
- Don't hardcode validation rules - use config
- Don't forget rate limit recording on failures

---

## Appendix A: Example Usage

### Basic Registration (API)

```php
use Aristonis\LaravelAuthentication\Actions\Register\ApiRegisterAction;
use Aristonis\LaravelAuthentication\Actions\Register\RegisterUserDto;

class AuthController extends Controller
{
    public function register(Request $request, ApiRegisterAction $action)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'name' => ['nullable', 'string'],
        ]);

        $dto = new RegisterUserDto(
            email: $validated['email'],
            password: $validated['password'],
            name: $validated['name'] ?? null,
            ipAddress: $request->ip(),
        );

        $result = $action($dto);

        return response()->json([
            'user' => $result->user,
            'token' => $result->meta['token'] ?? null,
        ]);
    }
}
```

### Custom User Creator

```php
use Aristonis\LaravelAuthentication\Contracts\UserCreatorInterface;

class CustomUserCreator implements UserCreatorInterface
{
    public function create(array $attributes): Authenticatable
    {
        // Custom logic: multi-tenant, LDAP sync, etc.
        $user = User::create([
            ...$attributes,
            'tenant_id' => tenant()->id,
            'source' => 'registration',
        ]);

        // Send welcome email, track signup, etc.
        event(new UserSignedUp($user));

        return $user;
    }
}
```

### Custom Password Validator

```php
use Aristonis\LaravelAuthentication\Contracts\PasswordValidatorInterface;

class CustomPasswordValidator implements PasswordValidatorInterface
{
    public function validate(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters';
        }

        // Custom rule: no dictionary words
        if ($this->containsDictionaryWord($password)) {
            $errors[] = 'Password cannot contain dictionary words';
        }

        return $errors;
    }
}
```

---

## Appendix B: Related Documentation

- **LoginAction Implementation**: `src/Actions/Login/AbstractLoginAction.php`
- **Configuration**: `config/laravel-authentication.php`
- **Contracts**: `src/Contracts/`
- **Exceptions**: `src/Exceptions/`
- **Service Provider**: `src/AuthenticationServiceProvider.php`
- **Coding Style**: `.qwen/rules/coding-style.md` (immutability, naming)
- **Testing Requirements**: `.qwen/rules/testing.md` (80% coverage, TDD)
- **Security Guidelines**: `.qwen/rules/security.md` (no hardcoded secrets)

---

**Document Status**: Ready for Implementation  
**Next Step**: Hand off to implementer for Phase 1 (Core Implementation)
