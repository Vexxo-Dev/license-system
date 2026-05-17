<?php

declare(strict_types=1);

function require_login(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function auth_user(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return $_SESSION['user'] ?? [];
}

function auth_user_role(): string
{
    return strtolower((string) (auth_user()['role'] ?? 'viewer'));
}

function can_manage_records(): bool
{
    return in_array(auth_user_role(), ['admin', 'manager'], true);
}

function require_manage_permission(): void
{
    require_login();

    if (!can_manage_records()) {
        header('Location: ../dashboard.php?error=permission');
        exit;
    }
}

function require_manage_page_permission(): void
{
    require_login();

    if (!can_manage_records()) {
        header('Location: dashboard.php?error=permission');
        exit;
    }
}

function require_non_viewer_page(): void
{
    require_login();

    if (auth_user_role() === 'viewer') {
        header('Location: licence.php');
        exit;
    }
}

function viewer_client_ids(PDO $db): array
{
    if (auth_user_role() !== 'viewer') {
        return [];
    }

    $userId = (int) (auth_user()['id'] ?? 0);
    if ($userId > 0) {
        $stmt = $db->prepare('SELECT client_id FROM users WHERE id = :id AND client_id IS NOT NULL LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $clientId = (int) ($stmt->fetch()['client_id'] ?? 0);
        if ($clientId > 0) {
            return [$clientId];
        }
    }

    return [];
}

function scoped_in_clause(array $ids, string $column): string
{
    if (auth_user_role() !== 'viewer') {
        return '';
    }

    if (!$ids) {
        return ' AND 1 = 0';
    }

    return ' AND ' . $column . ' IN (' . implode(',', array_map('intval', $ids)) . ')';
}
