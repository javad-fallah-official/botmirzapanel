<?php

echo "PHP Syntax Verification Script\n";
echo "==============================\n\n";

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

echo "Found " . count($phpFiles) . " PHP files to check\n\n";

// Check each file
foreach ($phpFiles as $file) {
    $checkedCount++;
    $relativePath = str_replace(__DIR__ . '/', '', $file);
    
    // Use php -l to check syntax
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "❌ ERROR in {$relativePath}:\n";
        echo "   " . implode("\n   ", $output) . "\n\n";
        $errorCount++;
    } else {
        echo "✅ {$relativePath}\n";
    }
}

echo "\n==============================\n";
echo "VERIFICATION COMPLETE\n";
echo "Files checked: {$checkedCount}\n";
echo "Errors found: {$errorCount}\n";

if ($errorCount === 0) {
    echo "\n🎉 ALL FILES PASSED SYNTAX VALIDATION!\n";
    exit(0);
} else {
    echo "\n⚠️  SYNTAX ERRORS DETECTED!\n";
    exit(1);
}