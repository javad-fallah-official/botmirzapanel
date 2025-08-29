<?php

declare(strict_types=1);

namespace App\Shared\Constants;

/**
 * Application-wide constants
 */
class AppConstants
{
    // Application Information
    public const APP_NAME = 'BotMirzaPanel';
    public const APP_VERSION = '2.0.0';
    public const APP_ENVIRONMENT = 'production';
    
    // Cache Keys
    public const CACHE_USER_PREFIX = 'user:';
    public const CACHE_PAYMENT_PREFIX = 'payment:';
    public const CACHE_CONFIG_PREFIX = 'config:';
    public const CACHE_SESSION_PREFIX = 'session:';
    
    // Cache TTL (in seconds)
    public const CACHE_TTL_SHORT = 300;     // 5 minutes
    public const CACHE_TTL_MEDIUM = 1800;   // 30 minutes
    public const CACHE_TTL_LONG = 3600;     // 1 hour
    public const CACHE_TTL_EXTENDED = 86400; // 24 hours
    
    // Database Table Names
    public const TABLE_USERS = 'users';
    public const TABLE_PAYMENTS = 'payments';
    public const TABLE_CONFIGS = 'configs';
    public const TABLE_LOGS = 'logs';
    public const TABLE_SESSIONS = 'sessions';
    public const TABLE_VOUCHERS = 'vouchers';
    
    // User Roles
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_MODERATOR = 'moderator';
    
    // Payment Status
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_COMPLETED = 'completed';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_CANCELLED = 'cancelled';
    public const PAYMENT_REFUNDED = 'refunded';
    
    // User Status
    public const USER_ACTIVE = 'active';
    public const USER_INACTIVE = 'inactive';
    public const USER_BANNED = 'banned';
    public const USER_PENDING = 'pending';
    
    // Log Levels
    public const LOG_EMERGENCY = 'emergency';
    public const LOG_ALERT = 'alert';
    public const LOG_CRITICAL = 'critical';
    public const LOG_ERROR = 'error';
    public const LOG_WARNING = 'warning';
    public const LOG_NOTICE = 'notice';
    public const LOG_INFO = 'info';
    public const LOG_DEBUG = 'debug';
    
    // Event Names
    public const EVENT_USER_CREATED = 'user.created';
    public const EVENT_USER_UPDATED = 'user.updated';
    public const EVENT_USER_DELETED = 'user.deleted';
    public const EVENT_PAYMENT_CREATED = 'payment.created';
    public const EVENT_PAYMENT_COMPLETED = 'payment.completed';
    public const EVENT_PAYMENT_FAILED = 'payment.failed';
    
    // API Response Codes
    public const API_SUCCESS = 200;
    public const API_CREATED = 201;
    public const API_BAD_REQUEST = 400;
    public const API_UNAUTHORIZED = 401;
    public const API_FORBIDDEN = 403;
    public const API_NOT_FOUND = 404;
    public const API_VALIDATION_ERROR = 422;
    public const API_INTERNAL_ERROR = 500;
    
    // Telegram Bot Constants
    public const TELEGRAM_MAX_MESSAGE_LENGTH = 4096;
    public const TELEGRAM_MAX_CAPTION_LENGTH = 1024;
    public const TELEGRAM_MAX_INLINE_BUTTONS = 100;
    
    // File Upload Limits
    public const MAX_FILE_SIZE = 10485760; // 10MB
    public const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'txt'];
    
    // Security Constants
    public const PASSWORD_MIN_LENGTH = 8;
    public const SESSION_LIFETIME = 7200; // 2 hours
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOGIN_LOCKOUT_TIME = 900; // 15 minutes
    
    // Pagination
    public const DEFAULT_PAGE_SIZE = 20;
    public const MAX_PAGE_SIZE = 100;
    
    // Date Formats
    public const DATE_FORMAT = 'Y-m-d';
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s\Z';
    
    // Validation Rules
    public const EMAIL_REGEX = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    public const PHONE_REGEX = '/^\+?[1-9]\d{1,14}$/';
    public const USERNAME_REGEX = '/^[a-zA-Z0-9_]{3,20}$/';
}