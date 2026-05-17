<?php

require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_send(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$data = api_input();
$email = trim($data['email'] ?? '');
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    api_send(['ok' => false, 'error' => 'Email and password are required.'], 400);
}

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
    api_send(['ok' => false, 'error' => 'Invalid email or password.'], 401);
}

if ($user['status'] !== 'active') {
    api_send(['ok' => false, 'error' => 'User account is not active.'], 403);
}

$update = $db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
$update->execute(['id' => $user['id']]);

api_send([
    'ok' => true,
    'data' => [
            'user' => [
                'id' => (int) $user['id'],
                'client_id' => isset($user['client_id']) ? (int) $user['client_id'] : null,
                'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status']
        ]
    ]
]);
