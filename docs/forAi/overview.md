# BotMirzaPanel – AI Reference Overview

This project is a Telegram bot that integrates with multiple VPN panels to sell VPN configurations inside the bot, manage user accounts/configs, and automate payment and provisioning workflows end‑to‑end.

## High‑Level Architecture
- Presentation layer: Telegram bot handlers (messages, commands, callbacks), optional Web/API/Console entry points.
- Application layer: Orchestrates use cases (PaymentService, PanelService, UserService, CronService).
- Domain layer: Business logic services, repository interfaces, value objects, and domain events.
- Infrastructure layer: Concrete adapters (panels, gateways), repositories, database/cache/logging utilities, and service providers (DI bindings).
- Shared utils: Constants, contracts, exceptions, helpers.

## Key Components
- TelegramBot
  - Wraps Telegram API and webhook processing.
  - Dispatches update payloads to handlers: MessageHandler, CommandHandler, CallbackHandler.
  - Sends/edits/deletes messages, forwards, answers callbacks, sends media, and queries chat member info.
- PanelService
  - Factory/facade for various VPN panel adapters (Marzban, MikroTik, X‑UI, S‑UI, WireGuard).
  - Core operations: create/update/delete user, get user/config/stats, enable/disable user, test connection, get status, list configured panels.
  - Reads panel configurations, configures adapter per request, and logs operations to DB.
- PaymentService
  - Strategy for multiple payment gateways.
  - createPayment: validates input, generates unique orderId, inserts into Payment_report, calls gateway createPayment, updates record with transaction id/data, returns payment URL.
  - processCallback: verifies callback with gateway, updates status, stores callback payload, and on success triggers provisioning logic.
  - Provides queries: getPaymentByOrderId/Id, getUserPayments, aggregated stats.
- Database & Repositories
  - DatabaseManager provides basic CRUD and query builder methods.
  - BaseRepository to share data access patterns.
  - Infrastructure repositories (UserRepository, PaymentRepository) implement domain interfaces (currently with stubs to be filled progressively).
- Dependency Injection (Service Providers)
  - InfrastructureServiceProvider: binds ConfigManager, DatabaseManager, Cache/Redis, Logger, and repository interfaces → concrete repositories.
  - DomainServiceProvider: binds domain services (UserDomainService, PaymentDomainService) and event dispatcher.
  - ApplicationServiceProvider: binds app services (PaymentService, PanelService, TelegramBot, CronService, etc.).

## Data Model Notes
- Payments table: Payment_report (used throughout PaymentService and repos). Typical fields include id, user_id, order_id, amount, currency, gateway, status, created_at, completed_at, description, callback_url, payment_url, gateway_transaction_id, gateway_data, callback_data, error_message.
- Users table: assumed by services/repos; user attributes are stored by the app and mirrored/provisioned on panels.

## Typical Flows
- Purchase flow
  1) User initiates purchase in Telegram.
  2) Bot calls PaymentService::createPayment with gateway and order info.
  3) Payment gateway returns payment URL; user pays.
  4) Gateway callback handled by PaymentService::processCallback.
  5) On completed status, provisioning is triggered (PanelService).
- Provisioning flow
  1) PanelService selects appropriate adapter based on configured panelId/type.
  2) Adapter is configured from stored panel settings.
  3) Adapter creates/enables user and returns configuration (file/link) and status.
- Telegram interaction flow
  - TelegramBot::processUpdate dispatches to handlers for commands (e.g., start, buy, help), messages, and inline callbacks to guide users through purchasing and managing configs.

## Configuration
- ConfigManager centralizes settings.
- Telegram: telegram.api_key is required.
- Panels: per‑panel configs (enabled, type, url/name, auth/secrets) are loaded and injected into adapters.
- Payment gateways: settings include enable flags, credentials, currency/amount limits, and callback URLs.
- Redis (optional): initialized from ConfigManager when enabled for caching/queues.

## Extending the System
- Add a new panel type
  - Implement PanelAdapterInterface with: configure, create/update/delete user, getUser/getUserConfig/getUserStats, enable/disable, testConnection, getCapabilities.
  - Register the adapter in PanelService initialization and add its config schema.
- Add a new payment gateway
  - Implement PaymentGatewayInterface with: getDisplayName/Description/SupportedCurrencies/limits, createPayment, verifyCallback.
  - Register the gateway and add configuration, including callback route.
- Add repository behavior
  - Define methods in Domain\Repositories interfaces, then implement them in Infrastructure\Repositories and bind in InfrastructureServiceProvider.
- Extend Telegram features
  - Add commands/callbacks in Telegram\Handlers; keep UX flows short and resilient to failures/timeouts.

## Operational Notes
- Entry points in project root: index.php, botapi.php (Telegram webhook), admin.php, panels.php, plus cron scripts under /cron and legacy payment endpoints under /payment.
- Webhook route should call TelegramBot::processUpdate with necessary services from the container.
- Logging and caching provided via Infrastructure services; do not emit secrets in logs.

## Known Gaps / TODOs (as of current codebase)
- Domain entities (e.g., User, Payment) aren’t explicitly modeled as rich objects; repositories return arrays. Consider introducing explicit entities where helpful.
- PaymentRepository/UserRepository contain stubs; progressively implement methods actually used by Domain services first.
- Some domain services expect Value Objects (e.g., UserId, Money) while current usage passes primitives. Align types across services, interfaces, and repositories.
- Ensure consistent schema for Payment_report and users across all queries and migrations.

## Directory Primer
- src/Telegram: TelegramBot and handlers for message/command/callback.
- src/Panel: PanelService and adapters for Marzban, MikroTik, X‑UI, S‑UI, WireGuard.
- src/Payment: PaymentService and payment gateway interfaces/implementations.
- src/Domain: Services, Repositories (interfaces), ValueObjects, Events.
- src/Infrastructure: Providers (DI), Repositories (concrete), Logging, Cache/Redis, External integrations.
- src/Database: DatabaseManager and BaseRepository.
- src/User, src/Cron, src/Presentation: User service, scheduled jobs, and any UI/API endpoints.

This overview is designed to help the AI reason about the project quickly, map features to layers, and make safe, incremental changes that align with the architecture.