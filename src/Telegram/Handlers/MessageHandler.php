<?php

namespace BotMirzaPanel\Telegram\Handlers;

use BotMirzaPanel\Telegram\TelegramBot;

/**
 * Handles regular text messages from users
 * Processes user interactions and state management
 */
class MessageHandler
{
    private TelegramBot $bot;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Handle incoming message
     */
    public function handle(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $messageId = $message['message_id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Get or create user
        $user = $userService->getOrCreateUser($userId, $message['from']);
        
        // Check if user is blocked
        if ($userService->isUserBlocked($userId)) {
            return;
        }
        
        // Rate limiting check
        if ($userService->isRateLimited($userId)) {
            return;
        }
        
        // Handle different message types
        if (isset($message['contact'])) {
            $this->handleContact($message, $services);
        } elseif (isset($message['photo'])) {
            $this->handlePhoto($message, $services);
        } elseif (isset($message['document'])) {
            $this->handleDocument($message, $services);
        } else {
            $this->handleTextMessage($message, $services);
        }
    }

    /**
     * Handle text messages
     */
    private function handleTextMessage(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        $text = $message['text'] ?? '';
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Get user's current step/state
        $userStep = $userService->getUserStep($userId);
        
        // Handle based on current step
        switch ($userStep) {
            case 'home':
                $this->handleHomeStep($message, $services);
                break;
                
            case 'waiting_phone':
                $this->handlePhoneInput($message, $services);
                break;
                
            case 'waiting_payment_amount':
                $this->handlePaymentAmount($message, $services);
                break;
                
            case 'admin_broadcast':
                $this->handleAdminBroadcast($message, $services);
                break;
                
            default:
                $this->handleDefaultStep($message, $services);
                break;
        }
    }

    /**
     * Handle home step messages
     */
    private function handleHomeStep(array $message, array $services): void
    {
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\Config\ConfigManager $config */
        $config = $services['config'];
        
        // Check if user is admin
        $adminId = $config->get('admin.id');
        if ($userId == $adminId) {
            $this->handleAdminMessage($message, $services);
            return;
        }
        
        // Handle user menu selections
        $this->handleUserMenuSelection($message, $services);
    }

    /**
     * Handle admin messages
     */
    private function handleAdminMessage(array $message, array $services): void
    {
        $text = $message['text'] ?? '';
        
        // Load text messages
        $textMessages = $this->loadTextMessages();
        
        // Handle admin menu items
        if ($text === $textMessages['Admin']['keyboardadmin']['ManageUser']) {
            $this->showUserManagement($message, $services);
        } elseif ($text === $textMessages['Admin']['keyboardadmin']['setting']) {
            $this->showSettings($message, $services);
        } elseif ($text === $textMessages['Admin']['keyboardadmin']['SendMessagetoAll']) {
            $this->initiateBroadcast($message, $services);
        }
        // Add more admin handlers...
    }

    /**
     * Handle user menu selections
     */
    private function handleUserMenuSelection(array $message, array $services): void
    {
        $text = $message['text'] ?? '';
        
        // Load text messages
        $textMessages = $this->loadTextMessages();
        
        // Handle user menu items
        if ($text === $textMessages['users']['keyboarduser']['myservice']) {
            $this->showUserServices($message, $services);
        } elseif ($text === $textMessages['users']['keyboarduser']['buyservice']) {
            $this->showServiceCatalog($message, $services);
        } elseif ($text === $textMessages['users']['keyboarduser']['Balance']) {
            $this->showBalance($message, $services);
        } elseif ($text === $textMessages['users']['keyboarduser']['support']) {
            $this->showSupport($message, $services);
        }
        // Add more user handlers...
    }

    /**
     * Handle contact sharing
     */
    private function handleContact(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        $contact = $message['contact'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Validate and save phone number
        if ($contact['user_id'] == $userId) {
            $userService->updateUserPhone($userId, $contact['phone_number']);
            $userService->setUserStep($userId, 'home');
            
            // Send confirmation and show main menu
            $this->showMainMenu($message, $services);
        }
    }

    /**
     * Handle photo uploads
     */
    private function handlePhoto(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        $userStep = $userService->getUserStep($userId);
        
        // Handle photo based on current step
        if ($userStep === 'waiting_payment_receipt') {
            $this->handlePaymentReceipt($message, $services);
        }
    }

    /**
     * Handle document uploads
     */
    private function handleDocument(array $message, array $services): void
    {
        // Similar to photo handling
        $this->handlePhoto($message, $services);
    }

    /**
     * Handle phone number input
     */
    private function handlePhoneInput(array $message, array $services): void
    {
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        
        /** @var \BotMirzaPanel\User\UserService $userService */
        $userService = $services['user'];
        
        // Validate phone number format
        if ($userService->validatePhoneNumber($text)) {
            $userService->updateUserPhone($userId, $text);
            $userService->setUserStep($userId, 'home');
            $this->showMainMenu($message, $services);
        } else {
            $this->bot->sendMessage(
                $userId,
                "Invalid phone number format. Please try again."
            );
        }
    }

    /**
     * Handle payment amount input
     */
    private function handlePaymentAmount(array $message, array $services): void
    {
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        
        if (is_numeric($text) && $text > 0) {
            /** @var \BotMirzaPanel\User\UserService $userService */
            $userService = $services['user'];
            
            $userService->setProcessingValue($userId, $text);
            $userService->setUserStep($userId, 'select_payment_method');
            
            $this->showPaymentMethods($message, $services);
        } else {
            $this->bot->sendMessage(
                $userId,
                "Please enter a valid amount."
            );
        }
    }

    /**
     * Show main menu
     */
    private function showMainMenu(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        // Load keyboard and text
        $keyboard = $this->loadKeyboard('user_main');
        $text = $this->loadTextMessages()['users']['welcome'];
        
        $this->bot->sendMessage($userId, $text, $keyboard);
    }

    /**
     * Show payment methods
     */
    private function showPaymentMethods(array $message, array $services): void
    {
        $userId = $message['from']['id'];
        
        $keyboard = $this->loadKeyboard('payment_methods');
        $text = "Please select a payment method:";
        
        $this->bot->sendMessage($userId, $text, $keyboard);
    }

    /**
     * Load text messages (placeholder - should load from text.php)
     */
    private function loadTextMessages(): array
    {
        // This should load from the text.php file
        // For now, return empty array
        return [];
    }

    /**
     * Load keyboard (placeholder - should load from keyboard.php)
     */
    private function loadKeyboard(string $type): array
    {
        // This should load from the keyboard.php file
        // For now, return empty array
        return [];
    }

    // Placeholder methods for various handlers
    private function showUserServices(array $message, array $services): void {}
    private function showServiceCatalog(array $message, array $services): void {}
    private function showBalance(array $message, array $services): void {}
    private function showSupport(array $message, array $services): void {}
    private function showUserManagement(array $message, array $services): void {}
    private function showSettings(array $message, array $services): void {}
    private function initiateBroadcast(array $message, array $services): void {}
    private function handlePaymentReceipt(array $message, array $services): void {}
    private function handleAdminBroadcast(array $message, array $services): void {}
    private function handleDefaultStep(array $message, array $services): void {}
}