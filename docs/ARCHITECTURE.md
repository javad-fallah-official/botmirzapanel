# BotMirzaPanel Modular Architecture Design

This document outlines the comprehensive architectural design and structure of the BotMirzaPanel application, transitioning from legacy code to a modern, modular architecture.

## Overview

BotMirzaPanel is a web-based management system for Telegram bots with payment processing capabilities. The application follows a **Clean Architecture** pattern with **Domain-Driven Design (DDD)** principles, ensuring clear separation of concerns, maintainability, and testability.

## Architectural Principles

### 1. Clean Architecture Layers
- **Domain Layer**: Core business logic, entities, and domain services
- **Application Layer**: Use cases, application services, and DTOs
- **Infrastructure Layer**: External concerns (database, APIs, frameworks)
- **Presentation Layer**: Web controllers, CLI commands, API endpoints

### 2. Dependency Injection
- Comprehensive DI container for managing dependencies
- Service providers for modular service registration
- Elimination of circular dependencies
- Interface-based programming for loose coupling

### 3. Domain-Driven Design
- Rich domain entities with business logic
- Value objects for data integrity
- Domain services for complex business operations
- Repository pattern for data access abstraction
- Domain events for decoupled communication

## Dependency Injection Implementation

### Container Features
- **Service Registration**: Register services with singleton or transient lifecycle
- **Automatic Resolution**: Automatically resolve constructor dependencies
- **Factory Support**: Register factory functions for complex object creation
- **Alias Support**: Create aliases for services
- **Circular Dependency Detection**: Prevent infinite loops during resolution
- **Validation**: Validate container configuration

### Service Providers
Modular service registration through providers:

```php
// Infrastructure services (database, config, cache, logging)
InfrastructureServiceProvider

// Domain services and repositories
DomainServiceProvider

// Application services and use cases
ApplicationServiceProvider
```

### Usage Example

```php
// Bootstrap the application
$app = Application::createAndBoot();

// Get services from container
$userService = $app->get(UserApplicationService::class);
$paymentService = $app->get(PaymentApplicationService::class);

// Create a user
$userData = [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe'
];
$userDTO = $userService->createUser($userData);

// Process a payment
$paymentData = [
    'user_id' => $userDTO->id,
    'amount' => 100.00,
    'currency' => 'USD',
    'gateway' => 'stripe'
];
$paymentDTO = $paymentService->createPayment($paymentData);
```

## New Modular Structure

```
src/
├── Application/                    # Application Layer
│   ├── Bootstrap/                 # Application initialization
│   │   └── Application.php        # Main application bootstrap
│   ├── DTOs/                      # Data Transfer Objects
│   │   ├── UserDTO.php           # User data transfer object
│   │   └── PaymentDTO.php        # Payment data transfer object
│   └── Services/                  # Application services (use cases)
│       ├── UserApplicationService.php
│       └── PaymentApplicationService.php
├── Domain/                        # Domain Layer (Core Business Logic)
│   ├── Entities/                  # Domain entities
│   │   ├── User.php              # User aggregate root
│   │   └── Payment.php           # Payment aggregate root
│   ├── ValueObjects/              # Value objects
│   │   ├── UserId.php            # User identifier
│   │   ├── Email.php             # Email value object
│   │   └── Money.php             # Money value object
│   ├── Repositories/              # Repository interfaces
│   │   ├── UserRepositoryInterface.php
│   │   └── PaymentRepositoryInterface.php
│   ├── Services/                  # Domain services
│   │   ├── UserDomainService.php
│   │   └── PaymentDomainService.php
│   └── Events/                    # Domain events
│       ├── DomainEvent.php       # Base event interface
│       ├── AbstractDomainEvent.php
│       ├── UserEvents.php        # User-related events
│       └── PaymentEvents.php     # Payment-related events
├── Infrastructure/                # Infrastructure Layer
│   ├── Container/                 # Dependency injection
│   │   ├── Container.php         # DI container implementation
│   │   ├── ServiceProviderInterface.php
│   │   └── AbstractServiceProvider.php
│   ├── Providers/                 # Service providers
│   │   ├── InfrastructureServiceProvider.php
│   │   ├── DomainServiceProvider.php
│   │   └── ApplicationServiceProvider.php
│   ├── Persistence/               # Data persistence
│   │   ├── UserRepository.php    # User repository implementation
│   │   └── PaymentRepository.php # Payment repository implementation
│   ├── Database/                  # Database management
│   │   └── DatabaseManager.php
│   ├── Config/                    # Configuration management
│   │   └── ConfigManager.php
│   ├── Cache/                     # Caching layer
│   │   └── CacheManager.php
│   ├── Logging/                   # Logging system
│   │   └── Logger.php
│   └── Events/                    # Event handling
│       └── EventDispatcher.php
├── Shared/                        # Shared components
│   ├── Contracts/                 # Interfaces and contracts
│   │   ├── ContainerInterface.php
│   │   ├── EventDispatcherInterface.php
│   │   ├── DatabaseInterface.php
│   │   ├── ConfigInterface.php
│   │   ├── CacheInterface.php
│   │   └── LoggerInterface.php
│   └── Exceptions/                # Custom exceptions
│       ├── DomainException.php
│       ├── ValidationException.php
│       └── ServiceException.php
├── Bot/                           # Legacy bot functionality (to be refactored)
│   ├── BotManager.php
│   ├── CommandHandler.php
│   └── WebhookHandler.php
├── Payment/                       # Legacy payment functionality (to be refactored)
│   ├── PaymentService.php
│   └── Gateways/
├── User/                          # Legacy user functionality (to be refactored)
│   └── UserService.php
└── Web/                           # Legacy web interface (to be refactored)
    ├── Controllers/
    ├── Views/
    └── Assets/
```

## Architecture Benefits

### 1. Clear Separation of Concerns
- **Domain Layer**: Contains pure business logic without external dependencies
- **Application Layer**: Orchestrates use cases and coordinates between layers
- **Infrastructure Layer**: Handles external concerns (database, APIs, frameworks)
- **Presentation Layer**: Manages user interfaces and input/output

### 2. Dependency Injection Container
- **Automatic Resolution**: Container automatically resolves dependencies
- **Service Providers**: Modular registration of services
- **Circular Dependency Detection**: Prevents and detects circular dependencies
- **Singleton Management**: Proper lifecycle management of services
- **Interface Binding**: Loose coupling through interface-based programming

### 3. Domain-Driven Design Implementation
- **Rich Entities**: Business logic encapsulated in domain entities
- **Value Objects**: Immutable objects ensuring data integrity
- **Repository Pattern**: Abstract data access with clean interfaces
- **Domain Events**: Decoupled communication between bounded contexts
- **Domain Services**: Complex business operations that don't belong to entities

### 4. Improved Testability
- **Mockable Dependencies**: All dependencies are injected and can be mocked
- **Isolated Units**: Each component can be tested in isolation
- **Clear Boundaries**: Well-defined interfaces make testing straightforward
- **Event Testing**: Domain events can be tested independently

### 5. Enhanced Maintainability
- **Modular Structure**: Changes are localized to specific modules
- **Clear Responsibilities**: Each class has a single, well-defined purpose
- **Consistent Naming**: Established naming conventions throughout
- **Documentation**: Comprehensive documentation and code comments

## New Modular Architecture

### Core Principles
1. **Single Responsibility**: Each class/module has one clear purpose
2. **Dependency Injection**: All dependencies injected through constructors
3. **Interface Segregation**: Small, focused interfaces
4. **Separation of Concerns**: Clear boundaries between layers

### Directory Structure

```
src/
├── Application/
│   ├── Commands/           # Command handlers (CQRS pattern)
│   ├── Queries/            # Query handlers
│   ├── Services/           # Application services
│   └── DTOs/              # Data Transfer Objects
├── Domain/
│   ├── Entities/          # Domain entities
│   ├── ValueObjects/      # Value objects
│   ├── Repositories/      # Repository interfaces
│   ├── Services/          # Domain services
│   └── Events/            # Domain events
├── Infrastructure/
│   ├── Database/          # Database implementations
│   ├── External/          # External API clients
│   ├── Filesystem/        # File operations
│   └── Logging/           # Logging implementations
├── Presentation/
│   ├── Web/               # Web controllers
│   ├── Api/               # API controllers
│   ├── Telegram/          # Telegram bot handlers
│   └── Console/           # CLI commands
├── Shared/
│   ├── Contracts/         # Shared interfaces
│   ├── Exceptions/        # Custom exceptions
│   ├── Utils/             # Utility classes
│   └── Constants/         # Application constants
└── Config/
    ├── Services/          # Service definitions
    ├── Routes/            # Route definitions
    └── Parameters/        # Configuration parameters
```

### Layer Responsibilities

#### 1. Domain Layer
- **Entities**: Core business objects (User, Payment, Panel, Service)
- **Value Objects**: Immutable objects (Money, Email, UserId)
- **Repositories**: Data access contracts
- **Services**: Business logic that doesn't belong to entities
- **Events**: Domain events for decoupling

#### 2. Application Layer
- **Commands**: Write operations (CreateUser, ProcessPayment)
- **Queries**: Read operations (GetUserStats, ListPanels)
- **Services**: Orchestrate domain operations
- **DTOs**: Data transfer between layers

#### 3. Infrastructure Layer
- **Database**: Concrete repository implementations
- **External**: Third-party API clients (Telegram, Payment gateways)
- **Filesystem**: File operations
- **Logging**: Logging implementations

#### 4. Presentation Layer
- **Web**: HTTP controllers for web interface
- **Api**: REST API controllers
- **Telegram**: Bot command handlers
- **Console**: CLI command handlers

### Module Dependencies

```
Presentation → Application → Domain
     ↓              ↓
Infrastructure ← ← ← ← ←
```

- **Domain**: No dependencies on other layers
- **Application**: Depends only on Domain
- **Infrastructure**: Implements Domain contracts
- **Presentation**: Depends on Application and Infrastructure

### Key Interfaces

#### Repository Contracts
```php
interface UserRepositoryInterface
interface PaymentRepositoryInterface
interface PanelRepositoryInterface
interface ServiceRepositoryInterface
```

#### Service Contracts
```php
interface TelegramClientInterface
interface PaymentGatewayInterface
interface PanelAdapterInterface
interface NotificationServiceInterface
```

#### Event Contracts
```php
interface EventDispatcherInterface
interface EventListenerInterface
```

### Migration Strategy

#### Phase 1: Core Infrastructure
1. Create new directory structure
2. Implement dependency injection container
3. Create base interfaces and contracts
4. Migrate configuration management

#### Phase 2: Domain Layer
1. Extract and refactor entities
2. Create value objects
3. Define repository interfaces
4. Implement domain services

#### Phase 3: Application Layer
1. Create command/query handlers
2. Implement application services
3. Define DTOs
4. Add validation layer

#### Phase 4: Infrastructure Layer
1. Implement repository concrete classes
2. Create external service clients
3. Add logging and monitoring
4. Implement caching layer

#### Phase 5: Presentation Layer
1. Refactor Telegram handlers
2. Create web controllers
3. Implement API endpoints
4. Add CLI commands

#### Phase 6: Legacy Migration
1. Create adapter layer for legacy code
2. Gradually migrate functionality
3. Remove legacy files
4. Update documentation

### Performance Optimizations

1. **Lazy Loading**: Load services only when needed
2. **Caching**: Implement caching at multiple levels
3. **Connection Pooling**: Reuse database connections
4. **Event-Driven**: Use events for decoupling
5. **Tree Shaking**: Remove unused code in production

### Testing Strategy

1. **Unit Tests**: Test individual classes in isolation
2. **Integration Tests**: Test layer interactions
3. **Functional Tests**: Test complete workflows
4. **Performance Tests**: Ensure scalability

### Documentation Requirements

1. **API Documentation**: OpenAPI/Swagger specs
2. **Code Documentation**: PHPDoc for all public methods
3. **Architecture Documentation**: This document and diagrams
4. **Deployment Documentation**: Setup and configuration guides