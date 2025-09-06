<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console;

use BotMirzaPanel\Presentation\Console\Commands\BaseCommand;
use BotMirzaPanel\Presentation\Console\Commands\UserCommand;
use BotMirzaPanel\Presentation\Console\Commands\PaymentCommand;
use BotMirzaPanel\Presentation\Console\Commands\PanelCommand;
use BotMirzaPanel\Presentation\Console\Commands\TelegramCommand;
use BotMirzaPanel\Presentation\Console\Commands\DatabaseCommand;
use BotMirzaPanel\Infrastructure\Container\ServiceContainer;

/**
 * Console Application
 * 
 * Main entry point for console commands
 */
class ConsoleApplication
{
    private ServiceContainer $container;
    private array $commands = [];
    
    public function __construct()
    {
        $this->container = $container;
        $this->registerCommands();
    }
    
    /**
     * Register available commands
     */
    private function registerCommands(): void
    {
        $this->commands = [
            'user' => UserCommand::class,
            'payment' => PaymentCommand::class,
            'panel' => PanelCommand::class,
            'telegram' => TelegramCommand::class,
            'database' => DatabaseCommand::class,
        ];
    }
    
    /**
     * Run console application
     */
    public function run(array $argv = []): int
    {
        try {
            // Remove script name from arguments
            array_shift($argv);
            
            if (empty($argv)) {
                $this->showHelp();
                return BaseCommand::EXIT_SUCCESS;
            }
            
            $commandName = array_shift($argv);
            
            // Handle global options
            if (in_array($commandName, ['--help', '-h', 'help'])) {
                $this->showHelp();
                return BaseCommand::EXIT_SUCCESS;
            }
            
            if (in_array($commandName, ['--version', '-v', 'version'])) {
                $this->showVersion();
                return BaseCommand::EXIT_SUCCESS;
            }
            
            if (!isset($this->commands[$commandName])) {
                $this->error("Unknown command: {$commandName}");
                $this->showHelp();
                return BaseCommand::EXIT_INVALID_ARGUMENT;
            }
            
            // Parse arguments and options
            $arguments = [];
            $options = [];
            
            foreach ($argv as $arg) {
                if (strpos($arg, '--') === 0) {
                    // Long option
                    $parts = explode('=', substr($arg, 2), 2);
                    $options[$parts[0]] = $parts[1] ?? true;
                } elseif (strpos($arg, '-') === 0 && strlen($arg) > 1) {
                    // Short option
                    $option = substr($arg, 1);
                    if (strlen($option) === 1) {
                        $options[$option] = true;
                    } else {
                        // Multiple short options
                        for ($i = 0; $i < strlen($option); $i++) {
                            $options[$option[$i]] = true;
                        }
                    }
                } else {
                    // Argument
                    $arguments[] = $arg;
                }
            }
            
            // Create and execute command
            $commandClass = $this->commands[$commandName];
            $command = new $commandClass();
            
            // Inject container if command extends BaseCommand
            if ($command instanceof BaseCommand) {
                $command->setContainer($this->container);
            }
            
            return $command->execute($arguments, $options);
            
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return BaseCommand::EXIT_FAILURE;
        }
    }
    
    /**
     * Show help message
     */
    private function showHelp(): void
    {
        $this->output("BotMirzaPanel Console Application\n");
        $this->output("Usage: php console.php <command> [options] [arguments]\n");
        $this->output("Available Commands:");
        
        foreach ($this->commands as $name => $class) {
            $command = new $class();
            $description = method_exists($command, 'getDescription') ? $command->getDescription() : 'No description';
            $this->output(sprintf("  %-15s %s", $name, $description));
        }
        
        $this->output("\nGlobal Options:");
        $this->output("  --help, -h      Show this help message");
        $this->output("  --version, -v   Show version information");
        $this->output("\nFor command-specific help, use: php console.php <command> --help");
    }
    
    /**
     * Show version information
     */
    private function showVersion(): void
    {
        $this->output("BotMirzaPanel Console Application v1.0.0");
    }
    
    /**
     * Output message to console
     */
    private function output(string $message): void
    {
        echo $message . PHP_EOL;
    }
    
    /**
     * Output error message to console
     */
    private function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
    
    /**
     * Get available commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
    
    /**
     * Add custom command
     */
    public function addCommand(string $name, string $commandClass): void
    {
        $this->commands[$name] = $commandClass;
    }
    
    /**
     * Remove command
     */
    public function removeCommand(string $name): void
    {
        unset($this->commands[$name]);
    }
}