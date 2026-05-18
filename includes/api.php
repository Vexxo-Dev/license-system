<?php

/*

api_send(): Sends a JSON response to the client.

api_input(): Gets the JSON input from the client.

api_require_auth(): Checks if user is authenticated via session. Returns error if not.

*/

function api_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function api_input(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function api_require_auth(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user'])) {
        api_send(['ok' => false, 'error' => 'Unauthorized. Please log in first.'], 401);
    }
}
