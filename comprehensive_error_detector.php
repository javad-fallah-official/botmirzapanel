<?php
/**
 * Comprehensive Error Detection Tool
 * Detects syntax, logic, architectural, and code quality issues
 */

class ComprehensiveErrorDetector
{
    private array $errors = [];
    private int $errorCount = 0;
    private array $checkedFiles = [];

    public function analyzeProject(string $srcDir = 'src'): void
    {
        echo "Comprehensive PHP Code Analysis\n";
        echo "==============================\n\n";
        
        $this->scanDirectory($srcDir);
        $this->generateReport();
    }

    private function scanDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->analyzeFile($file->getPathname());
            }
        }
    }

    private function analyzeFile(string $filePath): void
    {
        $this->checkedFiles[] = $filePath;
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        // 1. Syntax Check
        $this->checkSyntax($filePath);
        
        // 2. Code Quality Issues
        $this->checkCodeQuality($filePath, $content, $lines);
        
        // 3. Architecture Issues
        $this->checkArchitecture($filePath, $content, $lines);
        
        // 4. Type Safety Issues
        $this->checkTypeSafety($filePath, $content, $lines);
        
        // 5. Best Practices
        $this->checkBestPractices($filePath, $content, $lines);
    }

    private function checkSyntax(string $filePath): void
    {
        $output = [];
        $returnCode = 0;
        exec("php -l \"$filePath\" 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->addError('SYNTAX', $filePath, 0, implode(' ', $output));
        }
    }

    private function checkCodeQuality(string $filePath, string $content, array $lines): void
    {
        // Check for missing docblocks
        if (preg_match('/class\s+\w+/', $content) && !preg_match('/\/\*\*.*?\*\//s', $content)) {
            $this->addError('QUALITY', $filePath, 0, 'Missing class documentation');
        }
        
        // Check for long methods (>80 lines) - relaxed threshold to avoid false positives
        $inMethod = false;
        $methodStart = 0;
        $braceCount = 0;
        
        foreach ($lines as $lineNum => $line) {
            if (preg_match('/function\s+\w+/', $line)) {
                $inMethod = true;
                $methodStart = $lineNum + 1;
                $braceCount = 0;
            }
            
            if ($inMethod) {
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                if ($braceCount === 0 && $lineNum > $methodStart) {
                    if (($lineNum - $methodStart) > 80) {
                        $this->addError('QUALITY', $filePath, $methodStart, 'Method too long (>' . ($lineNum - $methodStart) . ' lines)');
                    }
                    $inMethod = false;
                }
            }
        }
        
        // Check for unused variables (heuristic): only flag if variable is never referenced again
        preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=/', $content, $assignments);
        preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\b/', $content, $usages);
        
        $assigned = array_unique($assignments[1]);
        $used = array_unique($usages[1]);
        $unused = array_diff($assigned, $used);
        
        foreach ($unused as $var) {
            if (!in_array($var, ['this', '_']) && strpos($content, '$' . $var) !== false) {
                $this->addError('QUALITY', $filePath, 0, "Potentially unused variable: \$$var");
            }
        }
    }

    private function checkArchitecture(string $filePath, string $content, array $lines): void
    {
        // Skip interfaces and abstract classes
        if (preg_match('/\binterface\b|\babstract\s+class\b/i', $content)) {
            return;
        }
        
        // Only flag concrete Service classes missing interface implementation
        if (preg_match('/class\s+(\w+)/', $content, $m)) {
            $className = $m[1];
            $isServiceClass = (strpos($className, 'Service') !== false) || (strpos($filePath, DIRECTORY_SEPARATOR . 'Service') !== false);
            $isInterfaceFile = (stripos($filePath, 'Interface.php') !== false);
            $isExceptionFile = (stripos($filePath, 'Exception.php') !== false);
            
            if ($isServiceClass && !$isInterfaceFile && !$isExceptionFile) {
                if (!preg_match('/implements\s+\w+/', $content)) {
                    $this->addError('ARCHITECTURE', $filePath, 0, 'Service class should implement an interface');
                }
            }
        }
        
        // Check for direct database access in controllers
        if (strpos($filePath, 'Controller') !== false && preg_match('/new\s+PDO|mysqli_/i', $content)) {
            $this->addError('ARCHITECTURE', $filePath, 0, 'Controller should not access database directly');
        }
        
        // Check for missing dependency injection (constructor without dependencies)
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            if (strpos($className, 'Controller') !== false || strpos($className, 'Service') !== false) {
                if (!preg_match('/function\s+__construct\s*\([^)]*\$[a-zA-Z_]/', $content)) {
                    $this->addError('ARCHITECTURE', $filePath, 0, 'Missing dependency injection in constructor');
                }
            }
        }
    }

    private function checkTypeSafety(string $filePath, string $content, array $lines): void
    {
        // Check for missing return types
        preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*(?!:)\s*\{/', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
            if (!preg_match('/\b(__construct|__destruct|__invoke)\b/', $match[0])) {
                $this->addError('TYPE_SAFETY', $filePath, $lineNum, 'Missing return type declaration');
            }
        }
        
        // Check for missing parameter types (per-parameter analysis)
        if (preg_match_all('/function\s+\w+\s*\(([^)]*)\)/', $content, $paramMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($paramMatches[1] as $pm) {
                $paramsStr = trim($pm[0]);
                if ($paramsStr === '') { continue; }
                $params = array_map('trim', explode(',', $paramsStr));
                foreach ($params as $p) {
                    // Skip variadic and by-reference checks for simplicity
                    // Consider typed if it starts with a type (including union and nullable) before the variable name
                    if (!preg_match('/^(?:\??[a-zA-Z_\\][a-zA-Z0-9_\\|]*)\s+\$[a-zA-Z_][a-zA-Z0-9_]*\s*(?:=|$)/', $p)) {
                        $lineNum = substr_count(substr($content, 0, $pm[1]), "\n") + 1;
                        $this->addError('TYPE_SAFETY', $filePath, $lineNum, 'Missing parameter type hints');
                        break; // Only report once per signature
                    }
                }
            }
        }
    }

    private function checkBestPractices(string $filePath, string $content, array $lines): void
    {
        // Check for hardcoded values
        foreach ($lines as $lineNum => $line) {
            $trim = trim($line);
            // Skip comments
            if (str_starts_with($trim, '//') || str_starts_with($trim, '#') || str_starts_with($trim, '/*') || str_starts_with($trim, '*')) {
                continue;
            }
            // Hardcoded URL found (skip obvious config arrays and annotations)
            if (preg_match('/["\']\s*(?:http:\/\/|https:\/\/|localhost|127\.0\.0\.1)/', $line) && !preg_match('/\b(route|routes|config|allowedHosts)\b/i', $line)) {
                $this->addError('BEST_PRACTICES', $filePath, $lineNum + 1, 'Hardcoded URL found');
            }
            
            // Potential hardcoded secret/token only when assigned to known keys/vars
            if (preg_match('/\b(password|api[_-]?key|secret|token)\b\s*[:=]\s*["\']\s*[^"\']{8,}["\']/', $line)) {
                $this->addError('BEST_PRACTICES', $filePath, $lineNum + 1, 'Potential hardcoded secret/token');
            }
        }
        
        // Check for SQL injection vulnerabilities (very rough)
        if (preg_match('/\$\w+\s*->\s*query\s*\(\s*\".*\$.*(SELECT|INSERT|UPDATE|DELETE)/i', $content) ||
            preg_match('/mysqli_query\s*\(.*\$.*\)/i', $content)) {
            $this->addError('SECURITY', $filePath, 0, 'Potential SQL injection vulnerability');
        }
        
        // Check for missing error handling for external operations
        if (preg_match('/\b(file_get_contents|curl_exec|json_decode)\b/', $content) && !preg_match('/\btry\s*\{|@|===\s*false|json_last_error\(\)/', $content)) {
            $this->addError('BEST_PRACTICES', $filePath, 0, 'Missing error handling for external operations');
        }
    }

    private function addError(string $type, string $file, int $line, string $message): void
    {
        $this->errorCount++;
        $this->errors[] = [
            'type' => $type,
            'file' => $file,
            'line' => $line,
            'message' => $message
        ];
        
        $relativePath = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $file);
        echo "❌ ERROR #{$this->errorCount} [{$type}] in {$relativePath}";
        if ($line > 0) {
            echo " (line {$line})";
        }
        echo ":\n   {$message}\n\n";
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "COMPREHENSIVE ANALYSIS SUMMARY\n";
        echo str_repeat('=', 50) . "\n";
        echo "Files checked: " . count($this->checkedFiles) . "\n";
        echo "Total errors found: {$this->errorCount}\n\n";
        
        if ($this->errorCount === 0) {
            echo "🎉 NO ERRORS FOUND!\n";
        } else {
            // Group errors by type
            $errorsByType = [];
            foreach ($this->errors as $error) {
                $errorsByType[$error['type']][] = $error;
            }
            
            echo "Error breakdown by type:\n";
            foreach ($errorsByType as $type => $errors) {
                echo "- {$type}: " . count($errors) . " errors\n";
            }
            
            echo "\n⚠️  {$this->errorCount} TOTAL ERRORS NEED TO BE ADDRESSED!\n";
        }
    }
}

// Run the analysis
$detector = new ComprehensiveErrorDetector();
$detector->analyzeProject();