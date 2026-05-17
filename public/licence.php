<?php

 
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/components.php';
require __DIR__ . '/../includes/db.php';

require_login();

$db = db_connection();
$viewerClientIds = viewer_client_ids($db);
$licenseScope = scoped_in_clause($viewerClientIds, 'licenses.client_id');
$clientScope = scoped_in_clause($viewerClientIds, 'id');
$licenses = $db->query(
    'SELECT licenses.id, licenses.license_key, licenses.status, licenses.type, licenses.expires_at,
            clients.name AS client_name, licenses.client_id
     FROM licenses
     LEFT JOIN clients ON clients.id = licenses.client_id
     WHERE 1=1' . $licenseScope . '
     ORDER BY licenses.id DESC'
)->fetchAll();

$clients = $db->query('SELECT id, name FROM clients WHERE 1=1' . $clientScope . ' ORDER BY name ASC')->fetchAll();

function format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $time = strtotime($value);
    if ($time === false) {
        return $value;
    }

    return date('M d, Y', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licenses - LicensePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fafbfc;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            background-color: #f3f4f6;
            border-right: 1px solid #e5e7eb;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 16px 24px;
            display: flex;
            align-items: flex-start;
            height: 60px;
        }

        .sidebar-logo-icon {
            background-color: #1d4ed8;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .sidebar-title-group {
            display: flex;
            flex-direction: column;
            margin-top: -2px;
        }

        .sidebar-title {
            color: #1d4ed8;
            font-weight: 700;
            font-size: 15px;
            line-height: 1.2;
        }

        .sidebar-subtitle {
            color: #6b7280;
            font-size: 11px;
            margin-top: 4px;
        }

        .sidebar-nav {
            padding: 16px 0;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            padding: 8px 24px;
            color: #4b5563;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin: 4px 12px;
            border-radius: 6px;
        }

        .nav-item-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
            color: #6b7280;
        }

        .nav-item-link:hover {
            background-color: #e5e7eb;
            color: #111827;
        }

        .nav-item-link.active {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .nav-item-link.active i {
            color: #1d4ed8;
        }

        .top-navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 60px;
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 999;
            justify-content: space-between;
        }

        .navbar-brand-text {
            color: #1d4ed8;
            font-weight: 700;
            font-size: 18px;
            margin-right: 32px;
        }

        .search-container {
            position: relative;
            width: 350px;
        }

        .search-container i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }

        .search-input {
            width: 100%;
            padding: 6px 12px 6px 36px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            color: #374151;
        }

        .search-input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .top-nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .top-nav-icons i {
            font-size: 18px;
            color: #4b5563;
            cursor: pointer;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }

        .main-content {
            margin-left: 250px;
            padding: 90px 40px 40px 40px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px 0;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        .btn-issue {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            font-weight: 500;
            font-size: 13px;
            padding: 8px 16px;
            border-radius: 4px;
        }

        .btn-issue:hover {
            background-color: #1e40af;
            border-color: #1e40af;
        }

        .card-table {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .card-header-toolbar {
            padding: 12px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fafafa;
        }

        .filter-select {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            color: #374151;
            background: white;
            cursor: pointer;
            min-width: 160px;
            justify-content: space-between;
        }

        .filter-select i.bi-funnel {
            color: #9ca3af;
            margin-right: 4px;
        }

        .pagination-info {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: #6b7280;
        }

        .pagination-controls {
            display: flex;
            gap: 4px;
            margin-left: 16px;
        }

        .btn-page {
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-page:hover {
            background: #f3f4f6;
        }

        .table-custom {
            margin-bottom: 0;
            width: 100%;
        }

        .table-custom th {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
            background-color: #ffffff;
            font-family: 'Inter', sans-serif;
        }

        .table-custom td {
            font-size: 13px;
            color: #374151;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .table-custom tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .status-active {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-expired {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .text-expired {
            color: #dc2626;
        }

        .font-monospace-custom {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            color: #4b5563;
        }

        .license-actions {
            display: inline-flex;
        }

        .license-actions form {
            margin: 0;
        }

        .license-actions .dropdown-menu {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            min-width: 190px;
            padding: 6px;
        }

        .license-actions .dropdown-item {
            align-items: center;
            border-radius: 6px;
            display: flex;
            font-size: 13px;
            gap: 8px;
            padding: 8px 10px;
        }

        .license-actions .dropdown-item i {
            color: #6b7280;
            font-size: 15px;
            width: 18px;
        }

        .license-actions .dropdown-item.text-danger i {
            color: #dc2626;
        }

        .btn-icon {
            background: transparent;
            border: none;
            border-radius: 4px;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
        }

        .btn-icon:hover {
            background-color: #f3f4f6;
            color: #111827;
        }
    </style>
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>

    <?php render_sidebar('licenses'); ?>
    <?php render_top_nav('Search licenses...'); ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Licenses</h1>
                <p class="page-subtitle">Manage client license keys, statuses, and renewals.</p>
            </div>
            <?php if (can_manage_records()): ?>
                <div>
                    <button class="btn btn-primary btn-issue" data-bs-toggle="modal" data-bs-target="#addLicenseModal">
                        <i class="bi bi-plus" style="margin-right: 2px;"></i> Issue New License
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-table">
            <div class="card-header-toolbar">
                <div class="dropdown">
                    <button class="filter-select" id="licenseStatusFilterButton" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-funnel"></i>
                            <span>All Statuses</span>
                        </span>
                        <i class="bi bi-chevron-down" style="font-size: 10px;"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-status="all">All Statuses</a></li>
                        <li><a class="dropdown-item" href="#" data-status="active">Active</a></li>
                        <li><a class="dropdown-item" href="#" data-status="expired">Expired</a></li>
                        <li><a class="dropdown-item" href="#" data-status="revoked">Revoked</a></li>
                    </ul>
                </div>
                <div class="pagination-info">
                    <span id="licensesResultCount" style="margin-right: 16px;">Showing 1-<?php echo count($licenses); ?> of <?php echo count($licenses); ?> licenses</span>
                    <div class="pagination-controls">
                        <button class="btn-page"><i class="bi bi-chevron-left" style="font-size: 10px;"></i></button>
                        <button class="btn-page"><i class="bi bi-chevron-right" style="font-size: 10px;"></i></button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Client Name</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Expiration Date</th>
                            <?php if (can_manage_records()): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="licensesTableBody">
                        <?php foreach ($licenses as $license): ?>
                            <?php
                                $statusClass = $license['status'] === 'expired' ? 'status-expired' : 'status-active';
                                $statusLabel = ucfirst($license['status']);
                                $typeLabel = ucfirst(strtolower($license['type']));
                            ?>
                            <tr
                                data-license-id="<?php echo (int) $license['id']; ?>"
                                data-license-key="<?php echo h($license['license_key']); ?>"
                                data-client-id="<?php echo (int) $license['client_id']; ?>"
                                data-client-name="<?php echo h($license['client_name'] ?? 'Unknown'); ?>"
                                data-status="<?php echo h($license['status']); ?>"
                                data-type="<?php echo h(strtolower($license['type'])); ?>"
                                data-expires-at="<?php echo h($license['expires_at'] ?? ''); ?>"
                            >
                                <td class="font-monospace-custom"><?php echo h($license['license_key']); ?></td>
                                <td class="fw-medium"><?php echo h($license['client_name'] ?? 'Unknown'); ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo h($typeLabel); ?></td>
                                <td class="<?php echo $license['status'] === 'expired' ? 'text-expired' : ''; ?>"><?php echo h(format_date($license['expires_at'])); ?></td>
                                <?php if (can_manage_records()): ?>
                                    <td class="text-end">
                                        <div class="dropdown license-actions">
                                            <button class="btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="License actions">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button class="dropdown-item edit-license-action" type="button" data-bs-toggle="modal" data-bs-target="#editLicenseModal">
                                                        <i class="bi bi-pencil-square"></i>
                                                        Edit license
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="post" action="actions/licenses_manage.php">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="license_id" value="<?php echo (int) $license['id']; ?>">
                                                        <button class="dropdown-item" type="submit">
                                                            <i class="bi <?php echo $license['status'] === 'active' ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                                            <?php echo $license['status'] === 'active' ? 'Mark expired' : 'Mark active'; ?>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="actions/licenses_manage.php" class="revoke-license-form">
                                                        <input type="hidden" name="action" value="revoke">
                                                        <input type="hidden" name="license_id" value="<?php echo (int) $license['id']; ?>">
                                                        <button class="dropdown-item" type="submit" <?php echo $license['status'] === 'revoked' ? 'disabled' : ''; ?>>
                                                            <i class="bi bi-ban"></i>
                                                            Revoke license
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="post" action="actions/licenses_manage.php" class="delete-license-form">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="license_id" value="<?php echo (int) $license['id']; ?>">
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <i class="bi bi-trash"></i>
                                                            Delete license
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLicenseModal" tabindex="-1" aria-labelledby="editLicenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLicenseModalLabel">Edit License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editLicenseForm" method="post" action="actions/licenses_manage.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="license_id" id="editLicenseId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="client_id" id="editLicenseClientSelect" required>
                                <option value="">Select a client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo (int) $client['id']; ?>"><?php echo h($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">License Key</label>
                            <input type="text" class="form-control" name="license_key" id="editLicenseKey" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" id="editLicenseType">
                                <option value="ENTERPRISE">Enterprise</option>
                                <option value="PROFESSIONAL">Professional</option>
                                <option value="STANDARD">Standard</option>
                                <option value="BASIC">Basic</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editLicenseStatus">
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="revoked">Revoked</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" name="expires_at" id="editLicenseExpiresAt">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addLicenseModal" tabindex="-1" aria-labelledby="addLicenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLicenseModalLabel">Issue New License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addLicenseForm" method="post" action="actions/licenses_add.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="client_id" id="licenseClientSelect" required>
                                <option value="">Select a client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo (int) $client['id']; ?>"><?php echo htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="ENTERPRISE">Enterprise</option>
                                <option value="PROFESSIONAL">Professional</option>
                                <option value="STANDARD" selected>Standard</option>
                                <option value="BASIC">Basic</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Active</option>
                                <option value="expired">Expired</option>
                                <option value="revoked">Revoked</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" name="expires_at">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">License Key (optional)</label>
                            <input type="text" class="form-control" name="license_key" placeholder="Auto-generate if empty">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Issue License</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/licence.js"></script>
</body>
</html>
