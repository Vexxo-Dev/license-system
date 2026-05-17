<?php


require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/db.php';

require_manage_permission();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php');
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$clientId = (int) ($_POST['client_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$role = strtolower(trim($_POST['role'] ?? 'viewer'));
$status = strtolower(trim($_POST['status'] ?? 'active'));
$password = (string) ($_POST['password'] ?? 'Temp1234!');

if ($fullName === '' || $email === '') {
    header('Location: ../users.php?error=missing');
    exit;
}

if (!in_array($role, ['admin', 'manager', 'viewer'], true)) {
    $role = 'viewer';
}

if (!in_array($status, ['active', 'inactive', 'pending'], true)) {
    $status = 'active';
}

$db = db_connection();

$check = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    header('Location: ../users.php?error=exists');
    exit;
}

$stmt = $db->prepare(
    'INSERT INTO users (client_id, full_name, email, password_hash, role, status, last_login_at, created_at, updated_at)
     VALUES (:client_id, :full_name, :email, :password_hash, :role, :status, NULL, NOW(), NOW())'
);

$stmt->execute([
    'client_id' => $clientId > 0 ? $clientId : null,
    'full_name' => $fullName,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
    'role' => $role,
    'status' => $status
]);

header('Location: ../users.php?added=1');
exit;
