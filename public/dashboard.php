<?php

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/components.php';
require __DIR__ . '/../includes/db.php';

require_non_viewer_page();

$db = db_connection();
$viewerClientIds = viewer_client_ids($db);
$clientScope = scoped_in_clause($viewerClientIds, 'id');
$licenseScope = scoped_in_clause($viewerClientIds, 'client_id');

$totalLicenses = (int) ($db->query('SELECT COUNT(*) AS total FROM licenses WHERE 1=1' . $licenseScope)->fetch()['total'] ?? 0);
$activeLicenses = (int) ($db->query("SELECT COUNT(*) AS total FROM licenses WHERE status = 'active'" . $licenseScope)->fetch()['total'] ?? 0);
$activeUsers = (int) ($db->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'")->fetch()['total'] ?? 0);
$totalUsers = (int) ($db->query('SELECT COUNT(*) AS total FROM users')->fetch()['total'] ?? 0);
$totalClients = (int) ($db->query('SELECT COUNT(*) AS total FROM clients WHERE 1=1' . $clientScope)->fetch()['total'] ?? 0);
$overLimitClients = (int) ($db->query(
    "SELECT COUNT(*) AS total
     FROM (
        SELECT clients.id,
               clients.status,
               COUNT(DISTINCT licenses.id) AS total_licenses,
               COUNT(DISTINCT CASE WHEN users.status = 'active' THEN users.id END) AS active_users
        FROM clients
        LEFT JOIN licenses ON licenses.client_id = clients.id
        LEFT JOIN users ON users.client_id = clients.id
        WHERE 1=1" . scoped_in_clause($viewerClientIds, 'clients.id') . "
        GROUP BY clients.id, clients.status
     ) AS client_usage
     WHERE active_users > total_licenses OR status = 'over_limit'"
)->fetch()['total'] ?? 0);
$expiringSoon = (int) ($db->query("SELECT COUNT(*) AS total FROM licenses WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)" . $licenseScope)->fetch()['total'] ?? 0);
$expiredLicenses = (int) ($db->query("SELECT COUNT(*) AS total FROM licenses WHERE (status = 'expired' OR (expires_at IS NOT NULL AND expires_at < CURDATE()))" . $licenseScope)->fetch()['total'] ?? 0);
$totalProvisionedSeats = (int) ($db->query('SELECT COALESCE(SUM(total_licenses), 0) AS total FROM clients WHERE 1=1' . $clientScope)->fetch()['total'] ?? 0);

$activeLicenseRate = $totalLicenses > 0 ? (int) round(($activeLicenses / $totalLicenses) * 100) : 0;
$activeUserRate = $totalUsers > 0 ? (int) round(($activeUsers / $totalUsers) * 100) : 0;

$licenseDistribution = $db->query(
    'SELECT type, COUNT(*) AS total
     FROM licenses
     WHERE 1=1' . $licenseScope . '
     GROUP BY type
     ORDER BY total DESC, type ASC'
)->fetchAll();

$maxDistributionCount = 0;
foreach ($licenseDistribution as $row) {
    $maxDistributionCount = max($maxDistributionCount, (int) $row['total']);
}

$activityParts = [
    "SELECT 'license' AS activity_type,
               CONCAT('License ', license_key, ' issued') AS title,
               COALESCE(clients.name, 'Unknown client') AS detail,
               licenses.created_at AS created_at
        FROM licenses
        LEFT JOIN clients ON clients.id = licenses.client_id
        WHERE 1=1" . scoped_in_clause($viewerClientIds, 'licenses.client_id'),
    "SELECT 'client' AS activity_type,
               CONCAT('Client ', name, ' registered') AS title,
               industry AS detail,
               created_at
        FROM clients
        WHERE 1=1" . $clientScope,
];

if (can_manage_records()) {
    $activityParts[] = "SELECT 'user' AS activity_type,
               CONCAT('User ', full_name, ' added') AS title,
               email AS detail,
               created_at
        FROM users";
}

$recentActivity = $db->query(
    "SELECT activity_type, title, detail, created_at
     FROM (" . implode(' UNION ALL ', $activityParts) . ") AS activity
     ORDER BY created_at DESC
     LIMIT 6"
)->fetchAll();

function dashboard_time_ago(?string $timestamp): string
{
    if (!$timestamp) {
        return 'Unknown time';
    }

    $time = strtotime($timestamp);
    if ($time === false) {
        return $timestamp;
    }

    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' mins ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    }

    return date('M d, Y', $time);
}

function dashboard_activity_color(string $type): string
{
    if ($type === 'client') {
        return 'gray';
    }
    if ($type === 'user') {
        return 'green';
    }

    return 'blue';
}

function dashboard_license_type_label(string $type): string
{
    return ucwords(strtolower(str_replace('_', ' ', $type)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LicensePro Admin</title>
    <meta name="description" content="LicensePro Admin Dashboard – real-time metrics and license consumption data for your enterprise.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>

    <?php render_sidebar('dashboard'); ?>

    <div class="main-content">

        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard Overview</h1>
                <p class="page-subtitle">Real-time metrics and license consumption data.</p>
            </div>
            <?php if (can_manage_records()): ?>
                <button class="btn-primary-custom" id="newLicenseBtn">
                    <i class="bi bi-plus"></i> New License
                </button>
            <?php endif; ?>
        </div>

        <div class="row g-3 mb-4" id="statsRow">

            <div class="col-xl-3 col-md-6">
                <div class="stat-card" id="cardTotalLicenses" role="link" tabindex="0" aria-label="Open licenses">
                    <div class="stat-card-top">
                        <span class="stat-card-label">Total Licenses</span>
                        <span class="stat-card-icon blue">
                            <i class="bi bi-key-fill"></i>
                        </span>
                    </div>
                    <div class="stat-card-value" id="valTotalLicenses"><?php echo number_format($totalLicenses); ?></div>
                    <div class="stat-card-trend <?php echo $activeLicenseRate >= 70 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="bi bi-activity"></i> <?php echo $activeLicenseRate; ?>% active
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card" id="cardActiveUsers" role="link" tabindex="0" aria-label="Open user management">
                    <div class="stat-card-top">
                        <span class="stat-card-label">Active Users</span>
                        <span class="stat-card-icon blue">
                            <i class="bi bi-people-fill"></i>
                        </span>
                    </div>
                    <div class="stat-card-value" id="valActiveUsers"><?php echo number_format($activeUsers); ?></div>
                    <div class="stat-card-trend <?php echo $activeUserRate >= 70 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="bi bi-activity"></i> <?php echo $activeUserRate; ?>% of users active
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card" id="cardTotalClients">
                    <div class="stat-card-top">
                        <span class="stat-card-label">Total Clients</span>
                        <span class="stat-card-icon gray">
                            <i class="bi bi-buildings-fill"></i>
                        </span>
                    </div>
                    <div class="stat-card-value" id="valTotalClients"><?php echo number_format($totalClients); ?></div>
                    <div class="stat-card-badge <?php echo $overLimitClients > 0 ? 'badge-action' : 'badge-stable'; ?>">
                        <?php echo $overLimitClients > 0 ? number_format($overLimitClients) . ' over limit' : 'No over-limit clients'; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card" id="cardExpiringSoon">
                    <div class="stat-card-top">
                        <span class="stat-card-label">Expiring Soon (30d)</span>
                        <span class="stat-card-icon red">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </span>
                    </div>
                    <div class="stat-card-value danger" id="valExpiringSoon"><?php echo number_format($expiringSoon); ?></div>
                    <div class="stat-card-badge <?php echo $expiringSoon > 0 ? 'badge-action' : 'badge-stable'; ?>">
                        <?php echo $expiredLicenses > 0 ? number_format($expiredLicenses) . ' expired' : 'No expired licenses'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">

            <div class="col-lg-7">
                <div class="panel" id="panelLicenseDistribution">
                    <div class="panel-header">
                        <span class="panel-title">License Type Distribution</span>
                        <span class="panel-meta"><?php echo number_format($totalLicenses); ?> total licenses</span>
                    </div>

                    <div class="distribution-list" id="distributionList">
                        <?php if ($licenseDistribution): ?>
                            <?php foreach ($licenseDistribution as $index => $row): ?>
                                <?php
                                    $count = (int) $row['total'];
                                    $width = $maxDistributionCount > 0 ? max(4, (int) round(($count / $maxDistributionCount) * 100)) : 0;
                                    $share = $totalLicenses > 0 ? (int) round(($count / $totalLicenses) * 100) : 0;
                                    $color = $index === 0 ? 'blue' : 'gray';
                                ?>
                                <div class="dist-row">
                                    <span class="dist-label"><?php echo h(dashboard_license_type_label($row['type'])); ?></span>
                                    <div class="dist-bar-wrap" title="<?php echo $share; ?>% of all licenses">
                                        <div class="dist-bar" style="width: <?php echo $width; ?>%;" data-color="<?php echo h($color); ?>"></div>
                                    </div>
                                    <span class="dist-count"><?php echo number_format($count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">No license data available yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="panel" id="panelRecentActivity">
                    <div class="panel-header">
                        <span class="panel-title">Recent Activity</span>
                        <span class="panel-meta">Latest database records</span>
                    </div>

                    <ul class="activity-list" id="activityList">
                        <?php if ($recentActivity): ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <li class="activity-item">
                                    <span class="activity-dot <?php echo h(dashboard_activity_color($activity['activity_type'])); ?>"></span>
                                    <div class="activity-body">
                                        <p class="activity-text"><?php echo h($activity['title']); ?></p>
                                        <span class="activity-meta"><?php echo h(dashboard_time_ago($activity['created_at'])); ?> · <?php echo h($activity['detail']); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="empty-state">No recent activity available yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
