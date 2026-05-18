<?php

 
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/api.php';

api_require_auth();

$db = db_connection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $clients = $db->query(
        "SELECT clients.id, clients.name, clients.industry, clients.status,
                COUNT(DISTINCT licenses.id) AS total_licenses,
                COUNT(DISTINCT CASE WHEN users.status = 'active' THEN users.id END) AS active_users,
                clients.primary_contact_name, clients.primary_contact_email
         FROM clients
         LEFT JOIN licenses ON licenses.client_id = clients.id
         LEFT JOIN users ON users.client_id = clients.id
         GROUP BY clients.id, clients.name, clients.industry, clients.status,
                  clients.primary_contact_name, clients.primary_contact_email
         ORDER BY clients.id DESC"
    )->fetchAll();
    api_send(['ok' => true, 'data' => $clients]);
}

if ($method !== 'POST') {
    api_send(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$data = api_input();
$name = trim($data['name'] ?? '');
$industry = trim($data['industry'] ?? '');
$status = strtolower(trim($data['status'] ?? 'active'));
$contactName = trim($data['primary_contact_name'] ?? '');
$contactEmail = trim($data['primary_contact_email'] ?? '');

if ($name === '' || $industry === '') {
    api_send(['ok' => false, 'error' => 'Name and industry are required.'], 400);
}

if (!in_array($status, ['active', 'over_limit', 'inactive'], true)) {
    $status = 'active';
}

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

api_send([
    'ok' => true,
    'data' => [
        'id' => (int) $db->lastInsertId(),
        'name' => $name,
        'industry' => $industry,
        'status' => $status,
        'total_licenses' => 0,
        'active_users' => 0,
        'primary_contact_name' => $contactName,
        'primary_contact_email' => $contactEmail
    ]
], 201);
