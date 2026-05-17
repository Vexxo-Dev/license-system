<?php

 
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? $_POST['workEmail'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $db = db_connection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $countStmt = $db->query('SELECT COUNT(*) AS total FROM users');
            $countRow = $countStmt->fetch();
            if ((int) ($countRow['total'] ?? 0) === 0) {
                $insert = $db->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role, status, last_login_at, created_at, updated_at)
                     VALUES (:full_name, :email, :password_hash, :role, :status, NULL, NOW(), NOW())'
                );
                $insert->execute([
                    'full_name' => 'Primary Admin',
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'role' => 'admin',
                    'status' => 'active'
                ]);

                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
            }
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'User account is not active.';
        } else {
            $update = $db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
            $update->execute(['id' => $user['id']]);

            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'client_id' => isset($user['client_id']) ? (int) $user['client_id'] : null,
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            header('Location: ' . (strtolower((string) $user['role']) === 'viewer' ? 'licence.php' : 'dashboard.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - LicensePro Enterprise Console</title>
    <meta name="description" content="Sign in to LicensePro Enterprise Console to manage your licenses, users, and clients.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

    <div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
        <div class="login-card">

            <div class="login-brand text-center mb-4">
                <h1 class="login-logo">LicensePro</h1>
                <p class="login-tagline">Enterprise Console Access</p>
            </div>

            <div id="loginAlert" class="alert alert-danger <?php echo $error ? '' : 'd-none'; ?>" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <span id="loginAlertMsg"><?php echo htmlspecialchars($error ?: 'Invalid email or password. Please try again.', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <form id="loginForm" method="post" action="login.php" novalidate>

                <div class="mb-3">
                    <label for="workEmail" class="form-label login-label">Work Email</label>
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            id="workEmail"
                            name="email"
                            class="form-control login-input"
                            placeholder="admin@organization.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                    <div class="invalid-feedback-custom d-none" id="emailError">
                        Please enter a valid work email address.
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label login-label mb-0">Password</label>
                        <a href="#" class="forgot-link" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                    <div class="input-group-custom mt-1">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control login-input"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback-custom d-none" id="passwordError">
                        Password is required.
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check remember-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                        <label class="form-check-label remember-label" for="rememberMe">
                            Remember me on this device
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-signin w-100" id="signInBtn">
                    <span class="btn-text">Sign In</span>
                    <i class="bi bi-arrow-right ms-2"></i>
                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status" id="signInSpinner"></span>
                </button>

            </form>

            <hr class="login-divider">

            <p class="text-center support-text mb-0">
                <i class="bi bi-question-circle me-1"></i>
                Need access? <a href="mailto:it-support@organization.com" class="support-link" id="contactITSupport">Contact IT Support</a>
            </p>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
