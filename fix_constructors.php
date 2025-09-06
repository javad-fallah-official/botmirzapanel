<?php

/**
 * Script to fix constructor return type declarations in PHP files
 * This removes ': void' return type declarations from constructors
 */

$directory = __DIR__ . '/src';
$pattern = '/public\s+function\s+__construct\s*\([^)]*\)\s*:\s*void/i';
$replacement = 'public function __construct($1)';

$count = 0;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        
        // Check if the file contains a constructor with void return type
        if (preg_match($pattern, $content)) {
            // Replace the constructor declaration
            $newContent = preg_replace($pattern, $replacement, $content);
            
            // Write the modified content back to the file
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                $count++;
                echo "Fixed constructor in: {$path}\n";
            }
        }
    }
}

echo "\nFixed constructors in {$count} files.\n";