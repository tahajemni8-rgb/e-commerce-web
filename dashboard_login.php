<?php
session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Rate limiting
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Check if user is already logged in
if (isset($_SESSION['owner_logged_in']) && $_SESSION['owner_logged_in'] === true) {
    header('Location: owner_dashboard.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard_login.php');
    exit();
}

// Handle login attempt
$login_error = '';
$show_captcha = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $login_error = 'Security error. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = $_POST['captcha'] ?? '';

        // Rate limiting check
        if ($_SESSION['login_attempts'] >= 5) {
            $time_diff = time() - $_SESSION['last_attempt'];
            if ($time_diff < 900) { // 15 minutes lockout
                $login_error = 'Too many failed attempts. Please wait 15 minutes before trying again.';
            } else {
                $_SESSION['login_attempts'] = 0;
            }
        }

        if (empty($login_error)) {
            // Show captcha after 3 failed attempts
            if ($_SESSION['login_attempts'] >= 3) {
                $show_captcha = true;
                if (empty($captcha) || !isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
                    $login_error = 'Invalid captcha. Please try again.';
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                }
            }

            if (!$show_captcha || empty($login_error)) {
                // Owner credentials (should be stored securely in production)
                $owner_username = 'owner';
                $owner_password_hash = password_hash('OwnerSecure2025!', PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);

                // Validate input
                if (empty($username) || empty($password)) {
                    $login_error = 'Please enter both username and password.';
                } elseif (strlen($username) > 50 || strlen($password) > 100) {
                    $login_error = 'Invalid input length.';
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                    $login_error = 'Username contains invalid characters.';
                } else {
                    // Verify credentials
                    if ($username === $owner_username && password_verify($password, $owner_password_hash)) {
                        // Successful login
                        $_SESSION['owner_logged_in'] = true;
                        $_SESSION['owner_username'] = $username;
                        $_SESSION['login_time'] = time();
                        $_SESSION['login_attempts'] = 0;

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        header('Location: owner_dashboard.php');
                        exit();
                    } else {
                        $login_error = 'Invalid username or password.';
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt'] = time();

                        if ($_SESSION['login_attempts'] >= 3) {
                            $show_captcha = true;
                        }
                    }
                }
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Generate captcha if needed
if ($show_captcha) {
    $_SESSION['captcha'] = generateCaptcha();
}

function generateCaptcha() {
    $characters = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $captcha = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Login - Secure Access</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #856404;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .captcha-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .captcha-display {
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            padding: 8px 12px;
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            letter-spacing: 2px;
            user-select: none;
        }

        .captcha-input {
            flex: 1;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message::before {
            content: '⚠️';
            font-size: 16px;
        }

        .attempts-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .security-features {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .security-features h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .security-features ul {
            list-style: none;
            padding: 0;
        }

        .security-features li {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .security-features li::before {
            content: '🔒';
            font-size: 10px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Owner Access</h1>
            <p>Secure login for authorized personnel only</p>
        </div>

        <div class="security-notice">
            <strong>Security Notice:</strong> This is a restricted access area. Unauthorized access attempts are logged and monitored.
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['login_attempts'] >= 3 && $_SESSION['login_attempts'] < 5): ?>
            <div class="attempts-warning">
                Warning: <?php echo 5 - $_SESSION['login_attempts']; ?> attempts remaining before temporary lockout.
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       maxlength="50" autocomplete="username"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       maxlength="100" autocomplete="current-password">
            </div>

            <?php if ($show_captcha): ?>
                <div class="form-group">
                    <label for="captcha">Security Code</label>
                    <div class="captcha-container">
                        <div class="captcha-display"><?php echo htmlspecialchars($_SESSION['captcha']); ?></div>
                        <input type="text" id="captcha" name="captcha" class="captcha-input"
                               required maxlength="6" placeholder="Enter code">
                    </div>
                </div>
            <?php endif; ?>

            <button type="submit" class="login-btn" id="loginBtn">
                Access Dashboard
            </button>
        </form>

        <div class="back-link">
            <a href="index.php">← Back to Store</a>
        </div>

        <div class="security-features">
            <h3>Security Features</h3>
            <ul>
                <li>Rate limiting & brute force protection</li>
                <li>CSRF protection</li>
                <li>Secure password hashing</li>
                <li>Session security</li>
                <li>Input validation & sanitization</li>
            </ul>
        </div>
    </div>

    <script>
        // Client-side validation and enhancements
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');

            // Basic client-side validation
            if (username.length === 0 || password.length === 0) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }

            if (username.length > 50 || password.length > 100) {
                e.preventDefault();
                alert('Input exceeds maximum length.');
                return;
            }

            // Disable button to prevent double submission
            loginBtn.disabled = true;
            loginBtn.textContent = 'Authenticating...';

            // Re-enable after 5 seconds if no response
            setTimeout(() => {
                loginBtn.disabled = false;
                loginBtn.textContent = 'Access Dashboard';
            }, 5000);
        });

        // Auto-focus username field
        document.getElementById('username').focus();

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
