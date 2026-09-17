<?php
/**
 * TRENDYPOST - Authentication & RBAC System
 * ==========================================
 * Complete role-based access control with session management.
 */

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Helpers.php';

class Auth {
    private static ?array $currentUser = null;
    private static ?array $currentRole = null;
    private static array $permissions = [];
    
    /**
     * Initialize session and auth
     */
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        
        // Load user if session exists
        if (!empty($_SESSION['user_id'])) {
            self::loadUser($_SESSION['user_id']);
        }
        
        // Check remember token
        if (!self::check() && !empty($_COOKIE['remember'])) {
            self::loginWithToken($_COOKIE['remember']);
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function check(): bool {
        return self::$currentUser !== null && self::$currentUser['status'] === 'active';
    }
    
    /**
     * Check if guest
     */
    public static function guest(): bool {
        return !self::check();
    }
    
    /**
     * Get current user
     */
    public static function user(): ?array {
        return self::$currentUser;
    }
    
    /**
     * Get user ID
     */
    public static function id(): ?int {
        return self::$currentUser['id'] ?? null;
    }
    
    /**
     * Get user role
     */
    public static function role(): ?array {
        return self::$currentRole;
    }
    
    /**
     * Get role name
     */
    public static function roleName(): string {
        return self::$currentRole['name'] ?? 'guest';
    }
    
    /**
     * Check if user has role
     */
    public static function hasRole(string|array $roles): bool {
        if (!self::check()) return false;
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array(self::roleName(), $roles);
    }
    
    /**
     * Check if super admin
     */
    public static function isSuperAdmin(): bool {
        return self::hasRole('super_admin');
    }
    
    /**
     * Check if admin or higher
     */
    public static function isAdmin(): bool {
        return self::hasRole(['super_admin', 'admin']);
    }
    
    /**
     * Check if editor or higher
     */
    public static function isEditor(): bool {
        return self::hasRole(['super_admin', 'admin', 'editor']);
    }
    
    /**
     * Check permission
     */
    public static function can(string $permission): bool {
        if (self::isSuperAdmin()) return true;
        if (!self::check()) return false;
        return in_array($permission, self::$permissions);
    }
    
    /**
     * Check any permission
     */
    public static function canAny(array $permissions): bool {
        foreach ($permissions as $perm) {
            if (self::can($perm)) return true;
        }
        return false;
    }
    
    /**
     * Require permission (redirect if not)
     */
    public static function requirePermission(string $permission, string $redirect = ''): void {
        if (!self::can($permission)) {
            flash_set('error', 'You do not have permission to access this resource.');
            $redirectUrl = $redirect ?: (self::check() ? admin_url('dashboard') : admin_url('login'));
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Login user
     */
    public static function login(string $email, string $password, bool $remember = false): array {
        // Rate limiting check
        $ip = client_ip();
        $attempts = self::getLoginAttempts($ip);
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        // Find user
        $user = Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.display_name as role_display, r.permissions as role_perms 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ? OR u.username = ? LIMIT 1",
            [$email, $email]
        );
        
        if (!$user) {
            self::incrementLoginAttempts($ip);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }
        
        if ($user['status'] === 'banned') {
            return ['success' => false, 'message' => 'Your account has been banned.'];
        }
        
        if ($user['status'] === 'pending') {
            return ['success' => false, 'message' => 'Please verify your email first.'];
        }
        
        // Check password
        if (!verify_password($password, $user['password_hash'])) {
            self::incrementLoginAttempts($ip);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }
        
        // Login success
        self::clearLoginAttempts($ip);
        self::setUserSession($user);
        
        // Update last login
        Database::update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
            'login_attempts' => 0,
            'locked_until' => null
        ], 'id = ?', [$user['id']]);
        
        // Remember me
        if ($remember) {
            $token = random_string(32);
            $expires = date('Y-m-d H:i:s', time() + 30 * 86400);
            Database::update('users', [
                'remember_token' => hash('sha256', $token)
            ], 'id = ?', [$user['id']]);
            setcookie('remember', $token, [
                'expires' => time() + 30 * 86400,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        // Log activity
        self::logActivity('login', 'User logged in');
        
        return ['success' => true, 'user' => $user, 'redirect' => admin_url('dashboard')];
    }
    
    /**
     * Register new user
     */
    public static function register(array $data): array {
        // Validate
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
        
        if (!is_valid_email($data['email'])) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.'];
        }
        
        if ($data['password'] !== ($data['password_confirm'] ?? '')) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }
        
        // Check existing
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1", 
            [$data['email'], $data['username']]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email or username already exists.'];
        }
        
        // Create user (subscriber role by default)
        $userId = Database::insert('users', [
            'username' => sanitize($data['username']),
            'email' => sanitize($data['email']),
            'password_hash' => hash_password($data['password']),
            'display_name' => sanitize($data['display_name'] ?? $data['username']),
            'first_name' => sanitize($data['first_name'] ?? ''),
            'last_name' => sanitize($data['last_name'] ?? ''),
            'role_id' => $data['role_id'] ?? 6, // subscriber
            'status' => 'active',
            'email_verified' => 1 // Auto-verify for now
        ]);
        
        self::logActivity('register', 'New user registered: ' . $data['username'], $userId);
        
        return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful! You can now log in.'];
    }
    
    /**
     * Logout user
     */
    public static function logout(): void {
        self::logActivity('logout', 'User logged out');
        
        if (self::$currentUser) {
            Database::update('users', ['remember_token' => null], 'id = ?', [self::$currentUser['id']]);
        }
        
        // Clear remember cookie
        setcookie('remember', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        // Clear session
        session_destroy();
        self::$currentUser = null;
        self::$currentRole = null;
        self::$permissions = [];
    }
    
    /**
     * Load user data
     */
    private static function loadUser(int $userId): void {
        $user = Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.display_name as role_display, r.permissions as role_perms 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ? LIMIT 1",
            [$userId]
        );
        
        if (!$user || $user['status'] !== 'active') {
            self::logout();
            return;
        }
        
        self::$currentUser = $user;
        self::$currentRole = [
            'id' => $user['role_id'],
            'name' => $user['role_name'],
            'display_name' => $user['role_display']
        ];
        
        // Load permissions
        self::loadPermissions();
    }
    
    /**
     * Load user permissions
     */
    private static function loadPermissions(): void {
        if (!self::$currentUser) return;
        
        $perms = Database::fetchAll(
            "SELECT p.name FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             WHERE rp.role_id = ?",
            [self::$currentUser['role_id']]
        );
        
        self::$permissions = array_column($perms, 'name');
    }
    
    /**
     * Set user session
     */
    private static function setUserSession(array $user): void {
        $_SESSION['user_id'] = $user['id'];
        self::loadUser($user['id']);
    }
    
    /**
     * Login with remember token
     */
    private static function loginWithToken(string $token): void {
        $hash = hash('sha256', $token);
        $user = Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.display_name as role_display 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.remember_token = ? AND u.status = 'active' LIMIT 1",
            [$hash]
        );
        
        if ($user) {
            self::setUserSession($user);
        }
    }
    
    /**
     * Get login attempts from IP
     */
    private static function getLoginAttempts(string $ip): int {
        return (int) ($_SESSION['login_attempts_' . md5($ip)] ?? 0);
    }
    
    /**
     * Increment login attempts
     */
    private static function incrementLoginAttempts(string $ip): void {
        $key = 'login_attempts_' . md5($ip);
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    }
    
    /**
     * Clear login attempts
     */
    private static function clearLoginAttempts(string $ip): void {
        unset($_SESSION['login_attempts_' . md5($ip)]);
    }
    
    /**
     * Log activity
     */
    public static function logActivity(string $type, string $message, ?int $userId = null): void {
        $uid = $userId ?? (self::$currentUser['id'] ?? null);
        Database::insert('activity_logs', [
            'user_id' => $uid,
            'type' => $type,
            'message' => $message,
            'ip_address' => client_ip(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Generate password reset token
     */
    public static function forgotPassword(string $email): array {
        $user = Database::fetchOne("SELECT id, email, display_name FROM users WHERE email = ? LIMIT 1", [$email]);
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found.'];
        }
        
        $token = random_string(32);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        Database::update('users', [
            'reset_token' => hash('sha256', $token),
            'reset_expires' => $expires
        ], 'id = ?', [$user['id']]);
        
        return ['success' => true, 'token' => $token, 'user' => $user];
    }
    
    /**
     * Reset password with token
     */
    public static function resetPassword(string $token, string $password): array {
        $hash = hash('sha256', $token);
        $user = Database::fetchOne(
            "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1",
            [$hash]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired token.'];
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.'];
        }
        
        Database::update('users', [
            'password_hash' => hash_password($password),
            'reset_token' => null,
            'reset_expires' => null
        ], 'id = ?', [$user['id']]);
        
        return ['success' => true, 'message' => 'Password reset successfully!'];
    }
    
    /**
     * Update user profile
     */
    public static function updateProfile(int $userId, array $data): array {
        $allowed = ['display_name', 'first_name', 'last_name', 'bio', 'avatar', 'social_links', 'seo_title', 'seo_description'];
        $update = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        
        if (empty($update)) {
            return ['success' => false, 'message' => 'No valid fields to update.'];
        }
        
        Database::update('users', $update, 'id = ?', [$userId]);
        
        // Reload user if updating self
        if ($userId === self::id()) {
            self::loadUser($userId);
        }
        
        return ['success' => true, 'message' => 'Profile updated successfully.'];
    }
    
    /**
     * Change password
     */
    public static function changePassword(int $userId, string $current, string $new): array {
        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (!$user || !verify_password($current, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        if (strlen($new) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.'];
        }
        
        Database::update('users', ['password_hash' => hash_password($new)], 'id = ?', [$userId]);
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
}

// Initialize auth
Auth::init();
