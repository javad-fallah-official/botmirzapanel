Architecture Deep Dive

1. Layers
- Presentation
  - TelegramBot and handlers under src/Telegram.
  - Optional Presentation\Api/Console/Web scaffolding present for future expansion.
- Application
  - PaymentService (src/Payment/PaymentService.php): orchestrates payment flows and gateway interactions.
  - PanelService (src/Panel/PanelService.php): orchestrates panel operations via adapters.
  - UserService (src/User/UserService.php): user-related orchestration (balance, profile, referrals, etc.).
  - CronService (src/Cron/CronService.php): scheduled tasks.
- Domain
  - Services: src/Domain/Services/*DomainService.php.
  - Repositories: src/Domain/Repositories/*RepositoryInterface.php.
  - ValueObjects: src/Domain/ValueObjects (Email, Money, UserId).
  - Events: src/Domain/Events.
- Infrastructure
  - Providers: src/Infrastructure/Providers (container bindings).
  - Repositories: src/Infrastructure/Repositories (DB implementations).
  - Integrations: Logging, Cache/Redis, External services.
- Database
  - DatabaseManager + BaseRepository in src/Database.

2. Dependency Injection & Bootstrap
- src/bootstrap.php sets up container, registers providers, boots them, and exposes global helpers (app/config/db/telegram/panel/payment/etc.).
- InfrastructureServiceProvider binds ConfigManager, DatabaseManager, Cache/Redis, Logger, and repository interfaces to concrete implementations.
- DomainServiceProvider binds domain services and lightweight EventDispatcherInterface.
- ApplicationServiceProvider binds application-level services (TelegramBot, PaymentService, PanelService, CronService, etc.).

3. Payment Flow (internals)
- createPayment
  - Validates gateway + amount constraints from config.
  - Generates unique order_id and callback URL.
  - Inserts payment row into Payment_report, obtains ID.
  - Calls gateway->createPayment($record) and updates row with gateway_transaction_id, gateway_data, payment_url.
- processCallback
  - Verifies gateway signature/data.
  - Updates payment row status and audit fields.
  - On success, triggers provisioning (PanelService) to enable/create user and provide config.
- Stats and queries
  - Aggregations by gateway/status, totals/averages.

4. Panel Flow (internals)
- getConfiguredPanels returns enabled panels and their capabilities by querying adapter->getCapabilities().
- User operations delegated to the adapter after adapter->configure($panelConfig).
- Operations are logged via PanelService::logPanelOperation.

5. Telegram Flow (internals)
- TelegramBot::processUpdate inspects update payload:
  - message with leading '/' → CommandHandler
  - message otherwise → MessageHandler
  - callback_query → CallbackHandler
- Supports messages, media, edits, deletes, forwards, callback answers, getChatMember.

6. Persistence
- Payment_report table provides audit trail for payments.
- Users table for bot users and VPN accounts mapping; schema consistency required.
- Repositories encapsulate DB access for domain services; base methods in BaseRepository, raw queries via DatabaseManager when needed.

7. Extensibility Notes
- Prefer introducing new behavior via interfaces (PanelAdapterInterface, PaymentGatewayInterface) and binding implementations in services.
- Keep domain services repository-interface-driven; implement methods in Infrastructure repositories and wire via provider.
- Add migrations/schema docs for any new tables/columns, keeping Payment_report consistent.

8. Known Technical Debts
- Some repository methods are stubs; implement incrementally.
- Domain service type alignment (Value Objects vs primitives, Money calculations) needs consolidation.
- Domain entities (User/Payment) could be introduced for stronger invariants.

9. Environment & Requirements
- PHP >= 7.4, ext: curl, json, pdo, mysqli, openssl, mbstring.
- PSR-4 autoload: BotMirzaPanel\ → src/.
- Timezone defaults to Asia/Tehran in bootstrap.

10. Entry Points & Scripts
- Root scripts: index.php, botapi.php (Telegram webhook), admin.php, panels.php.
- Cron tasks under /cron (e.g., expiration cleanup/notifications).
- Legacy payment gateway endpoints under /payment/*.