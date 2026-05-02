# RegisterAction Implementation - Handoff Summary

## 📋 What You're Building

A **RegisterAction** for the Laravel authentication package that follows the same action-based architecture as the existing LoginAction implementation.

**Goal**: Provide a flexible, extensible user registration system with validation, user creation, token generation, and event dispatching.

---

## 📁 Documents Provided

| Document | Purpose | Location |
|----------|---------|----------|
| **Requirements Specification** | Detailed requirements, constraints, acceptance criteria | `docs/REGISTER_ACTION_REQUIREMENTS.md` |
| **Quick Reference** | Code skeletons, examples, implementation checklist | `docs/REGISTER_ACTION_QUICK_REFERENCE.md` |
| **Architecture Diagram** | Visual flow of registration process | Opened in browser |
| **Class Diagram** | Class relationships and extension points | Opened in browser |
| **This Summary** | Quick overview and getting started | `docs/REGISTER_ACTION_HANDOFF.md` |

---

## 🎯 Key Requirements (TL;DR)

### Must Have (P0)
1. ✅ Validate user input (email, password, optional name)
2. ✅ Create user with hashed password
3. ✅ Prevent duplicate registration (409 conflict)
4. ✅ Rate limiting (3 attempts per 5 minutes default)
5. ✅ Generate Sanctum token for API registration
6. ✅ Dispatch `UserRegisteredEvent`
7. ✅ Follow immutability pattern (readonly DTOs/results)

### Should Have (P1)
8. ✅ Configurable validation rules
9. ✅ Extension points for custom user creation
10. ✅ Extension points for custom password validation
11. ✅ Auto-login option (configurable)
12. ✅ Mobile registration with expiring tokens

---

## 🏗️ Architecture Pattern

```
AbstractRegisterAction (base class - 80% shared logic)
├── ApiRegisterAction (creates Sanctum token)
├── WebRegisterAction (starts session, optional)
└── MobileRegisterAction (token with expiration)
```

**Key Difference from LoginAction**: Registration creates users instead of authenticating existing ones, so there's no `UserIdentifierInterface` dependency.

---

## 📦 Files to Create

```
src/Actions/Register/
├── AbstractRegisterAction.php      # Base class
├── ApiRegisterAction.php           # API implementation
├── RegisterUserDto.php             # Input DTO
├── RegisterUserResult.php          # Output result
└── Events/
    └── UserRegisteredEvent.php     # Success event

src/Contracts/
├── UserCreatorInterface.php        # Extension: custom user creation
└── PasswordValidatorInterface.php  # Extension: custom password rules

src/Rules/
└── PasswordRule.php                # Laravel validation rule

src/Services/
├── DefaultUserCreator.php          # Default user creation
└── DefaultPasswordValidator.php    # Default password validation
```

---

## 🔧 Configuration to Add

Add to `config/laravel-authentication.php`:

```php
'registration' => [
    'auto_login' => true,
    'token' => [
        'name' => 'registration_token',
        'abilities' => ['*'],
        'expiration_days' => 0,
    ],
    'validation' => [
        'email' => ['required', 'email', 'unique:users'],
        'password' => ['required', 'min:8'],
        'name' => ['nullable', 'string', 'max:255'],
    ],
    'required_fields' => ['email', 'password'],
    'user_creator' => null,
    'password_validator' => null,
],
```

---

## 🧪 Testing Requirements

**Minimum 80% coverage** with:

1. **Unit Tests**: Action logic, DTOs, validators
2. **Integration Tests**: Full registration flow with database
3. **Extension Tests**: Custom implementations work
4. **Security Tests**: Rate limiting, password validation, token handling

**Test Location**: `tests/Actions/Register/`

---

## 🚀 Implementation Order

### Day 1-2: Core Foundation
- [ ] `RegisterUserDto` and `RegisterUserResult`
- [ ] `UserRegisteredEvent`
- [ ] `AbstractRegisterAction` base class
- [ ] `UserCreatorInterface` + `DefaultUserCreator`
- [ ] `PasswordValidatorInterface` + `DefaultPasswordValidator`

### Day 3: Action Variants
- [ ] `ApiRegisterAction`
- [ ] `WebRegisterAction` (optional)
- [ ] `MobileRegisterAction`

### Day 4: Configuration & Integration
- [ ] Add config section
- [ ] Update service provider (if needed)
- [ ] Create `PasswordRule` for validation

### Day 5-6: Testing
- [ ] Unit tests for all classes
- [ ] Integration tests with database
- [ ] Extension point tests
- [ ] Security tests

### Day 7: Documentation
- [ ] Update README
- [ ] Add usage examples
- [ ] Document extension points

---

## ⚠️ Critical Gotchas

| Issue | Prevention |
|-------|------------|
| Mutating DTO | Make DTO `readonly`, create new instances |
| Exposing password hash | Use model hiding, never return in response |
| Rate limit race condition | Use DB unique constraint as final guard |
| Hardcoded validation | Pull from config, allow overrides |
| Token in logs | Document secure logging practices |
| Breaking existing config | Add new section, don't modify existing |

---

## 📚 Reference Materials

### Existing Code to Study
1. `src/Actions/Login/AbstractLoginAction.php` - Pattern reference
2. `src/Actions/Login/ApiLoginAction.php` - Implementation example
3. `src/Services/RateLimitService.php` - Rate limiting
4. `src/Services/TokenService.php` - Token generation

### Contracts to Follow
1. `src/Contracts/ActionInterface.php` - Action pattern
2. `src/Contracts/TokenServiceInterface.php` - Token service
3. `.qwen/rules/coding-style.md` - Immutability, naming
4. `.qwen/rules/testing.md` - 80% coverage, TDD

---

## ✅ Definition of Done

- [ ] All P0 requirements implemented
- [ ] All P1 requirements implemented
- [ ] 80%+ test coverage
- [ ] All extension points tested
- [ ] Configuration fully documented
- [ ] README updated with examples
- [ ] No breaking changes to existing code
- [ ] Code review passed
- [ ] Security review passed

---

## 🆘 When You Get Stuck

### Ambiguities to Clarify
See `REGISTER_ACTION_REQUIREMENTS.md` Section 10 for flagged ambiguities with recommendations.

### Common Questions

**Q: Should auto-login be enabled by default?**  
A: Yes (recommended) - matches modern UX expectations

**Q: What fields beyond email/password?**  
A: Start minimal (email, password only) - make extension easy

**Q: Should password rules apply to login too?**  
A: No - registration only to avoid breaking existing user logins

---

## 📞 Escalation Path

If you encounter:
- **Conflicting requirements** → Flag in PR, reference requirements doc
- **Technical blockers** → Create minimal POC, document findings
- **Scope creep** → Reference requirements doc, defer to P2/P3
- **Security concerns** → Run security-reviewer agent

---

## 🎯 Success Metrics

1. **Functional**: All requirements implemented and tested
2. **Quality**: 80%+ test coverage, no code smells
3. **Extensibility**: Extension points work with real examples
4. **Documentation**: Clear examples for common use cases
5. **Compatibility**: No breaking changes, existing tests pass

---

**Good luck! Start with the Quick Reference document and code skeletons.**

The requirements document has all the details. The diagrams show the architecture. You have everything you need to build this.
