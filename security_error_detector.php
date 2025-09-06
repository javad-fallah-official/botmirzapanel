<?php
/**
 * Security Error Detection Tool
 * Focuses on identifying critical security vulnerabilities
 */

class SecurityErrorDetector
{
    private array $securityErrors = [];
    private int $errorCount = 0;

    public function analyzeProject(string $srcDir = 'src'): void
    {
        echo "Security Vulnerability Analysis\n";
        echo "==============================\n\n";
        
        $this->scanDirectory($srcDir);
        $this->generateSecurityReport();
    }

    private function scanDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->analyzeFileForSecurity($file->getPathname());
            }
        }
    }

    private function analyzeFileForSecurity(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        // 1. SQL Injection Vulnerabilities
        $this->checkSQLInjection($filePath, $content, $lines);
        
        // 2. Hardcoded Secrets/Tokens
        $this->checkHardcodedSecrets($filePath, $content, $lines);
        
        // 3. XSS Vulnerabilities
        $this->checkXSSVulnerabilities($filePath, $content, $lines);
        
        // 4. File Upload Vulnerabilities
        $this->checkFileUploadSecurity($filePath, $content, $lines);
        
        // 5. Authentication/Authorization Issues
        $this->checkAuthSecurity($filePath, $content, $lines);
    }

    private function checkSQLInjection(string $filePath, string $content, array $lines): void
    {
        // Direct string concatenation in SQL queries
        $patterns = [
            '/\$.*\s*\.\s*["\'][^"\']*(SELECT|INSERT|UPDATE|DELETE)/i',
            '/".*\$.*".*\s*(SELECT|INSERT|UPDATE|DELETE)/i',
            '/\'.*\$.*\'.*\s*(SELECT|INSERT|UPDATE|DELETE)/i',
            '/(SELECT|INSERT|UPDATE|DELETE).*\$[^;]*[^?]/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $this->addSecurityError('SQL_INJECTION', $filePath, $lineNum, 
                        'Potential SQL injection: Direct variable concatenation in query');
                }
            }
        }
        
        // Check for missing prepared statements
        if (preg_match('/(mysqli_query|mysql_query|pg_query)\s*\([^)]*\$/', $content)) {
            $this->addSecurityError('SQL_INJECTION', $filePath, 0, 
                'Using direct query execution instead of prepared statements');
        }
    }

    private function checkHardcodedSecrets(string $filePath, string $content, array $lines): void
    {
        foreach ($lines as $lineNum => $line) {
            // API keys, tokens, passwords
            $patterns = [
                '/["\'][a-zA-Z0-9]{32,}["\']/' => 'Potential hardcoded API key/token',
                '/password\s*=\s*["\'][^"\'\/\s]{8,}["\']/' => 'Hardcoded password',
                '/secret\s*=\s*["\'][^"\'\/\s]{16,}["\']/' => 'Hardcoded secret',
                '/token\s*=\s*["\'][^"\'\/\s]{20,}["\']/' => 'Hardcoded token',
                '/key\s*=\s*["\'][^"\'\/\s]{16,}["\']/' => 'Hardcoded key'
            ];
            
            foreach ($patterns as $pattern => $message) {
                if (preg_match($pattern, $line)) {
                    $this->addSecurityError('HARDCODED_SECRET', $filePath, $lineNum + 1, $message);
                }
            }
        }
    }

    private function checkXSSVulnerabilities(string $filePath, string $content, array $lines): void
    {
        // Direct output of user input without escaping
        $patterns = [
            '/echo\s+\$_(GET|POST|REQUEST)\[/' => 'Direct output of user input (XSS risk)',
            '/print\s+\$_(GET|POST|REQUEST)\[/' => 'Direct output of user input (XSS risk)',
            '/\?>.*\$_(GET|POST|REQUEST)\[/' => 'Direct output in HTML context (XSS risk)'
        ];
        
        foreach ($patterns as $pattern => $message) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $this->addSecurityError('XSS', $filePath, $lineNum, $message);
                }
            }
        }
    }

    private function checkFileUploadSecurity(string $filePath, string $content, array $lines): void
    {
        // File upload without proper validation
        if (preg_match('/\$_FILES/', $content)) {
            if (!preg_match('/(mime_content_type|finfo_file|getimagesize)/', $content)) {
                $this->addSecurityError('FILE_UPLOAD', $filePath, 0, 
                    'File upload without proper MIME type validation');
            }
            
            if (!preg_match('/(pathinfo|basename).*PATHINFO_EXTENSION/', $content)) {
                $this->addSecurityError('FILE_UPLOAD', $filePath, 0, 
                    'File upload without extension validation');
            }
        }
    }

    private function checkAuthSecurity(string $filePath, string $content, array $lines): void
    {
        // Missing authentication checks in controllers
        if (strpos($filePath, 'Controller') !== false) {
            if (!preg_match('/(session_start|\$_SESSION|authenticate|authorize|checkAuth)/', $content)) {
                $this->addSecurityError('AUTH', $filePath, 0, 
                    'Controller missing authentication checks');
            }
        }
        
        // Weak session configuration
        if (preg_match('/session_start\(\)/', $content)) {
            if (!preg_match('/session_regenerate_id/', $content)) {
                $this->addSecurityError('AUTH', $filePath, 0, 
                    'Missing session ID regeneration (session fixation risk)');
            }
        }
    }

    private function addSecurityError(string $type, string $file, int $line, string $message): void
    {
        $this->errorCount++;
        $this->securityErrors[] = [
            'type' => $type,
            'file' => $file,
            'line' => $line,
            'message' => $message,
            'severity' => $this->getSeverity($type)
        ];
        
        $relativePath = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $file);
        $severity = $this->getSeverity($type);
        echo "🚨 SECURITY ERROR #{$this->errorCount} [{$severity}] {$type} in {$relativePath}";
        if ($line > 0) {
            echo " (line {$line})";
        }
        echo ":\n   {$message}\n\n";
    }

    private function getSeverity(string $type): string
    {
        $severityMap = [
            'SQL_INJECTION' => 'CRITICAL',
            'XSS' => 'HIGH',
            'HARDCODED_SECRET' => 'HIGH',
            'FILE_UPLOAD' => 'MEDIUM',
            'AUTH' => 'MEDIUM'
        ];
        
        return $severityMap[$type] ?? 'LOW';
    }

    private function generateSecurityReport(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "SECURITY VULNERABILITY SUMMARY\n";
        echo str_repeat('=', 60) . "\n";
        echo "Total security issues found: {$this->errorCount}\n\n";
        
        if ($this->errorCount === 0) {
            echo "🎉 NO SECURITY VULNERABILITIES FOUND!\n";
        } else {
            // Group by type and severity
            $byType = [];
            $bySeverity = [];
            
            foreach ($this->securityErrors as $error) {
                $byType[$error['type']][] = $error;
                $bySeverity[$error['severity']][] = $error;
            }
            
            echo "Vulnerabilities by type:\n";
            foreach ($byType as $type => $errors) {
                echo "- {$type}: " . count($errors) . " issues\n";
            }
            
            echo "\nVulnerabilities by severity:\n";
            foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'] as $severity) {
                if (isset($bySeverity[$severity])) {
                    echo "- {$severity}: " . count($bySeverity[$severity]) . " issues\n";
                }
            }
            
            echo "\n🚨 {$this->errorCount} SECURITY VULNERABILITIES NEED IMMEDIATE ATTENTION!\n";
        }
    }
}

// Run the security analysis
$detector = new SecurityErrorDetector();
$detector->analyzeProject();