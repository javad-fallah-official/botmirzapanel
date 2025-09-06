<?php
/**
 * Secure Database Functions
 * Replaces vulnerable legacy functions with secure implementations
 */

require_once 'vendor/autoload.php';

/**
 * Secure update function with proper parameterized queries
 */
function secure_update(string $table, string $field, $newValue, string $whereField = null, $whereValue = null): bool
{
    global $pdo;
    
    // Whitelist allowed tables
    $allowedTables = [
        "user", "help", "setting", "admin", "channels", "marzban_panel",
        "product", "invoice", "Payment_report", "Discount", "Giftcodeconsumed",
        "textbot", "PaySetting", "DiscountSell", "affiliates", "cancel_service", "category"
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new InvalidArgumentException("Table '{$table}' is not allowed");
    }
    
    // Whitelist allowed field names (basic validation)
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
        throw new InvalidArgumentException("Invalid field name: {$field}");
    }
    
    if ($whereField !== null) {
        // Validate where field name
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $whereField)) {
            throw new InvalidArgumentException("Invalid where field name: {$whereField}");
        }
        
        $sql = "UPDATE `{$table}` SET `{$field}` = ? WHERE `{$whereField}` = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$newValue, $whereValue]);
    } else {
        $sql = "UPDATE `{$table}` SET `{$field}` = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$newValue]);
    }
}

/**
 * Secure select function with proper parameterized queries
 */
function secure_select(string $table, string $field, string $whereField = null, $whereValue = null, string $type = "select")
{
    global $pdo;
    
    // Whitelist allowed tables
    $allowedTables = [
        "user", "help", "setting", "admin", "channels", "marzban_panel",
        "product", "invoice", "Payment_report", "Discount", "Giftcodeconsumed",
        "textbot", "PaySetting", "DiscountSell", "affiliates", "cancel_service", "category"
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new InvalidArgumentException("Table '{$table}' is not allowed");
    }
    
    // Validate field names (allow * and comma-separated fields)
    if ($field !== '*' && !preg_match('/^[a-zA-Z_*][a-zA-Z0-9_,\s*]*$/', $field)) {
        throw new InvalidArgumentException("Invalid field specification: {$field}");
    }
    
    $query = "SELECT {$field} FROM `{$table}`";
    $params = [];
    
    if ($whereField !== null) {
        // Validate where field name
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $whereField)) {
            throw new InvalidArgumentException("Invalid where field name: {$whereField}");
        }
        
        $query .= " WHERE `{$whereField}` = ?";
        $params[] = $whereValue;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        switch ($type) {
            case "count":
                return $stmt->rowCount();
            case "FETCH_COLUMN":
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            case "fetchAll":
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            default:
                return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Secure select query failed: " . $e->getMessage());
        throw new RuntimeException("Database query failed");
    }
}

/**
 * Secure step function with proper parameterized queries
 */
function secure_step(string $step, int $from_id): bool
{
    global $pdo;
    
    // Validate step value
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $step)) {
        throw new InvalidArgumentException("Invalid step value: {$step}");
    }
    
    $stmt = $pdo->prepare('UPDATE `user` SET `step` = ? WHERE `id` = ?');
    return $stmt->execute([$step, $from_id]);
}

/**
 * Secure insert function
 */
function secure_insert(string $table, array $data): int
{
    global $pdo;
    
    // Whitelist allowed tables
    $allowedTables = [
        "user", "help", "setting", "admin", "channels", "marzban_panel",
        "product", "invoice", "Payment_report", "Discount", "Giftcodeconsumed",
        "textbot", "PaySetting", "DiscountSell", "affiliates", "cancel_service", "category"
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new InvalidArgumentException("Table '{$table}' is not allowed");
    }
    
    if (empty($data)) {
        throw new InvalidArgumentException("Data array cannot be empty");
    }
    
    // Validate field names
    foreach (array_keys($data) as $field) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            throw new InvalidArgumentException("Invalid field name: {$field}");
        }
    }
    
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    return (int) $pdo->lastInsertId();
}

/**
 * Secure delete function
 */
function secure_delete(string $table, array $where): int
{
    global $pdo;
    
    // Whitelist allowed tables
    $allowedTables = [
        "user", "help", "setting", "admin", "channels", "marzban_panel",
        "product", "invoice", "Payment_report", "Discount", "Giftcodeconsumed",
        "textbot", "PaySetting", "DiscountSell", "affiliates", "cancel_service", "category"
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new InvalidArgumentException("Table '{$table}' is not allowed");
    }
    
    if (empty($where)) {
        throw new InvalidArgumentException("WHERE conditions cannot be empty for delete operations");
    }
    
    // Validate field names
    foreach (array_keys($where) as $field) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            throw new InvalidArgumentException("Invalid field name: {$field}");
        }
    }
    
    $conditions = [];
    foreach (array_keys($where) as $field) {
        $conditions[] = "`{$field}` = ?";
    }
    
    $sql = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $conditions);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($where));
    
    return $stmt->rowCount();
}

/**
 * Migration function to replace legacy function calls
 */
function migrate_legacy_functions(): void
{
    // This function can be used to systematically replace legacy function calls
    // with secure versions throughout the codebase
    
    echo "Legacy function migration utility loaded.\n";
    echo "Use secure_select(), secure_update(), secure_insert(), secure_delete() instead of legacy functions.\n";
}

// Auto-load secure functions
migrate_legacy_functions();