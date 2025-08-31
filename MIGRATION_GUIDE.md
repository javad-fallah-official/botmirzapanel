# Migration Guide: Legacy to Domain-Driven Architecture

This guide explains how to migrate from the legacy architecture to the new domain-driven design (DDD) architecture.

## Overview

The BotMirzaPanel application has been restructured using Domain-Driven Design principles with the following layers:

- **Domain Layer**: Core business logic, entities, value objects, and domain services
- **Application Layer**: Use cases, commands, queries, and handlers (CQRS pattern)
- **Infrastructure Layer**: Database repositories, external services, and technical concerns
- **Presentation Layer**: HTTP controllers, Telegram handlers, and console commands

## Migration Strategy

The migration is designed to be **gradual and backward-compatible**:

1. **Phase 1**: New domain architecture runs alongside legacy code
2. **Phase 2**: Gradually migrate components to use new architecture
3. **Phase 3**: Remove legacy code once migration is complete

## Current Status

### ✅ Completed

- Domain entities and value objects
- Domain services and repositories
- Application layer with CQRS pattern
- Infrastructure persistence layer
- Database migrations system
- Service container for dependency injection
- Legacy adapter for backward compatibility

### 🔄 In Progress

- Migration of existing services
- Integration testing
- Performance optimization

### ⏳ Pending

- Domain events implementation
- External services reorganization
- Presentation layer restructuring

## How to Use During Migration

### Service Access

The application now provides two ways to access services:

#### 1. Legacy Helper Functions (Recommended during migration)

```php
// These functions now use the new architecture under the hood
$userService = userService(); // Returns LegacyUserServiceAdapter
$paymentService = paymentService();
$panelService = panelService();
$telegramBot = telegram();
$db = db();
```

#### 2. Direct Service Container Access (For new code)

```php
// Access the new service container
global $serviceContainer;

// Get domain services
$userService = $serviceContainer->get(UserService::class);
$userRepository = $serviceContainer->get(UserRepository::class);

// Get application handlers
$createUserHandler = $serviceContainer->get(CreateUserCommandHandler::class);
$getUserHandler = $serviceContainer->get(GetUserByIdQueryHandler::class);
```

### Database Migrations

Run database migrations to update table structures:

```bash
# Run all pending migrations
php migrate.php migrate

# Check migration status
php migrate.php status

# Update existing tables
php migrate.php update

# Rollback last migration
php migrate.php rollback
```

### User Service Migration Example

The `UserService` has been migrated to use the new architecture:

#### Before (Legacy)
```php
$userService = new UserService($config, $db);
$user = $userService->getUserById(123);
```

#### After (With Adapter)
```php
$userService = userService(); // Uses LegacyUserServiceAdapter
$user = $userService->getUserById(123); // Same interface, new implementation
```

#### New Architecture (For new code)
```php
global $serviceContainer;

// Using CQRS pattern
$getUserHandler = $serviceContainer->get(GetUserByIdQueryHandler::class);
$query = new GetUserByIdQuery(new UserId(123));
$user = $getUserHandler->handle($query);
```

## Key Benefits

### 1. Better Separation of Concerns
- Domain logic is isolated from infrastructure
- Clear boundaries between layers
- Easier testing and maintenance

### 2. CQRS Pattern
- Separate read and write operations
- Better performance and scalability
- Clear command/query separation

### 3. Domain-Driven Design
- Rich domain models with business logic
- Value objects for data integrity
- Domain services for complex operations

### 4. Dependency Injection
- Proper service container
- Easy testing with mocks
- Better code organization

## Migration Checklist

### For Existing Code

- [ ] Update service instantiation to use helper functions
- [ ] Run database migrations
- [ ] Test existing functionality
- [ ] Update any direct database queries to use repositories

### For New Features

- [ ] Use domain entities and value objects
- [ ] Implement commands and queries
- [ ] Use dependency injection
- [ ] Follow domain-driven design principles

## Common Migration Patterns

### 1. Service Replacement

```php
// Old
$userService = new UserService($config, $db);

// New
$userService = userService(); // Uses adapter
```

### 2. Database Operations

```php
// Old
$user = $db->findOne('users', ['id' => $userId]);

// New
$userRepository = $serviceContainer->get(UserRepository::class);
$user = $userRepository->findById(new UserId($userId));
```

### 3. Business Logic

```php
// Old
if ($user['balance'] >= $amount) {
    $db->update('users', ['balance' => $user['balance'] - $amount], ['id' => $userId]);
}

// New
$user = $userRepository->findById(new UserId($userId));
if ($user->canAfford(new Money($amount))) {
    $user->deductBalance(new Money($amount));
    $userRepository->save($user);
}
```

## Troubleshooting

### Service Not Found
If you get "Service not found" errors, ensure the service is registered in `ServiceContainer.php`.

### Database Connection Issues
Check that the configuration file exists and database credentials are correct.

### Migration Failures
Run `php migrate.php status` to check migration status and resolve any issues.

## Next Steps

1. **Test the migration**: Ensure all existing functionality works
2. **Migrate components gradually**: Start with less critical components
3. **Implement domain events**: Add event-driven architecture
4. **Optimize performance**: Use the new architecture's benefits
5. **Remove legacy code**: Once migration is complete

## Support

For migration issues or questions:
1. Check the migration status with `php migrate.php status`
2. Review the service container configuration
3. Test with legacy adapter for backward compatibility
4. Gradually migrate to new patterns