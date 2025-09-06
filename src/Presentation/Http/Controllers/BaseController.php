<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Http\Controllers;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Shared\Exceptions\ValidationException;
use BotMirzaPanel\Shared\Exceptions\DomainException;

/**
 * Base Controller
 * 
 * Provides common functionality for all HTTP controllers
 */
abstract class BaseController
{
    protected ServiceContainer $container;

    public function __construct()
    {
        $this->container = $container;
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): array
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        return [
            'status' => $statusCode < 400 ? 'success' : 'error',
            'data' => $data,
            'timestamp' => date('c')
        ];
    }

    /**
     * Return success response
     */
    protected function success(array $data = [], string $message = 'Success'): array
    {
        return $this->json([
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Return error response
     */
    protected function error(string $message, int $statusCode = 400, array $errors = []): array
    {
        return $this->json([
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required validation
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field][] = "The {$field} field is required.";
                continue;
            }
            
            // Skip other validations if field is empty and not required
            if (empty($value)) {
                continue;
            }
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;
                    case 'integer':
                        if (!is_numeric($value) || (int)$value != $value) {
                            $errors[$field][] = "The {$field} must be an integer.";
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = "The {$field} must be a string.";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field][] = "The {$field} must be at least {$rule['min_length']} characters.";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field][] = "The {$field} may not be greater than {$rule['max_length']} characters.";
            }
            
            // Custom validation
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customResult = $rule['custom']($value);
                if ($customResult !== true) {
                    $errors[$field][] = is_string($customResult) ? $customResult : "The {$field} is invalid.";
                }
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
        
        return $data;
    }

    /**
     * Get request data
     */
    protected function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }
        
        return array_merge($_GET, $_POST);
    }

    /**
     * Handle exceptions and return appropriate response
     */
    protected function handleException(\Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            return $this->error($e->getMessage(), 422, $e->getErrors());
        }
        
        if ($e instanceof DomainException) {
            return $this->error($e->getMessage(), 400);
        }
        
        // Log unexpected errors
        error_log("Controller Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        
        return $this->error('Internal server error', 500);
    }

    /**
     * Get authenticated user ID from session/token
     */
    protected function getAuthenticatedUserId(): ?int
    {
        // Implementation depends on authentication mechanism
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): int
    {
        $userId = $this->getAuthenticatedUserId();
        
        if (!$userId) {
            http_response_code(401);
            echo json_encode($this->error('Authentication required', 401));
            exit;
        }
        
        return $userId;
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission): bool
    {
        // Implementation depends on permission system
        return true; // Placeholder
    }

    /**
     * Require specific permission
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            echo json_encode($this->error('Insufficient permissions', 403));
            exit;
        }
    }
}