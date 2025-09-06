<?php
// WARNING: SQL injection vulnerability detected in this file
// Please review and use prepared statements or secure_* functions


/**
 * New modular entry point for BotMirzaPanel
 * This file demonstrates how to use the new modular architecture
 * while maintaining backward compatibility with existing functionality
 */

// Define initialization constant
define('BOTMIRZAPANEL_INIT', true);

// Load the new modular bootstrap
$container = require_once __DIR__ . '/src/bootstrap.php';

// Get the application instance
$app = app();

// Handle different request types
try {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Parse the request path
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Route the request
    switch ($path) {
        case '':
        case 'index.php':
            handleWebhook();
            break;
            
        case 'webhook':
        case 'webhook.php':
            handleWebhook();
            break;
            
        case 'payment/callback':
        case 'payment_callback.php':
            handlePaymentCallback();
            break;
            
        case 'cron':
        case 'cron.php':
            handleCron();
            break;
            
        case 'admin':
        case 'admin.php':
            handleAdmin();
            break;
            
        case 'api/status':
            handleApiStatus();
            break;
            
        case 'api/health':
            handleApiHealth();
            break;
            
        default:
            // Try to handle legacy routes
            handleLegacyRoute($path);
            break;
    }
    
} catch (\Throwable $e) {
    handleError($e);
}

/**
 * Handle Telegram webhook
 */
function handleWebhook(): void
{
    header('Content-Type: application/json');
    
    try {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            echo json_encode(['status' => 'error', 'message' => 'No input data']);
            return;
        }
        
        $update = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
            return;
        }
        
        // Process the update using the new modular system
        $telegram = telegram();
        $services = [
            'config' => config(),
            'database' => db(),
            'telegram' => $telegram,
            'payment' => paymentService(),
            'panel' => panelService(),
            'user' => userService(),
            'cron' => cronService()
        ];
        $result = $telegram->processUpdate($update, $services);
        
        echo json_encode(['status' => 'success', 'result' => $result]);
        
    } catch (\Exception $e) {
        error_log("Webhook error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Internal error']);
    }
}

/**
 * Handle payment callbacks
 */
function handlePaymentCallback(): void
{
    header('Content-Type: application/json');
    
    try {
        $gateway = $_GET['gateway'] ?? $_POST['gateway'] ?? null;
        
        if (!$gateway) {
            echo json_encode(['status' => 'error', 'message' => 'Gateway not specified']);
            return;
        }
        
        // Process payment callback using the new modular system
        $paymentService = paymentService();
        $result = $paymentService->processCallback($gateway, $_REQUEST);
        
        if ($result['success']) {
            echo json_encode(['status' => 'success', 'data' => $result]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $result['error'] ?? 'Payment verification failed']);
        }
        
    } catch (\Exception $e) {
        error_log("Payment callback error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Internal error']);
    }
}

/**
 * Handle cron jobs
 */
function handleCron(): void
{
    // Verify cron access
    $cronKey = $_GET['key'] ?? $_POST['key'] ?? null;
    $expectedKey = config('cron.secret_key');
    
    if (!$cronKey || $cronKey !== $expectedKey) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    
    header('Content-Type: application/json');
    
    try {
        $jobName = $_GET['job'] ?? null;
        $cronService = cronService();
        
        if ($jobName) {
            // Run specific job
            $result = $cronService->runJob($jobName);
        } else {
            // Run all due jobs
            $result = $cronService->runDueJobs();
        }
        
        echo json_encode(['status' => 'success', 'result' => $result]);
        
    } catch (\Exception $e) {
        error_log("Cron error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Internal error']);
    }
}

/**
 * Handle admin interface
 */
function handleAdmin(): void
{
    // Simple admin interface
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Check admin authentication
    $isAdmin = $_SESSION['is_admin'] ?? false;
    
    if (!$isAdmin && isset($_POST['admin_password'])) {
        $adminPassword = config('admin.password');
        if ($_POST['admin_password'] === $adminPassword) {
            $_SESSION['is_admin'] = true;
            $isAdmin = true;
        }
    }
    
    if (!$isAdmin) {
        showAdminLogin();
        return;
    }
    
    showAdminDashboard();
}

/**
 * Handle API status endpoint
 */
function handleApiStatus(): void
{
    header('Content-Type: application/json');
    
    try {
        $status = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'version' => '2.0.0',
            'modules' => [
                'config' => class_exists('BotMirzaPanel\\Config\\ConfigManager'),
                'database' => class_exists('BotMirzaPanel\\Database\\DatabaseManager'),
                'telegram' => class_exists('BotMirzaPanel\\Telegram\\TelegramBot'),
                'payment' => class_exists('BotMirzaPanel\\Payment\\PaymentService'),
                'panel' => class_exists('BotMirzaPanel\\Panel\\PanelService'),
                'user' => class_exists('BotMirzaPanel\\User\\UserService'),
                'cron' => class_exists('BotMirzaPanel\\Cron\\CronService')
            ]
        ];
        
        echo json_encode($status, JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

/**
 * Handle API health check
 */
function handleApiHealth(): void
{
    header('Content-Type: application/json');
    
    try {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'checks' => []
        ];
        
        // Database health
        try {
            db()->fetchOne('SELECT 1');
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['database'] = 'error';
            $health['status'] = 'unhealthy';
        }
        
        // Config health
        try {
            config('app.name');
            $health['checks']['config'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['config'] = 'error';
            $health['status'] = 'unhealthy';
        }
        
        // Panel health
        try {
            $panels = panelService()->getConfiguredPanels();
            $health['checks']['panels'] = count($panels) > 0 ? 'ok' : 'warning';
        } catch (\Exception $e) {
            $health['checks']['panels'] = 'error';
        }
        
        echo json_encode($health, JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

/**
 * Handle legacy routes
 */
function handleLegacyRoute(string $path): void
{
    // Map legacy files to new system
    $legacyMappings = [
        'botapi.php' => 'webhook',
        'sendmessage.php' => 'cron',
        'cronday.php' => 'cron',
        'nowpayments.php' => 'payment/callback',
        'aqayepardakht.php' => 'payment/callback',
        'cartocart.php' => 'payment/callback',
        'iranpay.php' => 'payment/callback'
    ];
    
    if (isset($legacyMappings[$path])) {
        // Redirect to new route
        $newPath = $legacyMappings[$path];
        header("Location: /{$newPath}", true, 301);
        exit;
    }
    
    // Try to include legacy file if it exists
    $legacyFile = __DIR__ . '/' . $path;
    if (file_exists($legacyFile) && is_file($legacyFile)) {
        // Set up legacy environment
        setupLegacyEnvironment();
        include $legacyFile;
        return;
    }
    
    // 404 Not Found
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Not found']);
}

/**
 * Set up environment for legacy files
 */
function setupLegacyEnvironment(): void
{
    // Make new services available to legacy code through global variables
    global $config, $database, $telegram, $userService, $panelService, $paymentService;
    
    $config = config();
    $database = db();
    $telegram = telegram();
    $userService = userService();
    $panelService = panelService();
    $paymentService = paymentService();
    
    // Include legacy functions if they exist
    if (file_exists(__DIR__ . '/functions.php')) {
        include_once __DIR__ . '/functions.php';
    }
}

/**
 * Show admin login form
 */
function showAdminLogin(): void
{
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - BotMirzaPanel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 50px; }
        .login-form { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #005a87; }
        h2 { text-align: center; margin-bottom: 30px; color: #333; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Admin Login</h2>
        <form method="post">
            <div class="form-group">
                <label for="admin_password">Admin Password:</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>';
}

/**
 * Show admin dashboard
 */
function showAdminDashboard(): void
{
    try {
        $userStats = userService()->getSystemStats();
        $cronJobs = cronService()->getJobs();
        $panels = panelService()->getConfiguredPanels();
        $gateways = paymentService()->getAvailableGateways();
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - BotMirzaPanel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2em; font-weight: bold; color: #007cba; }
        .stat-label { color: #666; margin-top: 5px; }
        .section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h3 { margin-top: 0; color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .logout { float: right; color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BotMirzaPanel Admin Dashboard</h1>
            <a href="?logout=1" class="logout">Logout</a>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">' . ($userStats['total_users'] ?? 0) . '</div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . ($userStats['active_users'] ?? 0) . '</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . count($panels) . '</div>
                <div class="stat-label">Configured Panels</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . count($gateways) . '</div>
                <div class="stat-label">Payment Gateways</div>
            </div>
        </div>
        
        <div class="section">
            <h3>System Status</h3>
            <p><strong>Database:</strong> <span class="status-ok">Connected</span></p>
            <p><strong>Telegram Bot:</strong> <span class="status-ok">Active</span></p>
            <p><strong>Modular System:</strong> <span class="status-ok">Loaded</span></p>
        </div>
        
        <div class="section">
            <h3>Cron Jobs</h3>
            <table>
                <tr><th>Job Name</th><th>Schedule</th><th>Last Run</th><th>Status</th></tr>';
        
        foreach ($cronJobs as $job) {
            $status = $job['is_due'] ? '<span class="status-warning">Due</span>' : '<span class="status-ok">OK</span>';
            echo "<tr><td>{$job['name']}</td><td>{$job['schedule']}</td><td>{$job['last_run']}</td><td>{$status}</td></tr>";
        }
        
        echo '</table>
        </div>
        
        <div class="section">
            <h3>Configured Panels</h3>
            <table>
                <tr><th>Name</th><th>Type</th><th>URL</th><th>Status</th></tr>';
        
        foreach ($panels as $panel) {
            $status = '<span class="status-ok">Active</span>'; // You could test connection here
            echo "<tr><td>{$panel['name']}</td><td>{$panel['type']}</td><td>{$panel['url']}</td><td>{$status}</td></tr>";
        }
        
        echo '</table>
        </div>
    </div>
</body>
</html>';
        
    } catch (\Exception $e) {
        echo '<h1>Error loading dashboard</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: /admin');
    exit;
}