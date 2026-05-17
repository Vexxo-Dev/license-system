<?php


require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/db.php';

require_manage_permission();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../clients.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$industry = trim($_POST['industry'] ?? '');
$status = strtolower(trim($_POST['status'] ?? 'active'));
$contactName = trim($_POST['primary_contact_name'] ?? '');
$contactEmail = trim($_POST['primary_contact_email'] ?? '');

if ($name === '' || $industry === '') {
    header('Location: ../clients.php?error=missing');
    exit;
}

if (!in_array($status, ['active', 'over_limit', 'inactive'], true)) {
    $status = 'active';
}

$db = db_connection();
$stmt = $db->prepare(
    'INSERT INTO clients (name, industry, status, total_licenses, active_users, primary_contact_name, primary_contact_email, created_at, updated_at)
     VALUES (:name, :industry, :status, :total_licenses, :active_users, :primary_contact_name, :primary_contact_email, NOW(), NOW())'
);

$stmt->execute([
    'name' => $name,
    'industry' => $industry,
    'status' => $status,
    'total_licenses' => 0,
    'active_users' => 0,
    'primary_contact_name' => $contactName,
    'primary_contact_email' => $contactEmail
]);

header('Location: ../clients.php?added=1');
exit;
