<?php
/**
 * TRENDYPOST - Database Class
 * ============================
 * PDO-based database handler with prepared statements.
 * Secure, optimized, and singleton-patterned.
 * Includes proper timezone handling for consistent date/time operations.
 */

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

require_once CONFIG_PATH . 'config.php';

class Database {
    private static ?PDO $instance = null;
    private static ?string $timezone = null;
    
    /**
     * Get PDO instance (singleton) with proper timezone
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => APP_DEBUG ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ];
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // Set character set and timezone
                self::setConnectionDefaults();
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                if (APP_DEBUG) {
                    die("Database connection failed: " . $e->getMessage());
                }
                die("Service temporarily unavailable. Please try again later.");
            }
        }
        return self::$instance;
    }
    
    /**
     * Set connection defaults including timezone
     */
    private static function setConnectionDefaults(): void {
        try {
            // Set charset and collation
            self::$instance->exec("SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE);
            
            // Set timezone to match PHP timezone
            $phpTimezone = date_default_timezone_get();
            $timezoneOffset = self::getTimezoneOffset($phpTimezone);
            
            // Try to set MySQL timezone to match PHP
            try {
                self::$instance->exec("SET time_zone = '" . $timezoneOffset . "'");
                self::$timezone = $timezoneOffset;
            } catch (PDOException $e) {
                // If named timezone fails, try using offset
                try {
                    $offsetMinutes = self::getTimezoneOffsetMinutes($phpTimezone);
                    self::$instance->exec("SET time_zone = '" . $offsetMinutes . "'");
                    self::$timezone = $offsetMinutes;
                } catch (PDOException $e2) {
                    // If both fail, log but continue
                    error_log("Could not set MySQL timezone: " . $e2->getMessage());
                    self::$timezone = '+00:00';
                }
            }
            
            // Set SQL mode
            self::$instance->exec("SET sql_mode = 'NO_ENGINE_SUBSTITUTION'");
            
        } catch (PDOException $e) {
            error_log("Failed to set connection defaults: " . $e->getMessage());
        }
    }
    
    /**
     * Get timezone offset for MySQL (e.g., '+01:00')
     */
    private static function getTimezoneOffset(string $timezone): string {
        try {
            $dt = new DateTime('now', new DateTimeZone($timezone));
            $offset = $dt->getOffset();
            $hours = floor($offset / 3600);
            $minutes = abs(($offset % 3600) / 60);
            return sprintf('%+03d:%02d', $hours, $minutes);
        } catch (Exception $e) {
            error_log("Timezone offset calculation failed: " . $e->getMessage());
            return '+00:00';
        }
    }
    
    /**
     * Get timezone offset in minutes for MySQL
     */
    private static function getTimezoneOffsetMinutes(string $timezone): string {
        try {
            $dt = new DateTime('now', new DateTimeZone($timezone));
            $offset = $dt->getOffset();
            $hours = floor($offset / 3600);
            $minutes = abs(($offset % 3600) / 60);
            return sprintf('%+03d:%02d', $hours, $minutes);
        } catch (Exception $e) {
            error_log("Timezone offset calculation failed: " . $e->getMessage());
            return '+00:00';
        }
    }
    
    /**
     * Get the current database timezone
     */
    public static function getTimezone(): ?string {
        if (self::$timezone === null) {
            try {
                self::$timezone = self::fetchColumn("SELECT @@session.time_zone");
            } catch (Exception $e) {
                self::$timezone = '+00:00';
            }
        }
        return self::$timezone;
    }
    
    /**
     * Get current server time from database
     */
    public static function now(): string {
        return self::fetchColumn("SELECT NOW()");
    }
    
    /**
     * Get current UNIX timestamp from database
     */
    public static function unixNow(): int {
        return (int) self::fetchColumn("SELECT UNIX_TIMESTAMP(NOW())");
    }
    
    /**
     * Execute a prepared statement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single column value
     */
    public static function fetchColumn(string $sql, array $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert and get last ID
     */
    public static function insert(string $table, array $data): int {
        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$placeholders})";
        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }
    
    /**
     * Update records
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}` = ?";
        }
        $setClause = implode(', ', $set);
        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        self::query($sql, array_merge(array_values($data), $whereParams));
        return self::getInstance()->lastInsertId() ? 1 : 0;
    }
    
    /**
     * Delete records
     */
    public static function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): bool {
        return self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): bool {
        return self::getInstance()->rollBack();
    }
    
    /**
     * Get last insert ID
     */
    public static function lastId(): int {
        return (int) self::getInstance()->lastInsertId();
    }
    
    /**
     * Quote identifier (table/column names)
     */
    public static function quoteIdentifier(string $name): string {
        return '`' . str_replace('`', '``', $name) . '`';
    }
    
    /**
     * Check if a table exists
     */
    public static function tableExists(string $table): bool {
        $sql = "SHOW TABLES LIKE ?";
        $result = self::fetchColumn($sql, [$table]);
        return $result !== false;
    }
    
    /**
     * Count rows in table
     */
    public static function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";
        return (int) self::fetchColumn($sql, $params);
    }
    
    /**
     * Debug timezone information
     */
    public static function debugTimezone(): array {
        $info = [];
        $info['php_timezone'] = date_default_timezone_get();
        $info['php_time'] = date('Y-m-d H:i:s');
        $info['php_unix'] = time();
        $info['mysql_timezone'] = self::getTimezone();
        $info['mysql_now'] = self::now();
        $info['mysql_unix'] = self::unixNow();
        $info['mysql_timezone_set'] = self::fetchColumn("SELECT @@session.time_zone");
        $info['mysql_global_tz'] = self::fetchColumn("SELECT @@global.time_zone");
        return $info;
    }
    
    /**
     * Escape string for LIKE queries
     */
    public static function escapeLike(string $value): string {
        return addcslashes($value, '%_\\');
    }
    
    /**
     * Build WHERE clause from array
     */
    public static function buildWhere(array $conditions, string $operator = 'AND'): array {
        $where = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                // IN clause
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $where[] = "`{$key}` IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $where[] = "`{$key}` = ?";
                $params[] = $value;
            }
        }
        return [
            'sql' => implode(" {$operator} ", $where),
            'params' => $params
        ];
    }
    
    /**
     * Private constructor for singleton
     */
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}