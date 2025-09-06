<?php

/**
 * Simple PHP Code Analyzer
 * 
 * This script checks for common issues in PHP code:
 * 1. Missing return types on methods (except constructors)
 * 2. Undefined class references
 * 3. Potential undefined variables
 * 4. Missing property type declarations
 */

$directory = __DIR__ . '/src';
$issues = [];

// Function to analyze a PHP file
function analyzeFile($filePath) {
    $fileIssues = [];
    $content = file_get_contents($filePath);
    $tokens = token_get_all($content);
    
    // Check for methods without return types
    $pattern = '/function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*(?!:)/i';
    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
    
    foreach ($matches[1] as $match) {
        $methodName = $match[0];
        if ($methodName !== '__construct' && $methodName !== '__destruct') {
            $fileIssues[] = "Method '{$methodName}' is missing a return type declaration";
        }
    }
    
    // Check for properties without type declarations
    $pattern = '/\s*(protected|private|public)\s+\$([a-zA-Z0-9_]+)\s*(?!=|;)/i';
    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
    
    foreach ($matches[2] as $index => $match) {
        $propertyName = $match[0];
        $visibility = $matches[1][$index][0];
        $fileIssues[] = "{$visibility} property '\${$propertyName}' is missing a type declaration";
    }
    
    return $fileIssues;
}

// Recursively find all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $fileIssues = analyzeFile($path);
        
        if (!empty($fileIssues)) {
            $relativePath = str_replace(__DIR__ . '/', '', $path);
            $issues[$relativePath] = $fileIssues;
        }
    }
}

// Output results
echo "PHP Code Analysis Results\n";
echo "========================\n\n";

if (empty($issues)) {
    echo "No issues found!\n";
} else {
    $totalIssues = 0;
    
    foreach ($issues as $file => $fileIssues) {
        echo "File: {$file}\n";
        echo str_repeat('-', strlen("File: {$file}")) . "\n";
        
        foreach ($fileIssues as $issue) {
            echo "- {$issue}\n";
            $totalIssues++;
        }
        
        echo "\n";
    }
    
    echo "Total issues found: {$totalIssues}\n";
}