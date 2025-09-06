<?php

echo "PHP Syntax Error Detection\n";
echo "=========================\n\n";

$errorCount = 0;
$checkedCount = 0;

// Function to recursively find PHP files
function findPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Find all PHP files in src directory
$phpFiles = findPhpFiles(__DIR__ . '/src');

echo "Checking " . count($phpFiles) . " PHP files for syntax errors...\n\n";

// Check each file - only show errors
foreach ($phpFiles as $file) {
    $checkedCount++;
    $relativePath = str_replace(__DIR__ . '/', '', $file);
    
    // Use php -l to check syntax
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        $errorNumber = $errorCount + 1;
        echo "❌ ERROR #{$errorNumber} in {$relativePath}:\n";
        echo "   " . implode("\n   ", $output) . "\n\n";
        $errorCount++;
    }
}

echo "==============================\n";
echo "SYNTAX ERROR SUMMARY\n";
echo "Files checked: {$checkedCount}\n";
echo "Errors found: {$errorCount}\n";

if ($errorCount === 0) {
    echo "\n🎉 NO SYNTAX ERRORS FOUND!\n";
    exit(0);
} else {
    echo "\n⚠️  {$errorCount} SYNTAX ERRORS NEED TO BE FIXED!\n";
    exit(1);
}