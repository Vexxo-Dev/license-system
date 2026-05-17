<?php


require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/db.php';

require_manage_permission();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php');
    exit;
}

$db = db_connection();
$action = strtolower(trim($_POST['action'] ?? ''));
$userId = (int) ($_POST['user_id'] ?? 0);
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);

if ($userId <= 0) {
    header('Location: ../users.php?error=invalid_user');
    exit;
}

if ($action === 'update') {
    $fullName = trim($_POST['full_name'] ?? '');
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $role = strtolower(trim($_POST['role'] ?? 'viewer'));
    $status = strtolower(trim($_POST['status'] ?? 'active'));
    $password = (string) ($_POST['password'] ?? '');

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

    if ($userId === $currentUserId && $status !== 'active') {
        header('Location: ../users.php?error=self_status');
        exit;
    }

    $check = $db->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
    $check->execute(['email' => $email, 'id' => $userId]);
    if ($check->fetch()) {
        header('Location: ../users.php?error=exists');
        exit;
    }

    if ($password !== '') {
        $stmt = $db->prepare(
            'UPDATE users
             SET full_name = :full_name, email = :email, role = :role, status = :status,
                 client_id = :client_id, password_hash = :password_hash, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'client_id' => $clientId > 0 ? $clientId : null,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'id' => $userId
        ]);
    } else {
        $stmt = $db->prepare(
            'UPDATE users
             SET full_name = :full_name, email = :email, role = :role, status = :status,
                 client_id = :client_id, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'client_id' => $clientId > 0 ? $clientId : null,
            'id' => $userId
        ]);
    }

    if ($userId === $currentUserId) {
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['role'] = $role;
        $_SESSION['user']['client_id'] = $clientId > 0 ? $clientId : null;
    }

    header('Location: ../users.php?updated=1');
    exit;
}

if ($action === 'toggle_status') {
    if ($userId === $currentUserId) {
        header('Location: ../users.php?error=self_status');
        exit;
    }

    $stmt = $db->prepare(
        "UPDATE users
         SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END,
             updated_at = NOW()
         WHERE id = :id"
    );
    $stmt->execute(['id' => $userId]);

    header('Location: ../users.php?status_updated=1');
    exit;
}

if ($action === 'delete') {
    if ($userId === $currentUserId) {
        header('Location: ../users.php?error=self_delete');
        exit;
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);

    header('Location: ../users.php?deleted=1');
    exit;
}

header('Location: ../users.php?error=invalid_action');
exit;
