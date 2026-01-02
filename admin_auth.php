<?php
// Security functions for authentication

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers function
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Check if user is logged in as owner
function isOwnerLoggedIn() {
    return isset($_SESSION['owner_logged_in']) && $_SESSION['owner_logged_in'] === true;
}

// Require admin access - redirect to admin login if not authenticated
function requireAdminAccess() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }

    // Check session timeout (30 minutes for admin)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 1800) {
        session_destroy();
        header('Location: admin_login.php?expired=1');
        exit();
    }

    // Update last activity
    $_SESSION['login_time'] = time();
}

// Require owner access - redirect to owner login if not authenticated
function requireOwnerAccess() {
    if (!isOwnerLoggedIn()) {
        header('Location: dashboard_login.php');
        exit();
    }

    // Check session timeout (2 hours for owner)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
        session_destroy();
        header('Location: dashboard_login.php?expired=1');
        exit();
    }

    // Update last activity
    $_SESSION['login_time'] = time();
}

// Generate secure password hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Log security events
function logSecurityEvent($event, $details = '') {
    $logFile = __DIR__ . '/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $logEntry = sprintf(
        "[%s] %s - IP: %s - User-Agent: %s - %s\n",
        $timestamp,
        $event,
        $ip,
        substr($userAgent, 0, 100),
        $details
    );

    // In production, you might want to use a proper logging system
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Rate limiting check
function checkRateLimit($maxAttempts = 5, $timeWindow = 900) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }

    $now = time();
    $timeDiff = $now - $_SESSION[$key]['first_attempt'];

    // Reset if time window has passed
    if ($timeDiff > $timeWindow) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => $now];
        return true;
    }

    // Check if under limit
    if ($_SESSION[$key]['attempts'] < $maxAttempts) {
        $_SESSION[$key]['attempts']++;
        return true;
    }

    return false;
}

// Get rate limit status
function getRateLimitStatus() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        return ['attempts' => 0, 'remaining' => 5, 'reset_time' => 0];
    }

    $now = time();
    $timeDiff = $now - $_SESSION[$key]['first_attempt'];
    $remaining = max(0, 5 - $_SESSION[$key]['attempts']);
    $resetTime = 900 - $timeDiff;

    return [
        'attempts' => $_SESSION[$key]['attempts'],
        'remaining' => $remaining,
        'reset_time' => max(0, $resetTime)
    ];
}

// Secure logout
function secureLogout() {
    // Log the logout event
    logSecurityEvent('LOGOUT', 'User logged out');

    // Clear all session data
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
}

// Initialize security on every authenticated page
function initSecurity() {
    setSecurityHeaders();

    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Call initSecurity on authenticated pages
initSecurity();
?>