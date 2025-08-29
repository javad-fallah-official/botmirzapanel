BotMirzaPanel — AI Handbook (Working Document)

Purpose
- This is a self-contained, detailed guide to help an AI reason about, modify, and extend this project safely and effectively.
- Short description: A Telegram bot that connects to VPN panels to sell VPN configs directly inside the bot, manage them (create/enable/disable/delete), and automate payment → provisioning workflows end-to-end.

1) System Overview
- Layers
  - Presentation: Telegram handlers (Message/Command/Callback), optional Web/API/Console frontends.
  - Application: High-level use cases (PaymentService, PanelService, UserService, CronService).
  - Domain: Business logic (Domain Services), Repository interfaces, Value Objects, Domain Events.
  - Infrastructure: Concrete adapters (panels, gateways), Repositories, Database/Cache/Logging utilities, Service Providers (DI bindings).
  - Shared: Constants, Contracts, Exceptions, Helpers.
- Runtime
  - Bootstrap wires PSR-4 autoloading, providers, container, global helpers (config/db/telegram/panel/payment/etc.).
  - Entry points (root): index.php, botapi.php (Telegram webhook), admin.php, panels.php; legacy payment endpoints under /payment; scheduled tasks in /cron.

2) Core Components (What they do)
- TelegramBot
  - Wraps Telegram API; processes webhook updates and routes to handlers.
  - Sends/edits/deletes messages, sends media, answers callbacks, forwards, getChatMember.
- PanelService
  - Facade + factory over panel adapters (Marzban, MikroTik, X-UI, S-UI, WireGuard).
  - Operations: create/update/delete user, get user/config/stats, enable/disable, test connection, list configured panels, panel status checks.
  - Reads panel configs, configures adapter per request, logs operations to DB.
- PaymentService
  - Strategy over multiple payment gateways; maintains gateway registry/settings.
  - createPayment: validate → generate unique order_id → insert Payment_report → call gateway.createPayment → update record with gateway data → return payment_url.
  - processCallback: verify with gateway → update status and audit data → if completed, trigger provisioning.
  - Queries: getPaymentByOrderId/Id, getUserPayments, aggregated stats.
- Database/Repositories
  - DatabaseManager offers CRUD + query interface.
  - BaseRepository shared data access patterns. Infrastructure repositories implement domain interfaces; some methods are stubbed awaiting incremental implementation.
- DI Providers
  - InfrastructureServiceProvider: binds Config, Database, Cache/Redis, Logger, Repository interfaces → implementations.
  - DomainServiceProvider: binds domain services and event dispatcher.
  - ApplicationServiceProvider: binds PaymentService, PanelService, TelegramBot, CronService, etc.

3) Data Model (practical view)
- Payment_report (main payments table)
  - Key fields: id, user_id, order_id, amount, currency, gateway, status, created_at, completed_at, description, callback_url, payment_url, gateway_transaction_id, gateway_data (JSON), callback_data (JSON), error_message.
- Users table
  - Used by services/repos for user accounts, balances, referrals, roles/status; exact schema inferred by code; ensure consistency across queries.

4) Key Flows
- Purchase → Provisioning
  1) User initiates purchase via Telegram flow.
  2) PaymentService::createPayment creates db record + gateway session → returns payment_url.
  3) Gateway callback hits processCallback; verification updates status.
  4) If completed, provisioning uses PanelService to create/enable the VPN user and return config.
- Telegram Interaction
  - TelegramBot::processUpdate inspects update → routes to CommandHandler for /commands or MessageHandler otherwise; CallbackHandler for inline keyboards.
  - Flows guide user through buy/manage/config retrieval.
- Panel Operations
  - PanelService picks adapter by panelId/type, configures it from stored settings, performs action, logs results.

5) Configuration (essentials)
- ConfigManager provides typed access.
- Telegram: telegram.api_key required.
- Payment Gateways: enable flags, credentials/keys, currency + min/max amounts, callback URLs.
- Panels: per-panel entries with enabled, type (marzban/mikrotik/xui/sui/wireguard), url/name, and credentials.
- Redis/Cache: optional; connection details read from config.

6) Extensibility
- Add a panel type
  - Implement PanelAdapterInterface: configure, create/update/delete user, getUser/getUserConfig/getUserStats, enable/disable, testConnection, getCapabilities.
  - Register in PanelService initialization + config schema.
- Add a gateway
  - Implement PaymentGatewayInterface: meta (display name/desc/currencies/limits), createPayment, verifyCallback.
  - Register in PaymentService and expose settings + callback route.
- Add repository features
  - Add methods to Domain\Repositories interfaces → implement in Infrastructure\Repositories → bind in InfrastructureServiceProvider.
- Extend Telegram UX
  - Add handlers under Telegram\Handlers; keep steps minimal, handle failures/timeouts gracefully.

7) Dependency Graph (simplified)
- TelegramBot → uses ConfigManager; depends on handlers (Message/Command/Callback).
- PanelService → uses ConfigManager + DatabaseManager + Adapters (Marzban/MikroTik/X-UI/S-UI/WireGuard).
- PaymentService → uses ConfigManager + DatabaseManager + Gateways; writes to Payment_report.
- Domain services → depend on Repository interfaces (UserRepositoryInterface, PaymentRepositoryInterface).
- Repositories → depend on DatabaseManager.
- Providers → compose the above in the container.

8) Operational Runbook (for local/server)
- Ensure PHP >= 7.4 and required extensions (curl, json, pdo, mysqli, openssl, mbstring).
- Configure telegram.api_key and gateway/panel credentials.
- Set webhook (botapi.php endpoint) to route updates to TelegramBot::processUpdate via container.
- Confirm DB connectivity; make sure Payment_report and users schemas exist and match usage.
- Cron jobs in /cron handle periodic tasks (expiry checks, notifications, cleanups).
- Logs under /logs; cache under /cache; errors are handled by bootstrap error handlers.

9) Security & Privacy Notes
- Never log secrets/keys/PII. Scrub sensitive fields in error logs and gateway/panel traces.
- Validate and sanitize all user inputs from Telegram and callbacks.
- Use HTTPS for webhook and callbacks; verify gateway signatures strictly.

10) Current Gaps / TODOs (snapshot)
- Align Domain Services and Repository interfaces on types (e.g., Value Objects vs primitives, Money handling).
- Implement concrete methods in UserRepository and PaymentRepository that are currently stubs (start with those used by domain services).
- Consider introducing explicit domain entities for User/Payment to reduce array-shaped data and improve invariants.
- Ensure Payment_report and users schema consistency; add migrations or schema docs.

11) Codebase Map (quick)
- src/Telegram: TelegramBot + handlers.
- src/Panel: PanelService + adapters.
- src/Payment: PaymentService + gateways.
- src/Domain: Services, Repositories (interfaces), ValueObjects, Events.
- src/Infrastructure: Providers (DI), Repositories (concrete), Logging, Cache/Redis.
- src/Database: DatabaseManager, BaseRepository.
- Root: index.php, botapi.php, admin.php, panels.php; /cron scripts; /payment legacy endpoints.

12) Conventions
- PSR-4: BotMirzaPanel\ mapped to src/; PHP >= 7.4.
- Keep methods side-effect-aware; return arrays for data rows unless/ until entities are introduced.
- Use providers for DI; avoid new-ing dependencies directly in application/domain code.

Reading order
- Start with this AI Handbook, then see docs/forAi/overview.md for a compact summary.