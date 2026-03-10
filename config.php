<?php
/**
 * Global Configuration
 * Handles DB connection, session init, constants
 */

define('APP_NAME', 'MailFlow');
define('APP_URL', 'https://developerruhban.com/services/webmail');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf','doc','docx','txt','png','jpg','jpeg','gif','zip','xlsx','csv']);

// DirectAdmin hosting details — update these to your actual domain
define('DEFAULT_SENDER_NAME',  'MailFlow');
define('DEFAULT_SENDER_EMAIL', 'develope@developerruhban.com'); // Must exist in DA email accounts
define('SITE_DOMAIN',          'developerruhban.com');


// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'develope_main_website');
define('DB_PASS', 'v3rh66ewhRqvAT5shBb5');
define('DB_NAME', 'develope_main_website');

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns PDO database connection (singleton)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

/**
 * Generate or retrieve CSRF token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token
 */
function validateCsrf(): void {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('<div class="alert alert-error">Invalid request. CSRF token mismatch.</div>');
    }
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Require authenticated session
 */
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        redirect(APP_URL . '/auth/login.php');
    }
}

/**
 * Sanitize string input
 */
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message setter
 */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Flash message getter + clear
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
