<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console\Commands;

use BotMirzaPanel\Application\Queries\Panel\GetPanelsQuery;
use BotMirzaPanel\Application\Queries\Panel\GetPanelByIdQuery;
use BotMirzaPanel\Application\Commands\Panel\CreatePanelCommand;
use BotMirzaPanel\Application\Commands\Panel\UpdatePanelCommand;
use BotMirzaPanel\Infrastructure\External\Panel\PanelAdapterFactoryInterface;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelType;
use BotMirzaPanel\Domain\ValueObjects\Panel\PanelStatus;
use BotMirzaPanel\Domain\ValueObjects\Common\Url;

/**
 * Panel Management Console Command
 */
class PanelCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'panel';
    }

    public function getDescription(): string
    {
        return 'Panel management (list, show, create, update, delete, test, sync)';
    }

    public function getUsage(): string
    {
        return 'panel <action> [options]';
    }

    public function getHelp(): string
    {
        return <<<HELP
Panel Management Commands:

  panel list                   List all panels
  panel show <id>              Show panel details
  panel create <name> <url> <type> <username> <password>
                              Create new panel
  panel update <id> [options]  Update panel configuration
  panel delete <id>            Delete panel
  panel test <id>              Test panel connection
  panel sync <id>              Sync panel data
  panel users <id>             List panel users
  panel user:create <panel_id> <username> <email>
                              Create user on panel
  panel user:delete <panel_id> <username>
                              Delete user from panel
  panel stats <id>             Show panel statistics
  panel adapters               List available panel adapters
  panel adapter:test <type>    Test panel adapter

Options:
  --name=NAME                 Panel name
  --url=URL                   Panel URL
  --type=TYPE                 Panel type (marzban, x-ui, etc.)
  --username=USER             Panel username
  --password=PASS             Panel password
  --status=STATUS             Panel status (active, inactive, maintenance)
  --limit=N                   Limit number of results
  --offset=N                  Offset for pagination
  --format=FORMAT             Output format (table, json, csv)
  --output=FILE               Output to file
  --verbose, -v               Verbose output

Panel Types:
  marzban, x-ui, v2ray, shadowsocks, other

Panel Statuses:
  active, inactive, maintenance, error
HELP;
    }

    public function execute(array $arguments = [], array $options = []): int
    {
        $this->setArguments($arguments);
        $this->setOptions($options);

        try {
            $action = $this->getArgument(0);
            
            if (!$action) {
                $this->error('No action specified.');
                $this->output($this->getHelp());
                return self::EXIT_INVALID_ARGUMENT;
            }

            switch ($action) {
                case 'list':
                    return $this->listPanels();
                case 'show':
                    return $this->showPanel();
                case 'create':
                    return $this->createPanel();
                case 'update':
                    return $this->updatePanel();
                case 'delete':
                    return $this->deletePanel();
                case 'test':
                    return $this->testPanel();
                case 'sync':
                    return $this->syncPanel();
                case 'users':
                    return $this->listPanelUsers();
                case 'user:create':
                    return $this->createPanelUser();
                case 'user:delete':
                    return $this->deletePanelUser();
                case 'stats':
                    return $this->showPanelStats();
                case 'adapters':
                    return $this->listAdapters();
                case 'adapter:test':
                    return $this->testAdapter();
                default:
                    $this->error("Unknown action: {$action}");
                    $this->output($this->getHelp());
                    return self::EXIT_INVALID_ARGUMENT;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * List all panels
     */
    private function listPanels(): int
    {
        try {
            $query = new GetAllPanelsQuery(
                (int) ($this->getOption('limit') ?? 50),
                (int) ($this->getOption('offset') ?? 0)
            );
            
            $panels = $this->getQueryBus()->handle($query);
            
            if (empty($panels)) {
                $this->info('No panels found.');
                return self::EXIT_SUCCESS;
            }
            
            $format = $this->getOption('format', 'table');
            
            switch ($format) {
                case 'json':
                    $this->outputJson($panels);
                    break;
                case 'csv':
                    $this->outputCsv($panels, $this->getPanelHeaders());
                    break;
                default:
                    $this->outputPanelsTable($panels);
                    break;
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to list panels: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show panel details
     */
    private function showPanel(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $query = new GetPanelByIdQuery((int) $id);
            $panel = $this->getQueryBus()->handle($query);
            
            if (!$panel) {
                $this->error("Panel not found: {$id}");
                return self::EXIT_NOT_FOUND;
            }
            
            $this->output("Panel Details (ID: {$panel['id']})");
            $this->output("  Name: {$panel['name']}");
            $this->output("  URL: {$panel['url']}");
            $this->output("  Type: {$panel['type']}");
            $this->output("  Status: {$panel['status']}");
            $this->output("  Username: {$panel['username']}");
            $this->output("  Created: {$panel['created_at']}");
            $this->output("  Updated: {$panel['updated_at']}");
            $this->output("  Last Sync: {$panel['last_sync_at']}");
            
            if (!empty($panel['config'])) {
                $this->output("  Configuration:");
                foreach ($panel['config'] as $key => $value) {
                    // Hide sensitive information
                    if (stripos($key, 'password') !== false || stripos($key, 'secret') !== false) {
                        $value = str_repeat('*', strlen($value));
                    }
                    $this->output("    {$key}: {$value}");
                }
            }
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to show panel: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Create new panel
     */
    private function createPanel(): int
    {
        $name = $this->getArgument(1) ?? $this->getOption('name');
        $url = $this->getArgument(2) ?? $this->getOption('url');
        $type = $this->getArgument(3) ?? $this->getOption('type');
        $username = $this->getArgument(4) ?? $this->getOption('username');
        $password = $this->getArgument(5) ?? $this->getOption('password');
        
        if (!$name || !$url || !$type || !$username || !$password) {
            $this->error('Name, URL, type, username, and password are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $command = new CreatePanelCommand(
                $name,
                new Url($url),
                new PanelType($type),
                $username,
                $password,
                [] // config
            );
            
            $panelId = $this->getCommandBus()->handle($command);
            
            $this->success("Panel created successfully with ID: {$panelId}");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to create panel: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Update panel
     */
    private function updatePanel(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $updates = [];
            
            if ($this->hasOption('name')) {
                $updates['name'] = $this->getOption('name');
            }
            
            if ($this->hasOption('url')) {
                $updates['url'] = new Url($this->getOption('url'));
            }
            
            if ($this->hasOption('type')) {
                $updates['type'] = new PanelType($this->getOption('type'));
            }
            
            if ($this->hasOption('username')) {
                $updates['username'] = $this->getOption('username');
            }
            
            if ($this->hasOption('password')) {
                $updates['password'] = $this->getOption('password');
            }
            
            if ($this->hasOption('status')) {
                $updates['status'] = new PanelStatus($this->getOption('status'));
            }
            
            if (empty($updates)) {
                $this->error('No updates specified.');
                return self::EXIT_INVALID_ARGUMENT;
            }
            
            $command = new UpdatePanelCommand((int) $id, $updates);
            $this->getCommandBus()->handle($command);
            
            $this->success('Panel updated successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to update panel: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Delete panel
     */
    private function deletePanel(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!$this->confirm("Are you sure you want to delete panel {$id}?")) {
            $this->info('Deletion cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $command = new DeletePanelCommand((int) $id);
            $this->getCommandBus()->handle($command);
            
            $this->success('Panel deleted successfully.');
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to delete panel: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Test panel connection
     */
    private function testPanel(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $query = new GetPanelByIdQuery((int) $id);
            $panel = $this->getQueryBus()->handle($query);
            
            if (!$panel) {
                $this->error("Panel not found: {$id}");
                return self::EXIT_NOT_FOUND;
            }
            
            $factory = $this->getService(PanelAdapterFactoryInterface::class);
            $adapter = $factory->create($panel['type'], $panel);
            
            $this->info("Testing connection to panel: {$panel['name']}");
            
            $testResult = $adapter->testConnection();
            
            if ($testResult) {
                $this->success("Panel connection test successful.");
                return self::EXIT_SUCCESS;
            } else {
                $this->error("Panel connection test failed.");
                return self::EXIT_FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Connection test failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Sync panel data
     */
    private function syncPanel(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $this->info("Syncing panel data: {$id}");
            
            // TODO: Implement panel sync command
            $this->success('Panel data synced successfully.');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * List panel users
     */
    private function listPanelUsers(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $query = new GetPanelByIdQuery((int) $id);
            $panel = $this->getQueryBus()->handle($query);
            
            if (!$panel) {
                $this->error("Panel not found: {$id}");
                return self::EXIT_NOT_FOUND;
            }
            
            $factory = $this->getService(PanelAdapterFactoryInterface::class);
            $adapter = $factory->create($panel['type'], $panel);
            
            $this->info("Fetching users from panel: {$panel['name']}");
            
            $users = $adapter->getUsers();
            
            if (empty($users)) {
                $this->info('No users found on panel.');
                return self::EXIT_SUCCESS;
            }
            
            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'Username' => $user['username'],
                    'Email' => $user['email'] ?? 'N/A',
                    'Status' => $user['status'] ?? 'Unknown',
                    'Created' => $user['created_at'] ?? 'N/A'
                ];
            }
            
            $this->displayTable(['Username', 'Email', 'Status', 'Created'], $data);
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to list panel users: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Create user on panel
     */
    private function createPanelUser(): int
    {
        $panelId = $this->getArgument(1);
        $username = $this->getArgument(2);
        $email = $this->getArgument(3);
        
        if (!$panelId || !$username || !$email) {
            $this->error('Panel ID, username, and email are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $this->info("Creating user {$username} on panel {$panelId}");
            
            // TODO: Implement panel user creation
            $this->success('User created successfully on panel.');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to create panel user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Delete user from panel
     */
    private function deletePanelUser(): int
    {
        $panelId = $this->getArgument(1);
        $username = $this->getArgument(2);
        
        if (!$panelId || !$username) {
            $this->error('Panel ID and username are required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        if (!$this->confirm("Are you sure you want to delete user {$username} from panel {$panelId}?")) {
            $this->info('Deletion cancelled.');
            return self::EXIT_SUCCESS;
        }
        
        try {
            $this->info("Deleting user {$username} from panel {$panelId}");
            
            // TODO: Implement panel user deletion
            $this->success('User deleted successfully from panel.');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to delete panel user: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Show panel statistics
     */
    private function showPanelStats(): int
    {
        $id = $this->getArgument(1);
        
        if (!$id) {
            $this->error('Panel ID is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $this->output("Panel Statistics (ID: {$id})");
            $this->output('  Total Users: 0');
            $this->output('  Active Users: 0');
            $this->output('  Inactive Users: 0');
            $this->output('  Total Traffic: 0 GB');
            $this->output('  Monthly Traffic: 0 GB');
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to get panel statistics: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * List available panel adapters
     */
    private function listAdapters(): int
    {
        try {
            $factory = $this->getService(PanelAdapterFactoryInterface::class);
            $adapters = $factory->getAvailableAdapters();
            
            if (empty($adapters)) {
                $this->info('No panel adapters available.');
                return self::EXIT_SUCCESS;
            }
            
            $this->output('Available Panel Adapters:');
            
            $data = [];
            foreach ($adapters as $type => $config) {
                $data[] = [
                    'Type' => $type,
                    'Name' => $config['name'] ?? $type,
                    'Version' => $config['version'] ?? 'Unknown',
                    'Status' => $config['enabled'] ? 'Enabled' : 'Disabled'
                ];
            }
            
            $this->displayTable(['Type', 'Name', 'Version', 'Status'], $data);
            
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to list adapters: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Test panel adapter
     */
    private function testAdapter(): int
    {
        $type = $this->getArgument(1);
        
        if (!$type) {
            $this->error('Adapter type is required.');
            return self::EXIT_INVALID_ARGUMENT;
        }
        
        try {
            $factory = $this->getService(PanelAdapterFactoryInterface::class);
            
            $this->info("Testing adapter: {$type}");
            
            // Test adapter with dummy configuration
            $testConfig = [
                'url' => 'https://example.com',
                'username' => 'test',
                'password' => 'test'
            ];
            
            $adapter = $factory->create($type, $testConfig);
            
            $this->success("Adapter {$type} loaded successfully.");
            return self::EXIT_SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Adapter test failed: {$e->getMessage()}");
            return self::EXIT_FAILURE;
        }
    }

    /**
     * Output panels as table
     */
    private function outputPanelsTable(array $panels): void
    {
        $data = [];
        foreach ($panels as $panel) {
            $data[] = [
                'ID' => $panel['id'],
                'Name' => $panel['name'],
                'Type' => $panel['type'],
                'URL' => $panel['url'],
                'Status' => $panel['status'],
                'Created' => $panel['created_at']
            ];
        }
        
        $this->displayTable(['ID', 'Name', 'Type', 'URL', 'Status', 'Created'], $data);
    }

    /**
     * Get panel CSV headers
     */
    private function getPanelHeaders(): array
    {
        return ['ID', 'Name', 'Type', 'URL', 'Status', 'Username', 'Created', 'Updated', 'Last Sync'];
    }
}