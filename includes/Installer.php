<?php

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class Installer
{
    public const LOCK_FILE = 'installed.lock';
    public const LOCAL_FILE = 'local.php';

    public static function lockPath(): string
    {
        return rtrim(CONFIG_PATH, '/') . '/' . self::LOCK_FILE;
    }

    public static function localPath(): string
    {
        return rtrim(CONFIG_PATH, '/') . '/' . self::LOCAL_FILE;
    }

    public static function isInstalled(): bool
    {
        return is_file(self::lockPath());
    }

    public static function validateDatabase(array $state): array
    {
        $errors = [];
        if (trim((string) ($state['db_host'] ?? '')) === '') {
            $errors[] = 'Database host is required.';
        }
        if (trim((string) ($state['db_name'] ?? '')) === '') {
            $errors[] = 'Database name is required.';
        }
        if (trim((string) ($state['db_user'] ?? '')) === '') {
            $errors[] = 'Database user is required.';
        }
        return $errors;
    }

    public static function validateSite(array $state): array
    {
        $errors = [];
        if (trim((string) ($state['site_name'] ?? '')) === '') {
            $errors[] = 'Site name is required.';
        }
        $fresh = ((string) ($state['wipe'] ?? '0') === '1') || empty($state['existing']) || (int) ($state['user_count'] ?? 0) === 0;
        $pass = (string) ($state['admin_password'] ?? '');
        $user = trim((string) ($state['admin_username'] ?? ''));
        $email = trim((string) ($state['admin_email'] ?? ''));
        if ($fresh || $pass !== '' || $user !== '' || $email !== '') {
            if ($user === '' || $email === '') {
                $errors[] = 'Superadmin username and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Superadmin email is invalid.';
            }
            if ($fresh && $pass === '') {
                $errors[] = 'Superadmin password is required.';
            }
            if ($pass !== '' && strlen($pass) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            if ($pass !== '' && $pass !== (string) ($state['admin_password_confirm'] ?? '')) {
                $errors[] = 'Passwords do not match.';
            }
        }
        return $errors;
    }

    public static function bootstrapSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (defined('SESSION_NAME')) {
                session_name(SESSION_NAME);
            }
            session_start();
        }
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
    }

    public static function state(): array
    {
        $defaults = [
            'step' => 1,
            'db_host' => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
            'db_name' => defined('DB_NAME') ? DB_NAME : 'trendypost',
            'db_user' => defined('DB_USER') ? DB_USER : 'trendypost',
            'db_pass' => defined('DB_PASS') ? DB_PASS : '',
            'wipe' => '0',
            'existing' => false,
            'table_count' => 0,
            'user_count' => 0,
            'post_count' => 0,
            'site_name' => 'TrendyPost',
            'site_tagline' => 'Your Source for Trending News & Stories',
            'site_email' => 'admin@trendypost.com',
            'admin_username' => 'admin',
            'admin_email' => 'admin@trendypost.com',
            'admin_password' => '',
            'admin_password_confirm' => '',
        ];
        $saved = $_SESSION['install'] ?? [];
        return array_merge($defaults, is_array($saved) ? $saved : []);
    }

    public static function saveState(array $data): void
    {
        $_SESSION['install'] = array_merge(self::state(), $data);
    }

    public static function clearState(): void
    {
        unset($_SESSION['install']);
    }

    public static function requirements(): array
    {
        $writable = [
            'config' => defined('CONFIG_PATH') ? CONFIG_PATH : dirname(__DIR__) . '/config/',
            'uploads' => defined('UPLOADS_PATH') ? UPLOADS_PATH : dirname(__DIR__) . '/assets/uploads/',
            'cache' => defined('CACHE_PATH') ? CACHE_PATH : dirname(__DIR__) . '/cache/',
            'logs' => defined('LOGS_PATH') ? LOGS_PATH : dirname(__DIR__) . '/logs/',
        ];
        foreach ($writable as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
        $checks = [
            ['label' => 'PHP 8.0+', 'ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'detail' => PHP_VERSION],
            ['label' => 'PDO MySQL', 'ok' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? 'loaded' : 'missing'],
            ['label' => 'mbstring', 'ok' => extension_loaded('mbstring'), 'detail' => extension_loaded('mbstring') ? 'loaded' : 'missing'],
            ['label' => 'openssl', 'ok' => extension_loaded('openssl'), 'detail' => extension_loaded('openssl') ? 'loaded' : 'missing'],
            ['label' => 'json', 'ok' => extension_loaded('json'), 'detail' => extension_loaded('json') ? 'loaded' : 'missing'],
            ['label' => 'curl', 'ok' => extension_loaded('curl'), 'detail' => extension_loaded('curl') ? 'loaded' : 'missing'],
        ];
        foreach ($writable as $name => $dir) {
            $checks[] = [
                'label' => $name . ' writable',
                'ok' => is_dir($dir) && is_writable($dir),
                'detail' => $dir,
            ];
        }
        $ok = true;
        foreach ($checks as $c) {
            if (!$c['ok']) {
                $ok = false;
                break;
            }
        }
        return ['ok' => $ok, 'checks' => $checks];
    }

    public static function connect(string $host, string $user, string $pass, ?string $db = null): PDO
    {
        $dsn = 'mysql:host=' . $host . ';charset=utf8mb4';
        if ($db !== null && $db !== '') {
            $dsn .= ';dbname=' . $db;
        }
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec("SET sql_mode = 'NO_ENGINE_SUBSTITUTION'");
        return $pdo;
    }

    public static function inspect(PDO $pdo): array
    {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $info = [
            'existing' => count($tables) > 0,
            'table_count' => count($tables),
            'user_count' => 0,
            'post_count' => 0,
            'tables' => $tables,
        ];
        if (in_array('users', $tables, true)) {
            $info['user_count'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        }
        if (in_array('posts', $tables, true)) {
            $info['post_count'] = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        }
        return $info;
    }

    public static function ensureDatabase(string $host, string $user, string $pass, string $name): PDO
    {
        $safe = str_replace('`', '', $name);
        try {
            return self::connect($host, $user, $pass, $safe);
        } catch (PDOException $e) {
            $server = self::connect($host, $user, $pass);
            try {
                $server->exec('CREATE DATABASE IF NOT EXISTS `' . $safe . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (PDOException $createError) {
                throw new RuntimeException('Could not open or create database `' . $safe . '`: ' . $createError->getMessage());
            }
            return self::connect($host, $user, $pass, $safe);
        }
    }

    public static function dropAllTables(PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', (string) $table) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public static function runSqlFile(PDO $pdo, string $path, bool $lenient = false): void
    {
        if (!is_file($path)) {
            throw new RuntimeException('SQL file missing: ' . $path);
        }
        $sql = (string) file_get_contents($path);
        $sql = preg_replace('/^\s*CREATE DATABASE\b.*$/mi', '', $sql) ?? $sql;
        $sql = preg_replace('/^\s*USE\s+[`\w]+;?\s*$/mi', '', $sql) ?? $sql;
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (self::splitSql($sql) as $stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    if ($lenient && self::isIgnorableSqlError($e)) {
                        continue;
                    }
                    throw new RuntimeException('SQL error in ' . basename($path) . ': ' . $e->getMessage(), (int) $e->getCode(), $e);
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public static function applyMigrations(PDO $pdo): void
    {
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/';
        foreach (['analytics-migration.sql', 'indexing-migration.sql', 'ai-sources.sql'] as $file) {
            $path = $root . 'database/' . $file;
            if (is_file($path)) {
                self::runSqlFile($pdo, $path, true);
            }
        }
        self::ensurePostIngestColumns($pdo);
    }

    public static function ensurePostIngestColumns(PDO $pdo): void
    {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('posts', $tables, true)) {
            return;
        }
        $columns = $pdo->query('SHOW COLUMNS FROM posts')->fetchAll(PDO::FETCH_COLUMN);
        $needed = [
            'source_id' => 'INT UNSIGNED DEFAULT NULL',
            'original_url' => 'VARCHAR(500) DEFAULT NULL',
            'original_title' => 'VARCHAR(255) DEFAULT NULL',
        ];
        foreach ($needed as $name => $def) {
            if (!in_array($name, $columns, true)) {
                $pdo->exec('ALTER TABLE posts ADD COLUMN `' . $name . '` ' . $def);
            }
        }
        $indexes = $pdo->query('SHOW INDEX FROM posts')->fetchAll();
        $indexNames = [];
        foreach ($indexes as $row) {
            $indexNames[] = (string) ($row['Key_name'] ?? '');
        }
        if (!in_array('idx_original_url', $indexNames, true)) {
            try {
                $pdo->exec('ALTER TABLE posts ADD UNIQUE INDEX idx_original_url (original_url)');
            } catch (PDOException $e) {
                if (!self::isIgnorableSqlError($e)) {
                    throw $e;
                }
            }
        }
    }

    private static function isIgnorableSqlError(PDOException $e): bool
    {
        $code = (int) ($e->errorInfo[1] ?? 0);
        return in_array($code, [1050, 1054, 1060, 1061, 1062, 1068, 1091, 1146, 1826], true);
    }

    public static function importSchema(PDO $pdo): void
    {
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/';
        $master = $root . 'database/master_db.sql';
        $schema = $root . 'database/schema.sql';
        if (is_file($master)) {
            self::runSqlFile($pdo, $master);
        } elseif (is_file($schema)) {
            self::runSqlFile($pdo, $schema);
        } else {
            throw new RuntimeException('No schema file found.');
        }
        self::applyMigrations($pdo);
    }

    public static function upsertSetting(PDO $pdo, string $key, string $value, string $group = 'general', int $public = 1): void
    {
        $stmt = $pdo->prepare('SELECT id FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $id = $stmt->fetchColumn();
        if ($id) {
            $upd = $pdo->prepare('UPDATE settings SET setting_value = ?, setting_group = ? WHERE id = ?');
            $upd->execute([$value, $group, $id]);
            return;
        }
        $ins = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, setting_group, is_public) VALUES (?, ?, ?, ?)');
        $ins->execute([$key, $value, $group, $public]);
    }

    public static function upsertAdmin(PDO $pdo, string $username, string $email, string $password, bool $replaceExisting): void
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, display_name = ?, role_id = 1, status = 'active', email_verified = 1 WHERE role_id = 1 ORDER BY id ASC LIMIT 1");
        $upd->execute([$username, $email, $hash, $username]);
        if ($upd->rowCount() > 0) {
            return;
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $exists->execute([$email, $username]);
        $id = $exists->fetchColumn();
        if ($id) {
            $upd = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, display_name = ?, role_id = 1, status = 'active', email_verified = 1 WHERE id = ?");
            $upd->execute([$username, $email, $hash, $username, $id]);
            return;
        }
        $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, display_name, first_name, last_name, role_id, status, email_verified) VALUES (?, ?, ?, ?, 'Super', 'Admin', 1, 'active', 1)");
        $ins->execute([$username, $email, $hash, $username]);
    }

    public static function writeLocal(array $db, string $encKey, string $appKey): void
    {
        $path = self::localPath();
        $body = "<?php\n"
            . "if (!defined('TRENDYPOST')) {\n    define('TRENDYPOST', true);\n}\n"
            . self::defineLine('DB_HOST', $db['host'])
            . self::defineLine('DB_NAME', $db['name'])
            . self::defineLine('DB_USER', $db['user'])
            . self::defineLine('DB_PASS', $db['pass'])
            . self::defineLine('ENCRYPTION_KEY', $encKey)
            . self::defineLine('APP_KEY', $appKey);
        if (file_put_contents($path, $body) === false) {
            throw new RuntimeException('Could not write ' . $path);
        }
        @chmod($path, 0640);
    }

    public static function writeLock(): void
    {
        $path = self::lockPath();
        $body = 'installed_at=' . gmdate('c') . "\n";
        if (file_put_contents($path, $body) === false) {
            throw new RuntimeException('Could not write install lock.');
        }
    }

    public static function run(array $state): void
    {
        $host = trim((string) $state['db_host']);
        $name = trim((string) $state['db_name']);
        $user = trim((string) $state['db_user']);
        $pass = (string) $state['db_pass'];
        $wipe = (string) ($state['wipe'] ?? '0') === '1';

        $pdo = self::ensureDatabase($host, $user, $pass, $name);
        $info = self::inspect($pdo);

        if ($wipe && $info['existing']) {
            self::dropAllTables($pdo);
            self::importSchema($pdo);
        } elseif (!$info['existing']) {
            self::importSchema($pdo);
        } else {
            self::applyMigrations($pdo);
        }

        $siteName = trim((string) $state['site_name']) ?: 'TrendyPost';
        $tagline = trim((string) $state['site_tagline']);
        $email = trim((string) $state['site_email']);
        self::upsertSetting($pdo, 'site_name', $siteName, 'general', 1);
        self::upsertSetting($pdo, 'site_tagline', $tagline, 'general', 1);
        self::upsertSetting($pdo, 'site_email', $email, 'general', 1);

        $adminUser = trim((string) $state['admin_username']);
        $adminEmail = trim((string) $state['admin_email']);
        $adminPass = (string) $state['admin_password'];
        $fresh = $wipe || !$info['existing'] || (int) $info['user_count'] === 0;
        if ($adminPass !== '' && ($fresh || $adminUser !== '')) {
            if ($adminUser === '' || $adminEmail === '') {
                throw new RuntimeException('Admin username and email are required.');
            }
            self::upsertAdmin($pdo, $adminUser, $adminEmail, $adminPass, $wipe || $fresh);
        } elseif ($fresh) {
            throw new RuntimeException('Create a superadmin account to finish installation.');
        }

        self::writeLocal(
            ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass],
            bin2hex(random_bytes(32)),
            'base64:' . base64_encode(random_bytes(32))
        );
        self::writeLock();
        self::clearState();
    }

    private static function defineLine(string $name, string $value): string
    {
        return "if (!defined('" . $name . "')) {\n    define('" . $name . "', " . var_export($value, true) . ");\n}\n";
    }

    private static function splitSql(string $sql): array
    {
        $stmts = [];
        $buf = '';
        $len = strlen($sql);
        $quote = null;
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $n3 = substr($sql, $i, 3);
            $n2 = substr($sql, $i, 2);
            if ($quote === null && $n3 === '-- ') {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                continue;
            }
            if ($quote === null && $n2 === '/*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                continue;
            }
            if ($quote === null && $ch === '#') {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                continue;
            }
            if ($quote !== null) {
                $buf .= $ch;
                if ($ch === $quote && ($sql[$i - 1] ?? '') !== '\\') {
                    $quote = null;
                }
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
                $buf .= $ch;
                continue;
            }
            if ($ch === ';') {
                $stmt = trim($buf);
                $buf = '';
                if ($stmt !== '') {
                    $stmts[] = $stmt;
                }
                continue;
            }
            $buf .= $ch;
        }
        $tail = trim($buf);
        if ($tail !== '') {
            $stmts[] = $tail;
        }
        return $stmts;
    }
}
