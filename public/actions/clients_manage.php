<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/db.php';

require_manage_permission();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../clients.php');
    exit;
}

$clientId = (int) ($_POST['client_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$industry = trim($_POST['industry'] ?? '');
$status = strtolower(trim($_POST['status'] ?? 'active'));
$contactName = trim($_POST['primary_contact_name'] ?? '');
$contactEmail = trim($_POST['primary_contact_email'] ?? '');

if ($clientId <= 0 || $name === '' || $industry === '') {
    header('Location: ../clients.php?error=missing');
    exit;
}

if (!in_array($status, ['active', 'over_limit', 'inactive'], true)) {
    $status = 'active';
}

$db = db_connection();
$stmt = $db->prepare(
    'UPDATE clients
     SET name = :name, industry = :industry, status = :status,
         primary_contact_name = :primary_contact_name,
         primary_contact_email = :primary_contact_email, updated_at = NOW()
     WHERE id = :id'
);

$stmt->execute([
    'name' => $name,
    'industry' => $industry,
    'status' => $status,
    'primary_contact_name' => $contactName,
    'primary_contact_email' => $contactEmail,
    'id' => $clientId
]);

header('Location: ../clients.php?updated=1');
exit;
