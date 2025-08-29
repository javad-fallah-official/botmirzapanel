# Project Structure Blueprint

## Executive Summary

After comprehensive analysis of the BotMirzaPanel codebase, this document proposes an optimal project structure that addresses current architectural inconsistencies and establishes a clean, scalable foundation following Domain-Driven Design (DDD) and Clean Architecture principles.

## Current Architecture Analysis

### Strengths
- Clear separation of concerns with layered architecture
- Dependency injection container implementation
- Strategy pattern for payment gateways and panel adapters
- Value objects for domain modeling (Money)
- Service provider pattern for bootstrapping

### Issues Identified
1. **Inconsistent Layer Organization**: Mixed placement of services across layers
2. **Domain Layer Violations**: Domain services depending on infrastructure
3. **Presentation Layer Confusion**: Multiple presentation entry points scattered
4. **Repository Pattern Incomplete**: Missing proper domain entity definitions
5. **Configuration Management**: Scattered across multiple locations
6. **Legacy Database Access**: Direct database calls mixed with repository pattern

## Proposed Optimal Structure

```
src/
├── Domain/                           # Pure business logic (no external dependencies)
│   ├── Entities/                     # Domain entities
│   │   ├── User/
│   │   │   ├── User.php             # User aggregate root
│   │   │   ├── UserProfile.php      # User profile entity
│   │   │   └── UserPreferences.php  # User preferences entity
│   │   ├── Payment/
│   │   │   ├── Payment.php          # Payment aggregate root
│   │   │   ├── PaymentMethod.php    # Payment method entity
│   │   │   └── Transaction.php      # Transaction entity
│   │   ├── Panel/
│   │   │   ├── Panel.php            # Panel aggregate root
│   │   │   ├── PanelUser.php        # Panel user entity
│   │   │   └── PanelConfiguration.php
│   │   └── Subscription/
│   │       ├── Subscription.php     # Subscription aggregate root
│   │       ├── Plan.php             # Subscription plan entity
│   │       └── Usage.php            # Usage tracking entity
│   ├── ValueObjects/                 # Immutable value objects
│   │   ├── Common/
│   │   │   ├── Id.php               # Generic ID value object
│   │   │   ├── Email.php            # Email value object
│   │   │   ├── PhoneNumber.php      # Phone number value object
│   │   │   └── DateRange.php        # Date range value object
│   │   ├── Payment/
│   │   │   ├── Money.php            # Money value object (existing)
│   │   │   ├── Currency.php         # Currency value object
│   │   │   └── PaymentStatus.php    # Payment status value object
│   │   ├── User/
│   │   │   ├── UserId.php           # User ID value object
│   │   │   ├── Username.php         # Username value object
│   │   │   └── UserStatus.php       # User status value object
│   │   └── Panel/
│   │       ├── PanelId.php          # Panel ID value object
│   │       ├── PanelType.php        # Panel type value object
│   │       └── ConnectionConfig.php # Panel connection config
│   ├── Services/                     # Domain services (business logic)
│   │   ├── User/
│   │   │   ├── UserRegistrationService.php
│   │   │   ├── UserAuthenticationService.php
│   │   │   └── ReferralService.php
│   │   ├── Payment/
│   │   │   ├── PaymentProcessingService.php
│   │   │   ├── PaymentValidationService.php
│   │   │   └── RefundService.php
│   │   ├── Panel/
│   │   │   ├── PanelUserManagementService.php
│   │   │   ├── PanelSynchronizationService.php
│   │   │   └── PanelHealthCheckService.php
│   │   └── Subscription/
│   │       ├── SubscriptionManagementService.php
│   │       ├── UsageTrackingService.php
│   │       └── ExpirationService.php
│   ├── Repositories/                 # Repository interfaces (contracts)
│   │   ├── UserRepositoryInterface.php
│   │   ├── PaymentRepositoryInterface.php
│   │   ├── PanelRepositoryInterface.php
│   │   ├── SubscriptionRepositoryInterface.php
│   │   └── ConfigurationRepositoryInterface.php
│   ├── Events/                       # Domain events
│   │   ├── User/
│   │   │   ├── UserRegistered.php
│   │   │   ├── UserActivated.php
│   │   │   └── UserDeactivated.php
│   │   ├── Payment/
│   │   │   ├── PaymentCreated.php
│   │   │   ├── PaymentCompleted.php
│   │   │   └── PaymentFailed.php
│   │   └── Subscription/
│   │       ├── SubscriptionCreated.php
│   │       ├── SubscriptionExpired.php
│   │       └── SubscriptionRenewed.php
│   └── Exceptions/                   # Domain-specific exceptions
│       ├── UserNotFoundException.php
│       ├── PaymentValidationException.php
│       ├── PanelConnectionException.php
│       └── SubscriptionExpiredException.php
│
├── Application/                      # Application layer (use cases)
│   ├── UseCases/                     # Application use cases
│   │   ├── User/
│   │   │   ├── RegisterUser/
│   │   │   │   ├── RegisterUserCommand.php
│   │   │   │   ├── RegisterUserHandler.php
│   │   │   │   └── RegisterUserResponse.php
│   │   │   ├── AuthenticateUser/
│   │   │   │   ├── AuthenticateUserQuery.php
│   │   │   │   ├── AuthenticateUserHandler.php
│   │   │   │   └── AuthenticateUserResponse.php
│   │   │   └── UpdateUserProfile/
│   │   │       ├── UpdateUserProfileCommand.php
│   │   │       ├── UpdateUserProfileHandler.php
│   │   │       └── UpdateUserProfileResponse.php
│   │   ├── Payment/
│   │   │   ├── CreatePayment/
│   │   │   │   ├── CreatePaymentCommand.php
│   │   │   │   ├── CreatePaymentHandler.php
│   │   │   │   └── CreatePaymentResponse.php
│   │   │   ├── ProcessPayment/
│   │   │   │   ├── ProcessPaymentCommand.php
│   │   │   │   ├── ProcessPaymentHandler.php
│   │   │   │   └── ProcessPaymentResponse.php
│   │   │   └── RefundPayment/
│   │   │       ├── RefundPaymentCommand.php
│   │   │       ├── RefundPaymentHandler.php
│   │   │       └── RefundPaymentResponse.php
│   │   ├── Panel/
│   │   │   ├── CreatePanelUser/
│   │   │   │   ├── CreatePanelUserCommand.php
│   │   │   │   ├── CreatePanelUserHandler.php
│   │   │   │   └── CreatePanelUserResponse.php
│   │   │   ├── SyncPanelUsers/
│   │   │   │   ├── SyncPanelUsersCommand.php
│   │   │   │   ├── SyncPanelUsersHandler.php
│   │   │   │   └── SyncPanelUsersResponse.php
│   │   │   └── TestPanelConnection/
│   │   │       ├── TestPanelConnectionQuery.php
│   │   │       ├── TestPanelConnectionHandler.php
│   │   │       └── TestPanelConnectionResponse.php
│   │   └── Subscription/
│   │       ├── CreateSubscription/
│   │       ├── RenewSubscription/
│   │       └── CancelSubscription/
│   ├── Services/                     # Application services
│   │   ├── CommandBus.php           # Command bus implementation
│   │   ├── QueryBus.php             # Query bus implementation
│   │   ├── EventBus.php             # Event bus implementation
│   │   └── ValidationService.php    # Cross-cutting validation
│   ├── DTOs/                        # Data Transfer Objects
│   │   ├── User/
│   │   │   ├── UserDto.php
│   │   │   ├── UserProfileDto.php
│   │   │   └── UserRegistrationDto.php
│   │   ├── Payment/
│   │   │   ├── PaymentDto.php
│   │   │   ├── PaymentRequestDto.php
│   │   │   └── PaymentResponseDto.php
│   │   └── Panel/
│   │       ├── PanelDto.php
│   │       ├── PanelUserDto.php
│   │       └── PanelConfigDto.php
│   └── Contracts/                   # Application interfaces
│       ├── CommandHandlerInterface.php
│       ├── QueryHandlerInterface.php
│       ├── EventHandlerInterface.php
│       └── ValidationInterface.php
│
├── Infrastructure/                   # External concerns implementation
│   ├── Persistence/                 # Data persistence
│   │   ├── Database/
│   │   │   ├── Migrations/          # Database migrations
│   │   │   ├── Seeders/             # Database seeders
│   │   │   ├── Repositories/        # Repository implementations
│   │   │   │   ├── DatabaseUserRepository.php
│   │   │   │   ├── DatabasePaymentRepository.php
│   │   │   │   ├── DatabasePanelRepository.php
│   │   │   │   └── DatabaseSubscriptionRepository.php
│   │   │   ├── Entities/            # Database entity mappings
│   │   │   │   ├── UserEntity.php
│   │   │   │   ├── PaymentEntity.php
│   │   │   │   └── PanelEntity.php
│   │   │   └── DatabaseManager.php  # Database connection manager
│   │   ├── Cache/
│   │   │   ├── RedisCache.php       # Redis cache implementation
│   │   │   ├── FileCache.php        # File-based cache
│   │   │   └── CacheManager.php     # Cache abstraction
│   │   └── FileSystem/
│   │       ├── LocalFileSystem.php  # Local file operations
│   │       └── CloudFileSystem.php  # Cloud storage operations
│   ├── External/                    # External service integrations
│   │   ├── Payment/
│   │   │   ├── Gateways/            # Payment gateway implementations
│   │   │   │   ├── PayPalGateway.php
│   │   │   │   ├── StripeGateway.php
│   │   │   │   ├── ZarinPalGateway.php
│   │   │   │   └── CryptoGateway.php
│   │   │   ├── PaymentGatewayInterface.php
│   │   │   └── PaymentGatewayFactory.php
│   │   ├── Panel/
│   │   │   ├── Adapters/            # Panel adapter implementations
│   │   │   │   ├── MarzbanAdapter.php
│   │   │   │   ├── XUIAdapter.php
│   │   │   │   ├── MikroTikAdapter.php
│   │   │   │   ├── WireGuardAdapter.php
│   │   │   │   └── SUIAdapter.php
│   │   │   ├── PanelAdapterInterface.php
│   │   │   └── PanelAdapterFactory.php
│   │   ├── Telegram/
│   │   │   ├── TelegramApiClient.php # Telegram API client
│   │   │   ├── WebhookHandler.php    # Webhook processing
│   │   │   └── MessageFormatter.php  # Message formatting
│   │   └── Notification/
│   │       ├── EmailNotification.php
│   │       ├── SmsNotification.php
│   │       └── PushNotification.php
│   ├── Configuration/               # Configuration management
│   │   ├── ConfigurationManager.php
│   │   ├── EnvironmentLoader.php
│   │   └── ConfigurationValidator.php
│   ├── Logging/
│   │   ├── FileLogger.php           # File-based logging
│   │   ├── DatabaseLogger.php       # Database logging
│   │   └── LoggerFactory.php        # Logger factory
│   ├── Security/
│   │   ├── Encryption/
│   │   │   ├── EncryptionService.php
│   │   │   └── HashingService.php
│   │   ├── Authentication/
│   │   │   ├── JwtTokenService.php
│   │   │   └── SessionManager.php
│   │   └── Authorization/
│   │       ├── RoleManager.php
│   │       └── PermissionManager.php
│   ├── Scheduling/
│   │   ├── CronJobManager.php       # Cron job management
│   │   ├── TaskScheduler.php        # Task scheduling
│   │   └── Jobs/                    # Scheduled job implementations
│   │       ├── SyncPanelUsersJob.php
│   │       ├── ProcessExpiringSubscriptionsJob.php
│   │       └── CleanupLogsJob.php
│   └── DependencyInjection/         # DI container and providers
│       ├── Container.php            # DI container implementation
│       ├── ServiceProviders/        # Service provider implementations
│       │   ├── DomainServiceProvider.php
│       │   ├── ApplicationServiceProvider.php
│       │   ├── InfrastructureServiceProvider.php
│       │   └── PresentationServiceProvider.php
│       └── AbstractServiceProvider.php
│
├── Presentation/                    # Presentation layer (UI/API)
│   ├── Http/                       # HTTP presentation
│   │   ├── Controllers/            # HTTP controllers
│   │   │   ├── Api/
│   │   │   │   ├── V1/
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   ├── PaymentController.php
│   │   │   │   │   ├── PanelController.php
│   │   │   │   │   └── SubscriptionController.php
│   │   │   │   └── V2/              # Future API versions
│   │   │   └── Web/
│   │   │       ├── DashboardController.php
│   │   │       ├── AdminController.php
│   │   │       └── WebhookController.php
│   │   ├── Middleware/              # HTTP middleware
│   │   │   ├── AuthenticationMiddleware.php
│   │   │   ├── AuthorizationMiddleware.php
│   │   │   ├── RateLimitMiddleware.php
│   │   │   └── ValidationMiddleware.php
│   │   ├── Requests/               # Request validation
│   │   │   ├── User/
│   │   │   ├── Payment/
│   │   │   └── Panel/
│   │   ├── Resources/              # API resources
│   │   │   ├── User/
│   │   │   ├── Payment/
│   │   │   └── Panel/
│   │   └── Routes/                 # Route definitions
│   │       ├── api.php
│   │       ├── web.php
│   │       └── webhooks.php
│   ├── Telegram/                   # Telegram bot presentation
│   │   ├── Bot/
│   │   │   ├── TelegramBot.php     # Main bot class
│   │   │   ├── WebhookProcessor.php # Webhook processing
│   │   │   └── MessageDispatcher.php # Message routing
│   │   ├── Handlers/               # Message handlers
│   │   │   ├── Commands/           # Command handlers
│   │   │   │   ├── StartCommandHandler.php
│   │   │   │   ├── HelpCommandHandler.php
│   │   │   │   ├── MenuCommandHandler.php
│   │   │   │   └── AdminCommandHandler.php
│   │   │   ├── Callbacks/          # Callback handlers
│   │   │   │   ├── PaymentCallbackHandler.php
│   │   │   │   ├── PanelCallbackHandler.php
│   │   │   │   └── SubscriptionCallbackHandler.php
│   │   │   └── Messages/           # Message handlers
│   │   │       ├── TextMessageHandler.php
│   │   │       ├── PhotoMessageHandler.php
│   │   │       └── DocumentMessageHandler.php
│   │   ├── Keyboards/              # Telegram keyboards
│   │   │   ├── InlineKeyboards/
│   │   │   │   ├── MainMenuKeyboard.php
│   │   │   │   ├── PaymentKeyboard.php
│   │   │   │   └── AdminKeyboard.php
│   │   │   └── ReplyKeyboards/
│   │   │       ├── UserMenuKeyboard.php
│   │   │       └── AdminMenuKeyboard.php
│   │   ├── Messages/               # Message templates
│   │   │   ├── Templates/
│   │   │   │   ├── WelcomeMessage.php
│   │   │   │   ├── PaymentMessage.php
│   │   │   │   └── ErrorMessage.php
│   │   │   └── Formatters/
│   │   │       ├── UserFormatter.php
│   │   │       ├── PaymentFormatter.php
│   │   │       └── PanelFormatter.php
│   │   └── Middleware/             # Telegram middleware
│   │       ├── UserAuthMiddleware.php
│   │       ├── AdminAuthMiddleware.php
│   │       └── RateLimitMiddleware.php
│   ├── Console/                    # CLI presentation
│   │   ├── Commands/               # Console commands
│   │   │   ├── User/
│   │   │   │   ├── CreateUserCommand.php
│   │   │   │   └── ListUsersCommand.php
│   │   │   ├── Panel/
│   │   │   │   ├── SyncPanelCommand.php
│   │   │   │   └── TestPanelCommand.php
│   │   │   ├── Payment/
│   │   │   │   └── ProcessPaymentsCommand.php
│   │   │   └── System/
│   │   │       ├── InstallCommand.php
│   │   │       ├── MigrateCommand.php
│   │   │       └── CacheCommand.php
│   │   ├── Kernel.php              # Console kernel
│   │   └── CommandRegistry.php     # Command registration
│   └── Shared/                     # Shared presentation concerns
│       ├── Responses/              # Response formatters
│       │   ├── JsonResponse.php
│       │   ├── XmlResponse.php
│       │   └── ErrorResponse.php
│       ├── Validators/             # Input validators
│       │   ├── UserValidator.php
│       │   ├── PaymentValidator.php
│       │   └── PanelValidator.php
│       └── Transformers/           # Data transformers
│           ├── UserTransformer.php
│           ├── PaymentTransformer.php
│           └── PanelTransformer.php
│
├── Shared/                         # Shared kernel (cross-cutting concerns)
│   ├── Constants/                  # Application constants
│   │   ├── AppConstants.php        # General app constants
│   │   ├── DatabaseConstants.php   # Database-related constants
│   │   ├── CacheConstants.php      # Cache-related constants
│   │   └── ApiConstants.php        # API-related constants
│   ├── Contracts/                  # Shared interfaces
│   │   ├── CacheInterface.php
│   │   ├── LoggerInterface.php
│   │   ├── EventDispatcherInterface.php
│   │   └── ContainerInterface.php
│   ├── Utils/                      # Utility classes
│   │   ├── StringHelper.php        # String manipulation
│   │   ├── ArrayHelper.php         # Array manipulation
│   │   ├── DateHelper.php          # Date/time utilities
│   │   ├── ValidationHelper.php    # Validation utilities
│   │   └── CryptographyHelper.php  # Cryptography utilities
│   ├── Exceptions/                 # Base exceptions
│   │   ├── BaseException.php       # Base exception class
│   │   ├── ValidationException.php # Validation exceptions
│   │   ├── ServiceException.php    # Service exceptions
│   │   ├── InfrastructureException.php # Infrastructure exceptions
│   │   └── PresentationException.php # Presentation exceptions
│   ├── Traits/                     # Reusable traits
│   │   ├── Timestampable.php       # Timestamp management
│   │   ├── Identifiable.php        # ID management
│   │   ├── Cacheable.php           # Caching behavior
│   │   └── Loggable.php            # Logging behavior
│   └── Events/                     # Base event classes
│       ├── BaseEvent.php           # Base event class
│       ├── EventDispatcher.php     # Event dispatcher
│       └── EventSubscriber.php     # Event subscriber interface
│
├── Config/                         # Configuration files
│   ├── app.php                     # Application configuration
│   ├── database.php                # Database configuration
│   ├── cache.php                   # Cache configuration
│   ├── telegram.php                # Telegram configuration
│   ├── payment.php                 # Payment configuration
│   ├── panel.php                   # Panel configuration
│   ├── logging.php                 # Logging configuration
│   └── security.php                # Security configuration
│
├── Resources/                      # Application resources
│   ├── Views/                      # View templates
│   │   ├── emails/                 # Email templates
│   │   ├── telegram/               # Telegram message templates
│   │   └── web/                    # Web view templates
│   ├── Lang/                       # Localization files
│   │   ├── en/                     # English translations
│   │   ├── fa/                     # Persian translations
│   │   └── ru/                     # Russian translations
│   └── Assets/                     # Static assets
│       ├── css/                    # Stylesheets
│       ├── js/                     # JavaScript files
│       └── images/                 # Image assets
│
├── Storage/                        # Storage directories
│   ├── logs/                       # Application logs
│   ├── cache/                      # File cache storage
│   ├── uploads/                    # User uploads
│   └── temp/                       # Temporary files
│
├── Tests/                          # Test suite
│   ├── Unit/                       # Unit tests
│   │   ├── Domain/                 # Domain layer tests
│   │   ├── Application/            # Application layer tests
│   │   ├── Infrastructure/         # Infrastructure layer tests
│   │   └── Shared/                 # Shared component tests
│   ├── Integration/                # Integration tests
│   │   ├── Database/               # Database integration tests
│   │   ├── External/               # External service tests
│   │   └── Api/                    # API integration tests
│   ├── Feature/                    # Feature tests
│   │   ├── User/                   # User feature tests
│   │   ├── Payment/                # Payment feature tests
│   │   └── Panel/                  # Panel feature tests
│   ├── Fixtures/                   # Test fixtures
│   └── Helpers/                    # Test helpers
│
├── Scripts/                        # Utility scripts
│   ├── install.php                 # Installation script
│   ├── migrate.php                 # Database migration script
│   ├── seed.php                    # Database seeding script
│   └── deploy.php                  # Deployment script
│
├── bootstrap.php                   # Application bootstrap
├── composer.json                   # Composer dependencies
├── phpunit.xml                     # PHPUnit configuration
├── .env.example                    # Environment variables example
└── README.md                       # Project documentation
```

## Architectural Patterns and Principles

### 1. Clean Architecture
- **Dependency Rule**: Dependencies point inward toward the domain
- **Layer Isolation**: Each layer has clear responsibilities
- **Interface Segregation**: Small, focused interfaces

### 2. Domain-Driven Design (DDD)
- **Aggregate Roots**: User, Payment, Panel, Subscription
- **Value Objects**: Money, UserId, Email, etc.
- **Domain Services**: Business logic that doesn't belong to entities
- **Repository Pattern**: Data access abstraction

### 3. CQRS (Command Query Responsibility Segregation)
- **Commands**: State-changing operations
- **Queries**: Data retrieval operations
- **Handlers**: Separate handlers for commands and queries

### 4. Event-Driven Architecture
- **Domain Events**: Business events within the domain
- **Event Handlers**: React to domain events
- **Event Sourcing**: Optional for audit trails

## Naming Conventions

### File Naming
- **Classes**: PascalCase (e.g., `UserService.php`)
- **Interfaces**: PascalCase with "Interface" suffix (e.g., `UserRepositoryInterface.php`)
- **Traits**: PascalCase with descriptive names (e.g., `Timestampable.php`)
- **Configuration**: snake_case (e.g., `database.php`)

### Directory Naming
- **Directories**: PascalCase for namespaces (e.g., `Domain/`, `Application/`)
- **Subdirectories**: PascalCase for logical grouping (e.g., `UseCases/`, `ValueObjects/`)

### Class Naming
- **Entities**: Singular nouns (e.g., `User`, `Payment`)
- **Value Objects**: Descriptive nouns (e.g., `Money`, `Email`)
- **Services**: Noun + "Service" (e.g., `PaymentService`)
- **Repositories**: Entity + "Repository" (e.g., `UserRepository`)
- **Commands**: Verb + Noun + "Command" (e.g., `CreateUserCommand`)
- **Handlers**: Command/Query + "Handler" (e.g., `CreateUserHandler`)

## Module Dependencies

### Dependency Flow
```
Presentation → Application → Domain ← Infrastructure
                ↓
            Shared (Kernel)
```

### Layer Dependencies
1. **Domain Layer**: No external dependencies (pure business logic)
2. **Application Layer**: Depends only on Domain
3. **Infrastructure Layer**: Implements Domain interfaces
4. **Presentation Layer**: Depends on Application and Domain interfaces
5. **Shared Layer**: Used by all layers for common utilities

### Module Relationships
- **User Module**: Core entity, referenced by Payment and Subscription
- **Payment Module**: Depends on User, independent of Panel
- **Panel Module**: Depends on User, independent of Payment
- **Subscription Module**: Depends on User, Payment, and Panel

## Migration Strategy

### Phase 1: Foundation (Week 1-2)
1. Create new directory structure
2. Move shared utilities and constants
3. Implement base classes and interfaces
4. Set up dependency injection container

### Phase 2: Domain Layer (Week 3-4)
1. Create domain entities and value objects
2. Implement domain services
3. Define repository interfaces
4. Create domain events

### Phase 3: Application Layer (Week 5-6)
1. Implement use cases with CQRS pattern
2. Create DTOs and application services
3. Set up command and query buses
4. Implement event handling

### Phase 4: Infrastructure Layer (Week 7-8)
1. Implement repository concrete classes
2. Create external service adapters
3. Set up caching and logging
4. Implement security services

### Phase 5: Presentation Layer (Week 9-10)
1. Refactor Telegram bot handlers
2. Create HTTP API controllers
3. Implement console commands
4. Set up middleware and validation

### Phase 6: Testing and Optimization (Week 11-12)
1. Write comprehensive tests
2. Performance optimization
3. Documentation updates
4. Deployment preparation

## Benefits of Proposed Structure

### 1. Maintainability
- Clear separation of concerns
- Easy to locate and modify code
- Reduced coupling between components

### 2. Scalability
- Modular architecture supports growth
- Easy to add new features and integrations
- Horizontal scaling capabilities

### 3. Testability
- Dependency injection enables easy mocking
- Clear boundaries for unit testing
- Isolated components for integration testing

### 4. Flexibility
- Pluggable architecture for external services
- Easy to swap implementations
- Support for multiple presentation layers

### 5. Code Quality
- Consistent naming conventions
- Clear architectural boundaries
- Reduced technical debt

## Implementation Guidelines

### 1. Start Small
- Implement one module at a time
- Use feature flags for gradual rollout
- Maintain backward compatibility during transition

### 2. Follow SOLID Principles
- Single Responsibility Principle
- Open/Closed Principle
- Liskov Substitution Principle
- Interface Segregation Principle
- Dependency Inversion Principle

### 3. Use Design Patterns Appropriately
- Factory Pattern for object creation
- Strategy Pattern for algorithms
- Observer Pattern for events
- Repository Pattern for data access
- Command Pattern for operations

### 4. Maintain Documentation
- Update architecture documentation
- Document design decisions
- Maintain API documentation
- Create developer guides

## Conclusion

This proposed structure addresses the current architectural issues while providing a solid foundation for future growth. The clean separation of concerns, consistent naming conventions, and well-defined dependencies will significantly improve code maintainability, testability, and scalability.

The migration should be done incrementally to minimize disruption to the existing system while gradually improving the codebase quality and architectural integrity.