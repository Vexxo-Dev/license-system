<?php

require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/api.php';



$db = db_connection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $licenses = $db->query(
        'SELECT licenses.id, licenses.license_key, licenses.status, licenses.type, licenses.expires_at,
                clients.name AS client_name, licenses.client_id
         FROM licenses
         LEFT JOIN clients ON clients.id = licenses.client_id
         ORDER BY licenses.id DESC'
    )->fetchAll();
    api_send(['ok' => true, 'data' => $licenses]);
}

if ($method !== 'POST') {
    api_send(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$data = api_input();
$clientId = (int) ($data['client_id'] ?? 0);
$type = strtoupper(trim($data['type'] ?? 'STANDARD'));
$status = strtolower(trim($data['status'] ?? 'active'));
$expiresAt = trim($data['expires_at'] ?? '');
$licenseKey = trim($data['license_key'] ?? '');

if ($clientId <= 0) {
    api_send(['ok' => false, 'error' => 'Client is required.'], 400);
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

api_send([
    'ok' => true,
    'data' => [
        'id' => (int) $db->lastInsertId(),
        'license_key' => $licenseKey,
        'client_id' => $clientId,
        'status' => $status,
        'type' => $type,
        'expires_at' => $expiresAt
    ]
], 201);

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
