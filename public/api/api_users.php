<?php

 
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/api.php';

api_require_auth();

$db = db_connection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $users = $db->query(
        'SELECT users.id, users.client_id, clients.name AS client_name, users.full_name, users.email,
                users.role, users.status, users.last_login_at
         FROM users
         LEFT JOIN clients ON clients.id = users.client_id
         ORDER BY users.id DESC'
    )->fetchAll();
    api_send(['ok' => true, 'data' => $users]);
}

if ($method !== 'POST') {
    api_send(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$data = api_input();
$fullName = trim($data['full_name'] ?? '');
$clientId = (int) ($data['client_id'] ?? 0);
$email = trim($data['email'] ?? '');
$role = strtolower(trim($data['role'] ?? 'viewer'));
$status = strtolower(trim($data['status'] ?? 'active'));
$password = (string) ($data['password'] ?? 'Temp1234!');

if ($fullName === '' || $email === '') {
    api_send(['ok' => false, 'error' => 'Full name and email are required.'], 400);
}

if (!in_array($role, ['admin', 'viewer'], true)) {
    $role = 'viewer';
}

if (!in_array($status, ['active', 'inactive', 'pending'], true)) {
    $status = 'active';
}

$check = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    api_send(['ok' => false, 'error' => 'Email already exists.'], 409);
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

api_send([
    'ok' => true,
    'data' => [
        'id' => (int) $db->lastInsertId(),
        'client_id' => $clientId > 0 ? $clientId : null,
        'full_name' => $fullName,
        'email' => $email,
        'role' => $role,
        'status' => $status
    ]
], 201);
