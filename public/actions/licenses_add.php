<?php


require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/db.php';

require_manage_permission();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../licence.php');
    exit;
}

$clientId = (int) ($_POST['client_id'] ?? 0);
$type = strtoupper(trim($_POST['type'] ?? 'STANDARD'));
$status = strtolower(trim($_POST['status'] ?? 'active'));
$expiresAt = trim($_POST['expires_at'] ?? '');
$licenseKey = trim($_POST['license_key'] ?? '');

if ($clientId <= 0) {
    header('Location: ../licence.php?error=missing');
    exit;
}

if (!in_array($status, ['active', 'expired', 'revoked'], true)) {
    $status = 'active';
}

if (!in_array($type, ['ENTERPRISE', 'PROFESSIONAL', 'STANDARD', 'BASIC'], true)) {
    $type = 'STANDARD';
}

if ($licenseKey === '') {
    $licenseKey = generate_license_key($type);
}

$expiresAt = $expiresAt !== '' ? $expiresAt : null;

$db = db_connection();
$stmt = $db->prepare(
    'INSERT INTO licenses (license_key, client_id, status, type, expires_at, created_at, updated_at)
     VALUES (:license_key, :client_id, :status, :type, :expires_at, NOW(), NOW())'
);

$stmt->execute([
    'license_key' => $licenseKey,
    'client_id' => $clientId,
    'status' => $status,
    'type' => $type,
    'expires_at' => $expiresAt
]);

header('Location: ../licence.php?added=1');
exit;

function generate_license_key(string $type): string
{
    $prefix = 'LP';
    $typeCodeMap = [
        'ENTERPRISE' => 'ENT',
        'PROFESSIONAL' => 'PRO',
        'STANDARD' => 'STD',
        'BASIC' => 'BSC'
    ];
    $typeCode = $typeCodeMap[$type] ?? 'STD';
    $segmentA = strtoupper(bin2hex(random_bytes(2)));
    $segmentB = strtoupper(bin2hex(random_bytes(2)));

    return sprintf('%s-%s-%s-%s', $prefix, $typeCode, $segmentA, $segmentB);
}
