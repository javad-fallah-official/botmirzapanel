# Coding Standards and Conventions

This document defines the coding standards, naming conventions, and best practices for the BotMirzaPanel project.

## Table of Contents

1. [General Principles](#general-principles)
2. [PHP Standards](#php-standards)
3. [Naming Conventions](#naming-conventions)
4. [File Organization](#file-organization)
5. [Documentation Standards](#documentation-standards)
6. [Architecture Patterns](#architecture-patterns)
7. [Error Handling](#error-handling)
8. [Testing Standards](#testing-standards)

## General Principles

### 1. Code Quality
- Write clean, readable, and maintainable code
- Follow SOLID principles
- Use meaningful names for variables, methods, and classes
- Keep functions and methods small and focused
- Avoid deep nesting (max 3 levels)

### 2. Consistency
- Follow established patterns throughout the codebase
- Use consistent formatting and indentation
- Apply naming conventions uniformly
- Maintain consistent error handling approaches

### 3. Documentation
- Document all public APIs
- Include PHPDoc comments for classes and methods
- Write clear commit messages
- Maintain up-to-date README files

## PHP Standards

### 1. PSR Compliance
- Follow PSR-1 (Basic Coding Standard)
- Follow PSR-4 (Autoloading Standard)
- Follow PSR-12 (Extended Coding Style)

### 2. PHP Version
- Target PHP 8.1 or higher
- Use strict types: `declare(strict_types=1);`
- Leverage modern PHP features (typed properties, union types, etc.)

### 3. Code Structure
```php
<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\Email;

/**
 * User Entity
 * Represents a user in the system with business logic
 */
class User
{
    public function __construct(
        private UserId $id,
        private string $username,
        private Email $email,
        private string $firstName,
        private string $lastName
    ) {}

    public function getId(): UserId
    {
        return $this->id;
    }

    // Additional methods...
}
```

## Naming Conventions

### 1. Classes
- Use **PascalCase** for class names
- Use descriptive, noun-based names
- Suffix interfaces with `Interface`
- Suffix abstract classes with `Abstract` prefix
- Suffix exceptions with `Exception`

```php
// ✅ Good
class UserApplicationService
class PaymentRepositoryInterface
class AbstractDomainEvent
class ValidationException

// ❌ Bad
class userService
class paymentRepo
class BaseEvent
class ValidationError
```

### 2. Methods and Functions
- Use **camelCase** for method names
- Use verb-based names that describe the action
- Boolean methods should start with `is`, `has`, `can`, `should`

```php
// ✅ Good
public function createUser(array $data): UserDTO
public function isActive(): bool
public function hasPermission(string $permission): bool
public function canMakePayment(): bool

// ❌ Bad
public function user_create($data)
public function active()
public function permission($perm)
```

### 3. Properties and Variables
- Use **camelCase** for property and variable names
- Use descriptive names that indicate purpose
- Avoid abbreviations unless widely understood

```php
// ✅ Good
private string $firstName;
private UserRepositoryInterface $userRepository;
private EventDispatcherInterface $eventDispatcher;

// ❌ Bad
private $fname;
private $userRepo;
private $dispatcher;
```

### 4. Constants
- Use **SCREAMING_SNAKE_CASE** for constants
- Group related constants in classes or interfaces

```php
// ✅ Good
class PaymentStatus
{
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
}

// ❌ Bad
const paymentPending = 'pending';
const PAYMENT_COMPLETE = 'completed';
```

### 5. Namespaces
- Use **PascalCase** for namespace segments
- Follow PSR-4 autoloading standard
- Organize by architectural layer and feature

```php
// ✅ Good
namespace App\Domain\Entities;
namespace App\Application\Services;
namespace App\Infrastructure\Persistence;

// ❌ Bad
namespace app\domain\entities;
namespace Application_Services;
```

## File Organization

### 1. Directory Structure
- One class per file
- File name matches class name
- Organize files by architectural layer
- Group related functionality together

### 2. File Naming
- Use **PascalCase** for PHP class files
- Use **kebab-case** for configuration files
- Use **UPPERCASE** for documentation files

```
✅ Good
UserApplicationService.php
PaymentRepositoryInterface.php
database-config.php
README.md

❌ Bad
userApplicationService.php
payment_repository_interface.php
Database_Config.php
readme.md
```

### 3. Import Organization
- Group imports by type (built-in, vendor, application)
- Sort alphabetically within groups
- Use one import per line

```php
// ✅ Good
use DateTime;
use Exception;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\UserId;
use App\Shared\Exceptions\ValidationException;

// ❌ Bad
use App\Domain\Entities\User, App\Domain\ValueObjects\UserId;
use Exception, DateTime;
use Psr\Log\LoggerInterface;
```

## Documentation Standards

### 1. PHPDoc Comments
- Document all public methods and properties
- Include parameter and return type information
- Add meaningful descriptions

```php
/**
 * Create a new user in the system
 * 
 * @param array $userData User data including username, email, etc.
 * @return UserDTO The created user data transfer object
 * @throws ValidationException When user data is invalid
 * @throws ServiceException When user creation fails
 */
public function createUser(array $userData): UserDTO
{
    // Implementation...
}
```

### 2. Class Documentation
- Include class purpose and responsibility
- Document important design decisions
- Provide usage examples when helpful

```php
/**
 * User Application Service
 * 
 * Orchestrates user-related use cases and coordinates between
 * domain services, repositories, and event dispatching.
 * 
 * This service handles:
 * - User creation and validation
 * - User profile management
 * - User status changes
 * - Role assignment and permissions
 * 
 * @example
 * $userService = $container->get(UserApplicationService::class);
 * $user = $userService->createUser(['username' => 'john', 'email' => 'john@example.com']);
 */
class UserApplicationService
{
    // Implementation...
}
```

## Architecture Patterns

### 1. Dependency Injection
- Inject dependencies through constructor
- Use interfaces for loose coupling
- Avoid service locator pattern

```php
// ✅ Good
class UserApplicationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserDomainService $userDomainService,
        private EventDispatcherInterface $eventDispatcher
    ) {}
}

// ❌ Bad
class UserApplicationService
{
    private $userRepository;
    
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }
}
```

### 2. Repository Pattern
- Define repository interfaces in domain layer
- Implement repositories in infrastructure layer
- Use specific methods instead of generic CRUD

```php
// ✅ Good
interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function findActiveUsers(): array;
    public function save(User $user): void;
}

// ❌ Bad
interface UserRepositoryInterface
{
    public function find($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
}
```

### 3. Value Objects
- Make value objects immutable
- Include validation in constructor
- Implement equality methods

```php
// ✅ Good
class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}
```

## Error Handling

### 1. Exception Hierarchy
- Create specific exception types
- Extend from appropriate base exceptions
- Include context information

```php
// ✅ Good
class ValidationException extends DomainException
{
    public function __construct(
        string $entity,
        string $field,
        string $message,
        ?Throwable $previous = null
    ) {
        $fullMessage = "Validation failed for {$entity}.{$field}: {$message}";
        parent::__construct($fullMessage, 0, $previous);
    }
}

// ❌ Bad
throw new Exception('Validation failed');
```

### 2. Error Context
- Include relevant context in exceptions
- Use structured error messages
- Log errors appropriately

```php
// ✅ Good
try {
    $user = $this->userRepository->findById($userId);
    if (!$user) {
        throw new EntityNotFoundException('User', $userId->getValue());
    }
} catch (DatabaseException $e) {
    $this->logger->error('Failed to find user', [
        'user_id' => $userId->getValue(),
        'error' => $e->getMessage()
    ]);
    throw new ServiceException('UserService', 'findUser', 'Database error occurred', $e);
}
```

## Testing Standards

### 1. Test Organization
- Mirror source code structure in tests
- Use descriptive test method names
- Group related tests in test classes

```php
// ✅ Good
class UserApplicationServiceTest extends TestCase
{
    public function testCreateUserWithValidDataReturnsUserDTO(): void
    {
        // Test implementation...
    }

    public function testCreateUserWithInvalidEmailThrowsValidationException(): void
    {
        // Test implementation...
    }
}
```

### 2. Test Naming
- Use descriptive names that explain the scenario
- Follow the pattern: `test[MethodName][Scenario][ExpectedResult]`
- Use `@dataProvider` for multiple test cases

### 3. Mocking
- Mock external dependencies
- Use interfaces for mocking
- Verify interactions when necessary

```php
// ✅ Good
public function testCreateUserCallsRepositorySave(): void
{
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $userRepository->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(User::class));

    $service = new UserApplicationService($userRepository, ...);
    $service->createUser(['username' => 'test', 'email' => 'test@example.com']);
}
```

## Code Review Guidelines

### 1. Review Checklist
- [ ] Code follows established patterns
- [ ] Naming conventions are consistent
- [ ] Documentation is complete and accurate
- [ ] Error handling is appropriate
- [ ] Tests cover the functionality
- [ ] No security vulnerabilities
- [ ] Performance considerations addressed

### 2. Review Focus Areas
- **Architecture**: Does the code follow the established architecture?
- **Readability**: Is the code easy to understand?
- **Maintainability**: Will this code be easy to modify?
- **Testing**: Are there adequate tests?
- **Documentation**: Is the code properly documented?

## Tools and Automation

### 1. Code Quality Tools
- **PHP_CodeSniffer**: Enforce coding standards
- **PHPStan**: Static analysis for type safety
- **PHPMD**: Detect code smells and complexity
- **PHPUnit**: Unit testing framework

### 2. IDE Configuration
- Configure IDE to follow PSR-12 formatting
- Set up automatic import organization
- Enable real-time code quality checks
- Configure PHPDoc generation templates

### 3. Git Hooks
- Pre-commit hooks for code formatting
- Pre-push hooks for running tests
- Commit message validation
- Automated code quality checks

By following these coding standards and conventions, we ensure that the BotMirzaPanel codebase remains clean, maintainable, and consistent across all contributors.