<?php

declare(strict_types=1);

namespace BotMirzaPanel\Presentation\Web;

use BotMirzaPanel\Infrastructure\Container\ServiceContainer;
use BotMirzaPanel\Presentation\Http\Controllers\BaseController;

/**
 * Web Router
 * 
 * Handles HTTP routing for web requests
 */
class Router
{
    private ServiceContainer $container;
    private array $routes = [];
    private array $middleware = [];
    
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        $this->registerRoutes();
    }
    
    /**
     * Register application routes
     */
    private function registerRoutes(): void
    {
        // API Routes
        $this->group('/api', function() {
            // User routes
            $this->get('/users', 'UserController@index');
            $this->get('/users/{id}', 'UserController@show');
            $this->post('/users', 'UserController@store');
            $this->put('/users/{id}', 'UserController@update');
            $this->delete('/users/{id}', 'UserController@destroy');
            $this->get('/users/stats', 'UserController@stats');
            
            // Payment routes
            $this->get('/payments', 'PaymentController@index');
            $this->get('/payments/{id}', 'PaymentController@show');
            $this->post('/payments', 'PaymentController@store');
            $this->post('/payments/callback', 'PaymentController@callback');
            $this->get('/payments/stats', 'PaymentController@stats');
            $this->get('/payment-gateways', 'PaymentController@gateways');
            $this->post('/payment-gateways/{gateway}/test', 'PaymentController@testGateway');
        });
        
        // Webhook routes
        $this->post('/webhook/telegram', 'TelegramController@webhook');
        $this->post('/webhook/payment/{gateway}', 'PaymentController@webhook');
        
        // Web routes
        $this->get('/', 'WebController@index');
        $this->get('/admin', 'WebController@admin');
        $this->get('/login', 'WebController@login');
        $this->post('/login', 'WebController@authenticate');
        $this->post('/logout', 'WebController@logout');
    }
    
    /**
     * Handle HTTP request
     */
    public function handle(string $method, string $uri): array
    {
        $method = strtoupper($method);
        $uri = $this->normalizeUri($uri);
        
        // Find matching route
        $route = $this->findRoute($method, $uri);
        
        if (!$route) {
            return $this->notFound();
        }
        
        try {
            // Execute middleware
            foreach ($route['middleware'] as $middlewareClass) {
                $middleware = new $middlewareClass($this->container);
                $result = $middleware->handle();
                if ($result !== true) {
                    return $result;
                }
            }
            
            // Execute controller action
            return $this->executeController($route['controller'], $route['action'], $route['params']);
            
        } catch (\Exception $e) {
            return $this->error('Internal Server Error', 500, $e->getMessage());
        }
    }
    
    /**
     * Add GET route
     */
    public function get(string $path, string $controller): void
    {
        $this->addRoute('GET', $path, $controller);
    }
    
    /**
     * Add POST route
     */
    public function post(string $path, string $controller): void
    {
        $this->addRoute('POST', $path, $controller);
    }
    
    /**
     * Add PUT route
     */
    public function put(string $path, string $controller): void
    {
        $this->addRoute('PUT', $path, $controller);
    }
    
    /**
     * Add DELETE route
     */
    public function delete(string $path, string $controller): void
    {
        $this->addRoute('DELETE', $path, $controller);
    }
    
    /**
     * Add PATCH route
     */
    public function patch(string $path, string $controller): void
    {
        $this->addRoute('PATCH', $path, $controller);
    }
    
    /**
     * Add route with middleware
     */
    public function addRoute(string $method, string $path, string $controller, array $middleware = []): void
    {
        $path = $this->normalizeUri($path);
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'middleware' => array_merge($this->middleware, $middleware),
            'pattern' => $this->pathToPattern($path)
        ];
    }
    
    /**
     * Group routes with common prefix and middleware
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $originalMiddleware = $this->middleware;
        $this->middleware = array_merge($this->middleware, $middleware);
        
        // Temporarily store current prefix
        $originalPrefix = $this->currentPrefix ?? '';
        $this->currentPrefix = $originalPrefix . $prefix;
        
        $callback();
        
        // Restore original state
        $this->middleware = $originalMiddleware;
        $this->currentPrefix = $originalPrefix;
    }
    
    /**
     * Add middleware to current group
     */
    public function middleware(array $middleware): void
    {
        $this->middleware = array_merge($this->middleware, $middleware);
    }
    
    /**
     * Find matching route
     */
    private function findRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $matches = [];
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                
                $route['params'] = $params;
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Execute controller action
     */
    private function executeController(string $controller, string $action, array $params = []): array
    {
        [$controllerClass, $method] = explode('@', $controller);
        
        // Add namespace if not present
        if (strpos($controllerClass, '\\') === false) {
            $controllerClass = "BotMirzaPanel\\Presentation\\Http\\Controllers\\{$controllerClass}";
        }
        
        if (!class_exists($controllerClass)) {
            return $this->error('Controller not found', 404);
        }
        
        $controllerInstance = new $controllerClass();
        
        // Inject container if controller extends BaseController
        if ($controllerInstance instanceof BaseController) {
            $controllerInstance->setContainer($this->container);
        }
        
        if (!method_exists($controllerInstance, $method)) {
            return $this->error('Method not found', 404);
        }
        
        // Set route parameters
        if ($controllerInstance instanceof BaseController) {
            $controllerInstance->setRouteParams($params);
        }
        
        return $controllerInstance->$method();
    }
    
    /**
     * Convert path to regex pattern
     */
    private function pathToPattern(string $path): string
    {
        // Add current prefix if in group
        if (isset($this->currentPrefix)) {
            $path = $this->currentPrefix . $path;
        }
        
        // Escape special regex characters
        $pattern = preg_quote($path, '/');
        
        // Replace parameter placeholders with named capture groups
        $pattern = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $pattern);
        
        return '/^' . $pattern . '$/i';
    }
    
    /**
     * Normalize URI
     */
    private function normalizeUri(string $uri): string
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH) ?? $uri;
        
        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = substr($uri, 0, -1);
        }
        
        return $uri;
    }
    
    /**
     * Return 404 response
     */
    private function notFound(): array
    {
        return [
            'status' => 404,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'error' => 'Not Found',
                'message' => 'The requested resource was not found.'
            ])
        ];
    }
    
    /**
     * Return error response
     */
    private function error(string $message, int $status = 500, string $details = null): array
    {
        $body = [
            'error' => $message,
            'status' => $status
        ];
        
        if ($details && $_ENV['APP_DEBUG'] ?? false) {
            $body['details'] = $details;
        }
        
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body)
        ];
    }
    
    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}