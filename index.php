<?php

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

<<<<<<< HEAD
ini_set('error_log', 'error_log');
$version = "5.1.8";
date_default_timezone_set('Asia/Tehran');
require_once 'config.php';
require_once 'botapi.php';
require_once 'jdf.php';
require_once 'text.php';
require_once 'keyboard.php';
require_once 'functions.php';
require_once 'panels.php';
require_once 'vendor/autoload.php';

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
if (is_dir('installer')) {
    deleteFolder('installer');
}
$first_name = sanitizeUserName($first_name);
if (!in_array($Chat_type, ["private"]))
    return;
#-----------telegram_ip_ranges------------#
if (!checktelegramip())
    die("Unauthorized access");
#-------------Variable----------#
$users_ids = select("user", "id", null, null, "FETCH_COLUMN");
$setting = select("setting", "*");
$admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN");
if (!in_array($from_id, $users_ids) && intval($from_id) != 0) {
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['ManageUser']['sendmessageUser'], 'callback_data' => 'Response_' . $from_id],
            ]
        ]
    ]);
    $newuser = sprintf($textbotlang['Admin']['ManageUser']['NewUserMessage'], $first_name, $username, $from_id, $from_id);
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $newuser, $Response, 'html');
    }
}
if (intval($from_id) != 0) {
    if (intval($setting['status_verify']) == 1) {
        $verify = 0;
    } else {
        $verify = 1;
    }

    do {
        $ref_code = bin2hex(random_bytes(16));
        $stmt_check = $pdo->prepare("SELECT 1 FROM user WHERE ref_code = :ref_code");
        $stmt_check->bindParam(':ref_code', $ref_code);
        $stmt_check->execute();

    } while ($stmt_check->fetchColumn());

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO user
            (id, ref_code, step, limit_usertest, User_Status, number, Balance,
            pagenumber, username, message_count, last_message_time,
            affiliatescount, affiliates, verify)
        VALUES
            (:from_id, :ref_code, 'none', :limit_usertest_all, 'Active', 'none', '0',
            '1', :username, '0', '0', '0', '0', :verify)"
    );
    $stmt->bindParam(':ref_code', $ref_code);
    $stmt->bindParam(':verify', $verify);
    $stmt->bindParam(':from_id', $from_id);
    $stmt->bindParam(':limit_usertest_all', $setting['limit_usertest_all']);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
}
$user = select("user", "*", "id", $from_id, "select");
if ($user == false) {
    $user = array();
    $user = array(
        'step' => '',
        'Processing_value' => '',
        'User_Status' => '',
        'username' => '',
        'limit_usertest' => '',
        'last_message_time' => '',
        'affiliates' => '',
    );
}
if (($setting['status_verify'] == "1" && intval($user['verify']) == 0) && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $textbotlang['users']['VerifyUser'], null, 'html');
    return;
}
;
$channels = array();
$helpdata = select("help", "*");
$datatextbotget = select("textbot", "*", null, null, "fetchAll");
$id_invoice = select("invoice", "id_invoice", null, null, "FETCH_COLUMN");
$channels = select("channels", "*");
$usernameinvoice = select("invoice", "username", null, null, "FETCH_COLUMN");
$code_Discount = select("Discount", "code", null, null, "FETCH_COLUMN");
$users_ids = select("user", "id", null, null, "FETCH_COLUMN");
$marzban_list = select("marzban_panel", "name_panel", null, null, "FETCH_COLUMN");
$name_product = select("product", "name_product", null, null, "FETCH_COLUMN");
$SellDiscount = select("DiscountSell", "codeDiscount", null, null, "FETCH_COLUMN");
$ManagePanel = new ManagePanel();
$datatxtbot = array();
foreach ($datatextbotget as $row) {
    $datatxtbot[] = array(
        'id_text' => $row['id_text'],
        'text' => $row['text']
    );
}

$datatextbot = array(
    'text_usertest' => '',
    'text_Purchased_services' => '',
    'text_support' => '',
    'text_help' => '',
    'text_start' => '',
    'text_bot_off' => '',
    'text_roll' => '',
    'text_fq' => '',
    'text_dec_fq' => '',
    'text_account' => '',
    'text_sell' => '',
    'text_Add_Balance' => '',
    'text_channel' => '',
    'text_Discount' => '',
    'text_Tariff_list' => '',
    'text_dec_Tariff_list' => '',
);
foreach ($datatxtbot as $item) {
    if (isset($datatextbot[$item['id_text']])) {
        $datatextbot[$item['id_text']] = $item['text'];
    }
}
if (function_exists('shell_exec') && is_callable('shell_exec')) {
    $existingCronCommands = shell_exec('crontab -l');
    $phpFilePath = "https://$domainhosts/cron/sendmessage.php";
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}
#---------channel--------------#
if ($user['username'] == "none" || $user['username'] == null) {
    update("user", "username", $username, "id", $from_id);
}
#-----------User_Status------------#
if ($user['User_Status'] == "block") {
    $textblock = sprintf($textbotlang['Admin']['ManageUser']['BlockedUser'], $user['description_blocking']);
    sendmessage($from_id, $textblock, null, 'html');
    return;
}
if (strpos($text, "/start ") !== false) {
    if ($user['affiliates'] != 0) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['affiliateseduser'], null, 'html');
        return;
    }
    $affiliatesvalue = select("affiliates", "*", null, null, "select")['affiliatesstatus'];
    if ($affiliatesvalue == "offaffiliates") {
        sendmessage($from_id, $textbotlang['users']['affiliates']['offaffiliates'], $keyboard, 'HTML');
        return;
    }
    $token = str_replace("/start ", "", $text);
    $refRow = select("user", "id", "ref_code", $token, "select");
    if ($refRow !== false) {
        $affiliatesid = $refRow['id'];                 // modern link found
    }
    /*  2️⃣  fall back to legacy numeric ID  */ elseif (ctype_digit($token)) {
        $affiliatesid = (int) $token;                   // old link
    }
    /*  3️⃣  invalid token → pretend there is no referrer       */ else {
        $affiliatesid = 0;                             // will fail the in_array() test below
    }
    if (ctype_digit($affiliatesid)) {
        if (!in_array($affiliatesid, $users_ids)) {
            sendmessage($from_id, $textbotlang['users']['affiliates']['affiliatesyou'], null, 'html');
=======
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
>>>>>>> ac58632 (feat(core): introduce modular index.php entrypoint with routing and admin dashboard)
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

<<<<<<< HEAD

    $keyboardinfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $DataUserOut['username'], 'callback_data' => "username"],
                ['text' => $textbotlang['users']['stateus']['username'], 'callback_data' => 'username'],
            ],
            [
                ['text' => $status_var, 'callback_data' => 'status_var'],
                ['text' => $textbotlang['users']['stateus']['stateus'], 'callback_data' => 'status_var'],
            ],
            [
                ['text' => $expirationDate, 'callback_data' => 'expirationDate'],
                ['text' => $textbotlang['users']['stateus']['expirationDate'], 'callback_data' => 'expirationDate'],
            ],
            [],
            [
                ['text' => $day, 'callback_data' => 'day'],
                ['text' => $textbotlang['users']['stateus']['daysleft'], 'callback_data' => 'day'],
            ],
            [
                ['text' => $LastTraffic, 'callback_data' => 'LastTraffic'],
                ['text' => $textbotlang['users']['stateus']['LastTraffic'], 'callback_data' => 'LastTraffic'],
            ],
            [
                ['text' => $usedTrafficGb, 'callback_data' => 'expirationDate'],
                ['text' => $textbotlang['users']['stateus']['usedTrafficGb'], 'callback_data' => 'expirationDate'],
            ],
            [
                ['text' => $RemainingVolume, 'callback_data' => 'RemainingVolume'],
                ['text' => $textbotlang['users']['stateus']['RemainingVolume'], 'callback_data' => 'RemainingVolume'],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['stateus']['info'], $keyboardinfo, 'html');
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'html');
    step('home', $from_id);
}
if (preg_match('/product_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $username);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        sendmessage($from_id, $textbotlang['users']['stateus']['usernotfound'], $keyboard, 'html');
        update("invoice", "Status", "disabledn", "id_invoice", $nameloc['id_invoice']);
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], $keyboard, 'html');
        return;
    }
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = $textbotlang['users']['online'];
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = $textbotlang['users']['offline'];
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateString = $DataUserOut['online_at'];
            $lastonline = jdate('Y/m/d h:i:s', strtotime($dateString));
        } else {
            $lastonline = $textbotlang['users']['stateus']['notconnected'];
        }
    }
    #-------------status----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['onhold']
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['stateus']['Unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['users']['unlimited'];
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['stateus']['Notconsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) + 1 . $textbotlang['users']['stateus']['day'] : $textbotlang['users']['stateus']['Unlimited'];
    #-----------------------------#
    if (!in_array($status, ['active', "on_hold"])) {
        $keyboardsetting = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extend_' . $username],
                ],
                [
                    ['text' => $textbotlang['users']['stateus']['RemoveSerivecbtn'], 'callback_data' => 'removebyuser-' . $username],
                    ['text' => $textbotlang['users']['Extra_volume']['sellextra'], 'callback_data' => 'Extra_volume_' . $username],
                ],
                [
                    ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => 'backorder'],
                ]
            ]
        ]);
        $textinfo = sprintf($textbotlang['users']['stateus']['InfoSerivceDisable'], $status_var, $DataUserOut['username'], $nameloc['Service_location'], $nameloc['id_invoice'], $LastTraffic, $usedTrafficGb, $expirationDate, $day);
    } else {
        $keyboarddate = array(
            'linksub' => array(
                'text' => $textbotlang['users']['stateus']['linksub'],
                'callback_data' => "subscriptionurl_"
            ),
            'config' => array(
                'text' => $textbotlang['users']['stateus']['config'],
                'callback_data' => "config_"
            ),
            'extend' => array(
                'text' => $textbotlang['users']['extend']['title'],
                'callback_data' => "extend_"
            ),
            'changelink' => array(
                'text' => $textbotlang['users']['changelink']['btntitle'],
                'callback_data' => "changelink_"
            ),
            'removeservice' => array(
                'text' => $textbotlang['users']['removeconfig']['btnremoveuser'],
                'callback_data' => "removeserviceuserco-"
            )
            ,
            'Extra_volume' => array(
                'text' => $textbotlang['users']['Extra_volume']['sellextra'],
                'callback_data' => "Extra_volume_"
            ),
        );
        if ($marzban_list_get['type'] == "wgdashboard") {
            unset($keyboarddate['config']);
            unset($keyboarddate['changelink']);
        }
        if ($marzban_list_get['type'] == "mikrotik") {
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['linksub']);
            unset($keyboarddate['config']);
            unset($keyboarddate['extend']);
            unset($keyboarddate['changelink']);
            unset($keyboarddate['Extra_volume']);
        }
        if ($nameloc['name_product'] == "usertest") {
            unset($keyboarddate['removeservice']);
        }
        $tempArray = [];
        $keyboardsetting = ['inline_keyboard' => []];
        foreach ($keyboarddate as $keyboardtext) {
            $tempArray[] = ['text' => $keyboardtext['text'], 'callback_data' => $keyboardtext['callback_data'] . $username];
            if (count($tempArray) == 2) {
                $keyboardsetting['inline_keyboard'][] = $tempArray;
                $tempArray = [];
            }
        }
        if (count($tempArray) > 0) {
            $keyboardsetting['inline_keyboard'][] = $tempArray;
        }
        $keyboardsetting['inline_keyboard'][] = [['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => 'backorder']];
        $keyboardsetting = json_encode($keyboardsetting);
        if ($marzban_list_get['type'] == "mikrotik") {
            $textinfo = sprintf($textbotlang['users']['stateus']['InfoSerivceActive_mikrotik'], $status_var, $DataUserOut['username'], $DataUserOut['subscription_url'], $nameloc['Service_location'], $nameloc['id_invoice'], $LastTraffic, $usedTrafficGb, $expirationDate, $day);
        } else {
            $textinfo = sprintf($textbotlang['users']['stateus']['InfoSerivceActive'], $status_var, $DataUserOut['username'], $nameloc['Service_location'], $nameloc['id_invoice'], $lastonline, $LastTraffic, $usedTrafficGb, $expirationDate, $day);
        }
    }
    Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
}
if (preg_match('/subscriptionurl_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $username);
    $subscriptionurl = $DataUserOut['subscription_url'];
    if ($marzban_list_get['type'] == "wgdashboard") {
        $textsub = "";
    } else {
        $textsub = "<code>$subscriptionurl</code>";
    }
    $randomString = bin2hex(random_bytes(2));
    $urlimage = "$from_id$randomString.png";
    $writer = new PngWriter();
    $qrCode = QrCode::create($subscriptionurl)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
        ->setSize(400)
        ->setMargin(0)
        ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
    $result = $writer->write($qrCode, null, null);
    $result->saveToFile($urlimage);
    telegram('sendphoto', [
        'chat_id' => $from_id,
        'photo' => new CURLFile($urlimage),
        'caption' => $textsub,
        'parse_mode' => "HTML",
    ]);
    if ($marzban_list_get['type'] == "wgdashboard") {
        $urlimage = "{$marzban_list_get['inboundid']}_{$nameloc['username']}.conf";
        file_put_contents($urlimage, $DataUserOut['subscription_url']);
        sendDocument($from_id, $urlimage, $textbotlang['users']['buy']['configwg']);
        unlink($urlimage);
    }
    unlink($urlimage);
} elseif (preg_match('/config_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $username);
    foreach ($DataUserOut['links'] as $configs) {
        $randomString = bin2hex(random_bytes(2));
        $urlimage = "$from_id$randomString.png";
        $writer = new PngWriter();
        $qrCode = QrCode::create($configs)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(400)
            ->setMargin(0)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
        $result = $writer->write($qrCode, null, null);
        $result->saveToFile($urlimage);
        telegram('sendphoto', [
            'chat_id' => $from_id,
            'photo' => new CURLFile($urlimage),
            'caption' => "<code>$configs</code>",
            'parse_mode' => "HTML",
        ]);
        unlink($urlimage);
    }
} elseif (preg_match('/extend_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $username);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    update("user", "Processing_value", $username, "id", $from_id);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :Location OR location = '/all')");
    $stmt->bindValue(':Location', $nameloc['Service_location']);
    $stmt->execute();
    $productextend = ['inline_keyboard' => []];
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productextend['inline_keyboard'][] = [
            ['text' => $result['name_product'], 'callback_data' => "serviceextendselect_" . $result['code_product']]
        ];
    }
    $productextend['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backorder'], 'callback_data' => "product_" . $username]
    ];

    $json_list_product_lists = json_encode($productextend);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], $json_list_product_lists);
} elseif (preg_match('/serviceextendselect_(\w+)/', $datain, $dataget)) {
    $codeproduct = $dataget[1];
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    if( $nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['error2'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :Location OR location = '/all') AND code_product = :code_product LIMIT 1");
    $stmt->bindValue(':Location', $nameloc['Service_location']);
    $stmt->bindValue(':code_product', $codeproduct);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if($product == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['error2'], null, 'HTML');
        return;
    }
    update("invoice", "name_product", $product['name_product'], "username", $user['Processing_value']);
    update("invoice", "Service_time", $product['Service_time'], "username", $user['Processing_value']);
    update("invoice", "Volume", $product['Volume_constraint'], "username", $user['Processing_value']);
    update("invoice", "price_product", $product['price_product'], "username", $user['Processing_value']);
    update("user", "Processing_value_one", $codeproduct, "id", $from_id);
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivce-" . $codeproduct],
            ],
            [
                ['text' => $textbotlang['users']['backhome'], 'callback_data' => "backuser"]

            ]
        ]
    ]);
    $textextend = sprintf($textbotlang['users']['extend']['invoicExtend'], $nameloc['username'], $product['name_product'], $product['price_product'], $product['Service_time'], $product['Service_time'], $product['Volume_constraint']);
    Editmessagetext($from_id, $message_id, $textextend, $keyboardextend);
} elseif (preg_match('/confirmserivce-(.*)/', $datain, $dataget)) {
    $codeproduct = $dataget[1];
    deletemessage($from_id, $message_id);
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['error2'], null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['error2'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :Location OR location = '/all') AND code_product = :code_product LIMIT 1");
    $stmt->bindValue(':Location', $nameloc['Service_location']);
    $stmt->bindValue(':code_product', $codeproduct);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['error2'], null, 'HTML');
        return;
    }
    if ($user['Balance'] < $product['price_product']) {
        $Balance_prim = $product['price_product'] - $user['Balance'];
        update("user", "Processing_value", $Balance_prim, "id", $from_id);
        sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
        sendmessage($from_id, $textbotlang['users']['sell']['selectpayment'], $backuser, 'HTML');
        step('get_step_payment', $from_id);
        return;
    }
    $usernamepanel = $nameloc['username'];
    $Balance_Low_user = $user['Balance'] - $product['price_product'];
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $ManagePanel->ResetUserDataUsage($nameloc['Service_location'], $user['Processing_value']);
    if ($marzban_list_get['type'] == "marzban") {
        if (intval($product['Service_time']) == 0) {
            $newDate = 0;
        } else {
            $date = strtotime("+" . $product['Service_time'] . "day");
            $newDate = strtotime(date("Y-m-d H:i:s", $date));
        }
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $datam = array(
            "expire" => $newDate,
            "data_limit" => $data_limit
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $datam);
    } elseif ($marzban_list_get['type'] == "marzneshin") {
        if (intval($product['Service_time']) == 0) {
            $newDate = 0;
        } else {
            $date = strtotime("+" . $product['Service_time'] . "day");
            $newDate = strtotime(date("Y-m-d H:i:s", $date));
        }
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $datam = array(
            "expire_date" => $newDate,
            "data_limit" => $data_limit
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $datam);
    } elseif ($marzban_list_get['type'] == "x-ui_single") {
        $date = strtotime("+" . $product['Service_time'] . "day");
        $newDate = strtotime(date("Y-m-d H:i:s", $date)) * 1000;
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $config = array(
            'id' => intval($marzban_list_get['inboundid']),
            'settings' => json_encode(
                array(
                    'clients' => array(
                        array(
                            "totalGB" => $data_limit,
                            "expiryTime" => $newDate,
                            "enable" => true,
                        )
                    ),
                )
            ),
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $config);
    } elseif ($marzban_list_get['type'] == "alireza") {
        $date = strtotime("+" . $product['Service_time'] . "day");
        $newDate = strtotime(date("Y-m-d H:i:s", $date)) * 1000;
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $config = array(
            'id' => intval($marzban_list_get['inboundid']),
            'settings' => json_encode(
                array(
                    'clients' => array(
                        array(
                            "totalGB" => $data_limit,
                            "expiryTime" => $newDate,
                            "enable" => true,
                        )
                    ),
                )
            ),
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $config);
    } elseif ($marzban_list_get['type'] == "s_ui") {
        $date = strtotime("+" . $product['Service_time'] . "day");
        $newDate = strtotime(date("Y-m-d H:i:s", $date));
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $config = array(
            "volume" => $data_limit,
            "expiry" => $newDate
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $config);
    } elseif ($marzban_list_get['type'] == "wgdashboard") {
        $usernamepanel = $nameloc['username'];
        $namepanel = $nameloc['Service_location'];
        allowAccessPeers($namepanel, $usernamepanel);
        $datauser = get_userwg($usernamepanel, $namepanel);
        $count = 0;
        foreach ($datauser['jobs'] as $jobsvolume) {
            if ($jobsvolume['Field'] == "date") {
                break;
            }
            $count += 1;
        }
        $datam = array(
            "Job" => $datauser['jobs'][$count],
        );
        deletejob($namepanel, $datam);
        $count = 0;
        foreach ($datauser['jobs'] as $jobsvolume) {
            if ($jobsvolume['Field'] == "total_data") {
                break;
            }
            $count += 1;
        }
        $datam = array(
            "Job" => $datauser['jobs'][$count],
        );
        deletejob($namepanel, $datam);

        if (intval($product['Service_time']) == 0) {
            $newDate = 0;
        } else {
            $date = strtotime("+" . $product['Service_time'] . "day");
            $newDate = strtotime(date("Y-m-d H:i:s", $date));
        }
        if ($newDate != 0) {
            $newDate = date("Y-m-d H:i:s", $newDate);
            setjob($namepanel, "date", $newDate, $datauser['id']);
        }
        setjob($namepanel, "total_data", $product['Volume_constraint'], $datauser['id']);
    }
    $keyboardextendfnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => "backorder"],
            ],
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $usernamepanel],
            ]
        ]
    ]);
    $priceproductformat = number_format($product['price_product']);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance']);
    update("invoice", "Status", "active", "id_invoice", $nameloc['id_invoice']);
    sendmessage($from_id, $textbotlang['users']['extend']['thanks'], $keyboardextendfnished, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['Report']['extend'], $from_id, $username, $product['name_product'], $priceproductformat, $usernamepanel, $balanceformatsell, $nameloc['Service_location']);
    if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
} elseif (preg_match('/changelink_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $keyboardchange = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['changelink']['confirm'], 'callback_data' => "confirmchange_" . $username],
            ],
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $username],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['changelink']['warnchange'], $keyboardchange);
} elseif (preg_match('/confirmchange_(\w+)/', $datain, $dataget)) {
    $usernameconfig = $dataget[1];
    $nameloc = select("invoice", "*", "username", $usernameconfig, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $ManagePanel->Revoke_sub($marzban_list_get['name_panel'], $usernameconfig);
    $keyboardchange = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $usernameconfig],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['changelink']['confirmed'], $keyboardchange);
} elseif (preg_match('/Extra_volume_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    update("user", "Processing_value", $username, "id", $from_id);
    $textextra = " .";
    sendmessage($from_id, sprintf($textbotlang['users']['Extra_volume']['VolumeValue'], $setting['Extra_volume']), $backuser, 'HTML');
    step('getvolumeextra', $from_id);
} elseif ($user['step'] == "getvolumeextra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backuser, 'HTML');
        return;
    }
    if ($text < 1) {
        sendmessage($from_id, $textbotlang['users']['Extra_volume']['invalidprice'], $backuser, 'HTML');
        return;
    }
    $priceextra = $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Extra_volume']['extracheck'], 'callback_data' => 'confirmaextra_' . $text],
            ]
        ]
    ]);
    $priceextra = number_format($priceextra * $setting['Extra_volume']);
    $setting['Extra_volume'] = number_format($setting['Extra_volume']);
    $textextra = sprintf($textbotlang['users']['Extra_volume']['invoiceExtraVolume'], $setting['Extra_volume'], $priceextra, $text);
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextra_(\w+)/', $datain, $dataget)) {
    $volume = $dataget[1];
    $price_extra = $setting['Extra_volume'] * $volume;
    Editmessagetext($from_id, $message_id, $text_callback, json_encode(['inline_keyboard' => []]));
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get == false) {
        sendmessage($from_id, $textbotlang['users']['stateus']['error'], null, 'html');
        return;
    }
    if ($user['Balance'] < $price_extra && intval($setting['Extra_volume']) != 0) {
        $Balance_prim = $price_extra - $user['Balance'];
        update("user", "Processing_value", $Balance_prim, "id", $from_id);
        sendmessage($from_id, $textbotlang['users']['sell']['None-credit'], $step_payment, 'HTML');
        step('get_step_payment', $from_id);
        return;
    }
    if (intval($setting['Extra_volume']) != 0) {
        $Balance_Low_user = $user['Balance'] - $price_extra;
        update("user", "Balance", $Balance_Low_user, "id", $from_id);
    }
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $user['Processing_value']);
    $data_limit = $DataUserOut['data_limit'] + ($volume * pow(1024, 3));
    if ($marzban_list_get['type'] == "marzban") {
        $datam = array(
            "data_limit" => $data_limit
        );
    } elseif ($marzban_list_get['type'] == "marzneshin") {
        $datam = array(
            "data_limit" => $data_limit
        );
    } elseif ($marzban_list_get['type'] == "x-ui_single") {
        $datam = array(
            'id' => intval($marzban_list_get['inboundid']),
            'settings' => json_encode(
                array(
                    'clients' => array(
                        array(
                            "totalGB" => $data_limit,
                        )
                    ),
                )
            ),
        );
    } elseif ($marzban_list_get['type'] == "alireza") {
        $datam = array(
            'id' => intval($marzban_list_get['inboundid']),
            'settings' => json_encode(
                array(
                    'clients' => array(
                        array(
                            "totalGB" => $data_limit,
                        )
                    ),
                )
            ),
        );
    } elseif ($marzban_list_get['type'] == "s_ui") {
        $datam = array(
            "volume" => $data_limit,
        );
    } elseif ($marzban_list_get['type'] == "wgdashboard") {
        $data_limit = ($DataUserOut['data_limit'] / pow(1024, 3)) + ($volume / $setting['Extra_volume']);
        $datauser = get_userwg($nameloc['username'], $nameloc['Service_location']);
        $count = 0;
        foreach ($datauser['jobs'] as $jobsvolume) {
            if ($jobsvolume['Field'] == "total_data") {
                break;
            }
            $count += 1;
        }
        allowAccessPeers($nameloc['Service_location'], $nameloc['username']);
        if (isset($datauser['jobs'][$count])) {
            $datam = array(
                "Job" => $datauser['jobs'][$count],
            );
            deletejob($nameloc['Service_location'], $datam);
        } else {
            ResetUserDataUsagewg($datauser['id'], $nameloc['Service_location']);
        }
        setjob($nameloc['Service_location'], "total_data", $data_limit, $datauser['id']);
    }
    $ManagePanel->Modifyuser($nameloc['username'], $marzban_list_get['name_panel'], $datam);
    $keyboardextrafnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $user['Processing_value']],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['Extra_volume']['extraadded'], $keyboardextrafnished, 'HTML');
    $volumes = $volume;
    $price_extra = number_format($price_extra);
    $text_report = sprintf($textbotlang['Admin']['Report']['Extra_volume'], $from_id, $volumes, $price_extra);
    if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
} elseif (preg_match('/removeserviceuserco-(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username);
    if (isset($DataUserOut['status']) && in_array($DataUserOut['status'], ["expired", "limited", "disabled"])) {
        sendmessage($from_id, $textbotlang['users']['stateus']['notusername'], null, 'html');
        return;
    }
    $requestcheck = select("cancel_service", "*", "username", $username, "count");
    if ($requestcheck != 0) {
        sendmessage($from_id, $textbotlang['users']['stateus']['errorexits'], null, 'html');
        return;
    }
    $confirmremove = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['RequestRemove'], 'callback_data' => "confirmremoveservices-$username"],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['stateus']['descriptions_removeservice'], $confirmremove);
} elseif (preg_match('/removebyuser-(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
    update('invoice', 'status', 'removebyuser', 'id_invoice', $nameloc['id_invoice']);
    $tetremove = sprintf($textbotlang['Admin']['Report']['NotifRemoveByUser'], $nameloc['username']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'text' => $tetremove,
            'parse_mode' => "HTML"
        ]);
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['stateus']['RemovedService'], null, 'html');
} elseif (preg_match('/confirmremoveservices-(\w+)/', $datain, $dataget)) {
    $checkcancelservice = mysqli_query($connect, "SELECT * FROM cancel_service WHERE id_user = '$from_id' AND status = 'waiting'");
    if (mysqli_num_rows($checkcancelservice) != 0) {
        sendmessage($from_id, $textbotlang['users']['stateus']['exitsrequsts'], null, 'HTML');
        return;
    }
    $usernamepanel = $dataget[1];
    $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $stmt = $connect->prepare("INSERT IGNORE INTO cancel_service (id_user, username,description,status) VALUES (?, ?, ?, ?)");
    $descriptions = "0";
    $Status = "waiting";
    $stmt->bind_param("ssss", $from_id, $usernamepanel, $descriptions, $Status);
    $stmt->execute();
    $stmt->close();
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $usernamepanel);
    #-------------status----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['onhold']
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['stateus']['Unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['stateus']['Unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['users']['unlimited'];
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['stateus']['Notconsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['stateus']['day'] : $textbotlang['users']['stateus']['Unlimited'];
    #-----------------------------#
    $textinfoadmin = sprintf($textbotlang['users']['stateus']['RequestInfoRemove'], $from_id, $username, $nameloc['username'], $status_var, $nameloc['Service_location'], $nameloc['id_invoice'], $usedTrafficGb, $LastTraffic, $RemainingVolume, $expirationDate, $day);
    $confirmremoveadmin = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['removeconfig']['btnremoveuser'], 'callback_data' => "remoceserviceadmin-$usernamepanel"],
                ['text' => $textbotlang['users']['removeconfig']['rejectremove'], 'callback_data' => "rejectremoceserviceadmin-$usernamepanel"],
            ],
        ]
    ]);
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $textinfoadmin, $confirmremoveadmin, 'html');
        step('home', $admin);
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['removeconfig']['accepetrequest'], $keyboard, 'html');
}
#-----------usertest------------#
if ($text == $datatextbot['text_usertest']) {
    $locationproduct = select("marzban_panel", "*", null, null, "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpanel'], null, 'HTML');
        return;
    }
    if ($setting['get_number'] == "1" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "1")
        return;
    if ($user['limit_usertest'] <= 0) {
        sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard, 'html');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['Service']['Location'], $list_marzban_usertest, 'html');
}
if ($user['step'] == "createusertest" || preg_match('/locationtests_(.*)/', $datain, $dataget)) {
    if ($user['limit_usertest'] <= 0) {
        sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard, 'html');
        return;
    }
    if ($user['step'] == "createusertest") {
        $name_panel = $user['Processing_value_one'];
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
    } else {
        deletemessage($from_id, $message_id);
        $id_panel = $dataget[1];
        $marzban_list_get = select("marzban_panel", "*", "id", $id_panel, "select");
        $name_panel = $marzban_list_get['name_panel'];
    }
    $randomString = bin2hex(random_bytes(2));
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $name_panel, "select");

    if ($marzban_list_get['MethodUsername'] == $textbotlang['users']['customusername']) {
        if ($user['step'] != "createusertest") {
            step('createusertest', $from_id);
            update("user", "Processing_value_one", $name_panel, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
            return;
        }
    }
    $username_ac = strtolower(generateUsername($from_id, $marzban_list_get['MethodUsername'], $user['username'], $randomString, $text));
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $random_number = random_int(1000000, 9999999);
        $username_ac = $username_ac . $random_number;
    }
    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", strtotime("+" . $setting['time_usertest'] . "hours"))),
        'data_limit' => $setting['val_usertest'] * 1048576,
    );
    $dataoutput = $ManagePanel->createUser($name_panel, $username_ac, $datac, true);
    if ($dataoutput['username'] == null) {
        $dataoutput['msg'] = json_encode($dataoutput['msg']);
        sendmessage($from_id, $textbotlang['users']['usertest']['errorcreat'], $keyboard, 'html');
        $texterros = sprintf($textbotlang['users']['buy']['errorInCreate'], $dataoutput['msg'], $from_id, $username);
        foreach ($admin_ids as $admin) {
            sendmessage($admin, $texterros, null, 'html');
        }
        step('home', $from_id);
        return;
    }
    $date = time();
    $randomString = bin2hex(random_bytes(2));
    $sql = "INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $Status = "active";
    $usertest = "usertest";
    $price = "0";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $from_id);
    $stmt->bindParam(2, $randomString);
    $stmt->bindParam(3, $username_ac, PDO::PARAM_STR);
    $stmt->bindParam(4, $date);
    $stmt->bindParam(5, $name_panel, PDO::PARAM_STR);
    $stmt->bindParam(6, $usertest, PDO::PARAM_STR);
    $stmt->bindParam(7, $price);
    $stmt->bindParam(8, $setting['val_usertest']);
    $stmt->bindParam(9, $setting['time_usertest']);
    $stmt->bindParam(10, $Status);
    $stmt->execute();
    $config = "";
    $text_config = "";
    $output_config_link = "";
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
    }
    if ($marzban_list_get['configManual'] == "onconfig") {
        if (is_array($dataoutput['configs'])) {
            foreach ($dataoutput['configs'] as $configs) {
                $config .= "\n" . $configs;
            }
        }
        $text_config = $config;
    }
    $Shoppinginfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ]);
    if ($marzban_list_get['type'] == "wgdashboard") {
        $textcreatuser = sprintf($textbotlang['users']['buy']['createservicewg'], $username_ac, $marzban_list_get['name_panel'], $setting['time_usertest'], $setting['val_usertest']);
    } elseif ($marzban_list_get['type'] == "mikrotik") {
        $textcreatuser = sprintf($textbotlang['users']['buy']['createservice_mikrotik_test'], $username_ac, $dataoutput['subscription_url'], $marzban_list_get['name_panel'], $setting['time_usertest'], $setting['val_usertest']);
    } else {
        $textcreatuser = sprintf($textbotlang['users']['buy']['createservicetest'], $username_ac, $marzban_list_get['name_panel'], $setting['time_usertest'], $setting['val_usertest'], $output_config_link, $text_config);
    }
    if ($marzban_list_get['sublink'] == "onsublink" && $marzban_list_get['type'] != "mikrotik") {
        $urlimage = "$from_id$randomString.png";
        $writer = new PngWriter();
        $qrCode = QrCode::create($output_config_link)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(400)
            ->setMargin(0)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
        $result = $writer->write($qrCode, null, null);
        $result->saveToFile($urlimage);
        telegram('sendphoto', [
            'chat_id' => $from_id,
            'photo' => new CURLFile($urlimage),
            'reply_markup' => $Shoppinginfo,
            'caption' => $textcreatuser,
            'parse_mode' => "HTML",
        ]);
        if ($marzban_list_get['type'] == "wgdashboard") {
            $urlimage = "{$marzban_list_get['inboundid']}_{$dataoutput['username']}.conf";
            file_put_contents($urlimage, $output_config_link);
            sendDocument($from_id, $urlimage, $textbotlang['users']['buy']['configwg']);
            unlink($urlimage);
        }
        sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
        unlink($urlimage);
    } else {
        sendmessage($from_id, $textcreatuser, $Shoppinginfo, 'HTML');
        sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    }
    step('home', $from_id);
    $limit_usertest = $user['limit_usertest'] - 1;
    update("user", "limit_usertest", $limit_usertest, "id", $from_id);
    step('home', $from_id);
    $text_report = sprintf($textbotlang['Admin']['Report']['ReportTestCreate'], $from_id, $username, $username_ac, $first_name, $marzban_list_get['name_panel'], $user['number']);
    if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
}
#-----------help------------#
if ($text == $datatextbot['text_help'] || $datain == "helpbtn" || $text == "/help") {
    if ($setting['help_Status'] == "0") {
        sendmessage($from_id, $textbotlang['users']['help']['disablehelp'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
    step('sendhelp', $from_id);
} elseif ($user['step'] == "sendhelp") {
    $helpdata = select("help", "*", "name_os", $text, "select");
    if (strlen($helpdata['Media_os']) != 0) {
        if ($helpdata['type_Media_os'] == "video") {
            sendvideo($from_id, $helpdata['Media_os'], $helpdata['Description_os']);
        } elseif ($helpdata['type_Media_os'] == "photo")
            sendphoto($from_id, $helpdata['Media_os'], $helpdata['Description_os']);
    } else {
        sendmessage($from_id, $helpdata['Description_os'], $json_list_help, 'HTML');
=======
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
>>>>>>> ac58632 (feat(core): introduce modular index.php entrypoint with routing and admin dashboard)
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