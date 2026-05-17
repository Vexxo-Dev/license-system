<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/db.php';

require_manage_permission();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../licence.php');
    exit;
}

$db = db_connection();
$action = strtolower(trim($_POST['action'] ?? ''));
$licenseId = (int) ($_POST['license_id'] ?? 0);

if ($licenseId <= 0) {
    header('Location: ../licence.php?error=invalid_license');
    exit;
}

if ($action === 'update') {
    $licenseKey = trim($_POST['license_key'] ?? '');
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $type = strtoupper(trim($_POST['type'] ?? 'STANDARD'));
    $status = strtolower(trim($_POST['status'] ?? 'active'));
    $expiresAt = trim($_POST['expires_at'] ?? '');

    if ($licenseKey === '' || $clientId <= 0) {
        header('Location: ../licence.php?error=missing');
        exit;
    }

    if (!in_array($type, ['ENTERPRISE', 'PROFESSIONAL', 'STANDARD', 'BASIC'], true)) {
        $type = 'STANDARD';
    }

    if (!in_array($status, ['active', 'expired', 'revoked'], true)) {
        $status = 'active';
    }

    $check = $db->prepare('SELECT id FROM licenses WHERE license_key = :license_key AND id <> :id LIMIT 1');
    $check->execute(['license_key' => $licenseKey, 'id' => $licenseId]);
    if ($check->fetch()) {
        header('Location: ../licence.php?error=license_exists');
        exit;
    }

    $stmt = $db->prepare(
        'UPDATE licenses
         SET license_key = :license_key, client_id = :client_id, type = :type, status = :status,
             expires_at = :expires_at, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        'license_key' => $licenseKey,
        'client_id' => $clientId,
        'type' => $type,
        'status' => $status,
        'expires_at' => $expiresAt !== '' ? $expiresAt : null,
        'id' => $licenseId
    ]);

    header('Location: ../licence.php?updated=1');
    exit;
}

if ($action === 'toggle_status') {
    $stmt = $db->prepare(
        "UPDATE licenses
         SET status = CASE WHEN status = 'active' THEN 'expired' ELSE 'active' END,
             updated_at = NOW()
         WHERE id = :id"
    );
    $stmt->execute(['id' => $licenseId]);

    header('Location: ../licence.php?status_updated=1');
    exit;
}

if ($action === 'revoke') {
    $stmt = $db->prepare("UPDATE licenses SET status = 'revoked', updated_at = NOW() WHERE id = :id");
    $stmt->execute(['id' => $licenseId]);

    header('Location: ../licence.php?revoked=1');
    exit;
}

if ($action === 'delete') {
    $stmt = $db->prepare('DELETE FROM licenses WHERE id = :id');
    $stmt->execute(['id' => $licenseId]);

    header('Location: ../licence.php?deleted=1');
    exit;
}

header('Location: ../licence.php?error=invalid_action');
exit;
