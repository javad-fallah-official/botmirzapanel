<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Console\Commands;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;

/**
 * Base Console Command
 * 
 * Provides common functionality for all console commands
 */
abstract class BaseCommand
{
    protected ServiceContainer $container;
    protected array $arguments = [];
    protected array $options = [];

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Execute the command
     */
    abstract public function execute(array $arguments = [], array $options = []): int;

    /**
     * Get command name
     */
    abstract public function getName(): string;

    /**
     * Get command description
     */
    abstract public function getDescription(): string;

    /**
     * Get command usage
     */
    public function getUsage(): string
    {
        return $this->getName();
    }

    /**
     * Get command help
     */
    public function getHelp(): string
    {
        return $this->getDescription();
    }

    /**
     * Set arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * Set options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Get argument by name
     */
    protected function getArgument(string $name, $default = null)
    {
        return $this->arguments[$name] ?? $default;
    }

    /**
     * Get option by name
     */
    protected function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Check if option exists
     */
    protected function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Output message to console
     */
    protected function output(string $message, bool $newline = true): void
    {
        echo $message . ($newline ? "\n" : '');
    }

    /**
     * Output info message
     */
    protected function info(string $message): void
    {
        $this->output("[INFO] {$message}");
    }

    /**
     * Output success message
     */
    protected function success(string $message): void
    {
        $this->output("[SUCCESS] {$message}");
    }

    /**
     * Output warning message
     */
    protected function warning(string $message): void
    {
        $this->output("[WARNING] {$message}");
    }

    /**
     * Output error message
     */
    protected function error(string $message): void
    {
        $this->output("[ERROR] {$message}");
    }

    /**
     * Ask user for input
     */
    protected function ask(string $question, string $default = ''): string
    {
        $this->output($question . ($default ? " [{$default}]" : '') . ': ', false);
        $input = trim(fgets(STDIN));
        return $input ?: $default;
    }

    /**
     * Ask user for confirmation
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $answer = $this->ask("{$question} [{$defaultText}]");
        
        if (empty($answer)) {
            return $default;
        }
        
        return in_array(strtolower($answer), ['y', 'yes', '1', 'true']);
    }

    /**
     * Ask user to choose from options
     */
    protected function choice(string $question, array $choices, string $default = ''): string
    {
        $this->output($question);
        foreach ($choices as $key => $choice) {
            $this->output("  [{$key}] {$choice}");
        }
        
        do {
            $answer = $this->ask('Please choose', $default);
            if (isset($choices[$answer])) {
                return $answer;
            }
            $this->error('Invalid choice. Please try again.');
        } while (true);
    }

    /**
     * Display table
     */
    protected function table(array $headers, array $rows): void
    {
        if (empty($rows)) {
            $this->output('No data to display.');
            return;
        }
        
        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen($header);
        }
        
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen((string) $cell));
            }
        }
        
        // Display header
        $this->displayTableRow($headers, $widths);
        $this->displayTableSeparator($widths);
        
        // Display rows
        foreach ($rows as $row) {
            $this->displayTableRow($row, $widths);
        }
    }

    /**
     * Display table row
     */
    private function displayTableRow(array $row, array $widths): void
    {
        $output = '|';
        foreach ($row as $i => $cell) {
            $output .= ' ' . str_pad((string) $cell, $widths[$i]) . ' |';
        }
        $this->output($output);
    }

    /**
     * Display table separator
     */
    private function displayTableSeparator(array $widths): void
    {
        $output = '+';
        foreach ($widths as $width) {
            $output .= str_repeat('-', $width + 2) . '+';
        }
        $this->output($output);
    }

    /**
     * Display progress bar
     */
    protected function progressBar(int $current, int $total, int $width = 50): void
    {
        $percentage = ($current / $total) * 100;
        $progress = (int) (($current / $total) * $width);
        
        $bar = '[' . str_repeat('=', $progress) . str_repeat(' ', $width - $progress) . ']';
        $this->output("\r{$bar} {$percentage}% ({$current}/{$total})", false);
        
        if ($current === $total) {
            $this->output(''); // New line when complete
        }
    }

    /**
     * Validate required arguments
     */
    protected function validateRequiredArguments(array $required): bool
    {
        foreach ($required as $arg) {
            if (!isset($this->arguments[$arg])) {
                $this->error("Required argument '{$arg}' is missing.");
                return false;
            }
        }
        return true;
    }

    /**
     * Parse command line arguments
     */
    public static function parseArguments(array $argv): array
    {
        $arguments = [];
        $options = [];
        
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (strpos($arg, '--') === 0) {
                // Long option
                $option = substr($arg, 2);
                if (strpos($option, '=') !== false) {
                    [$key, $value] = explode('=', $option, 2);
                    $options[$key] = $value;
                } else {
                    $options[$option] = true;
                }
            } elseif (strpos($arg, '-') === 0) {
                // Short option
                $option = substr($arg, 1);
                $options[$option] = true;
            } else {
                // Argument
                $arguments[] = $arg;
            }
        }
        
        return [$arguments, $options];
    }

    /**
     * Get service from container
     */
    protected function getService(string $serviceClass)
    {
        return $this->container->get($serviceClass);
    }

    /**
     * Handle exceptions
     */
    protected function handleException(\Exception $e): int
    {
        $this->error("Command failed: " . $e->getMessage());
        
        if ($this->hasOption('verbose') || $this->hasOption('v')) {
            $this->output("Stack trace:");
            $this->output($e->getTraceAsString());
        }
        
        return 1;
    }

    /**
     * Exit codes
     */
    const EXIT_SUCCESS = 0;
    const EXIT_FAILURE = 1;
    const EXIT_INVALID_ARGUMENT = 2;
    const EXIT_PERMISSION_DENIED = 3;
    const EXIT_NOT_FOUND = 4;
}