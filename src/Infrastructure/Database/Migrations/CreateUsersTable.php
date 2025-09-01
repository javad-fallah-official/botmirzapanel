<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Database\Migrations;

use BotMirzaPanel\Database\DatabaseManager;

/**
 * Migration to create or update the users table for the new domain model
 */
class CreateUsersTable
{
    private DatabaseManager $db;
    
    public function __construct(DatabaseManager $db): void
    {
        $this->db = $db;
    }
    
    /**
     * Run the migration
     */
    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY,
                telegram_chat_id VARCHAR(50) NOT NULL UNIQUE,
                username VARCHAR(50) NULL UNIQUE,
                first_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NULL,
                email VARCHAR(255) NULL UNIQUE,
                phone_number VARCHAR(20) NULL UNIQUE,
                password_hash VARCHAR(255) NULL,
                balance DECIMAL(10,2) DEFAULT 0.00,
                status ENUM('active', 'inactive', 'banned', 'pending') DEFAULT 'active',
                is_premium BOOLEAN DEFAULT FALSE,
                referred_by VARCHAR(36) NULL,
                referral_code VARCHAR(20) NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_telegram_chat_id (telegram_chat_id),
                INDEX idx_username (username),
                INDEX idx_email (email),
                INDEX idx_phone_number (phone_number),
                INDEX idx_status (status),
                INDEX idx_referral_code (referral_code),
                INDEX idx_referred_by (referred_by),
                INDEX idx_created_at (created_at),
                
                FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->db->execute($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->execute('DROP TABLE IF EXISTS users');
    }
    
    /**
     * Check if migration needs to be run
     */
    public function shouldRun(): bool
    {
        try {
            $result = $this->db->selectOne('DESCRIBE users');
            return false; // Table exists
        } catch (\Exception $e) {
            return true; // Table doesn't exist
        }
    }
    
    /**
     * Update existing table structure if needed
     */
    public function updateExistingTable(): void
    {
        // Check and add missing columns
        $columns = $this->getTableColumns();
        
        $requiredColumns = [
            'id' => 'VARCHAR(36) PRIMARY KEY',
            'telegram_chat_id' => 'VARCHAR(50) NOT NULL',
            'username' => 'VARCHAR(50) NULL',
            'first_name' => 'VARCHAR(100) NULL',
            'last_name' => 'VARCHAR(100) NULL',
            'email' => 'VARCHAR(255) NULL',
            'phone_number' => 'VARCHAR(20) NULL',
            'password_hash' => 'VARCHAR(255) NULL',
            'balance' => 'DECIMAL(10,2) DEFAULT 0.00',
            'status' => "ENUM('active', 'inactive', 'banned', 'pending') DEFAULT 'active'",
            'is_premium' => 'BOOLEAN DEFAULT FALSE',
            'referred_by' => 'VARCHAR(36) NULL',
            'referral_code' => 'VARCHAR(20) NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $this->db->execute("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        }
        
        // Add indexes if they don't exist
        $this->addIndexIfNotExists('idx_telegram_chat_id', 'telegram_chat_id');
        $this->addIndexIfNotExists('idx_username', 'username');
        $this->addIndexIfNotExists('idx_email', 'email');
        $this->addIndexIfNotExists('idx_phone_number', 'phone_number');
        $this->addIndexIfNotExists('idx_status', 'status');
        $this->addIndexIfNotExists('idx_referral_code', 'referral_code');
        $this->addIndexIfNotExists('idx_referred_by', 'referred_by');
        $this->addIndexIfNotExists('idx_created_at', 'created_at');
    }
    
    private function getTableColumns(): array
    {
        $result = $this->db->select('DESCRIBE users');
        return array_column($result, 'Field');
    }
    
    private function addIndexIfNotExists(string $indexName, string $column): void
    {
        try {
            $this->db->execute("CREATE INDEX {$indexName} ON users ({$column})");
        } catch (\Exception $e) {
            // Index might already exist, ignore error
        }
    }
}