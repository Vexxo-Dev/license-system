<?php

 
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/components.php';
require __DIR__ . '/../includes/db.php';

/*

    client_initials(): Gets the initials of the client's name.

    status_badge_class(): Gets the class of the status badge.

    status_label(): Gets the label of the status.

    require_login(): Checks if the user is logged in.

    db_connection(): Gets the database connection.

*/

require_non_viewer_page();

$db = db_connection();
$viewerClientIds = viewer_client_ids($db);
$clientScope = scoped_in_clause($viewerClientIds, 'clients.id');
$clients = $db->query(
    "SELECT clients.id, clients.name, clients.industry, clients.status,
            clients.primary_contact_name, clients.primary_contact_email,
            COUNT(DISTINCT licenses.id) AS total_licenses,
            COUNT(DISTINCT CASE WHEN users.status = 'active' THEN users.id END) AS active_users
     FROM clients
     LEFT JOIN licenses ON licenses.client_id = clients.id
     LEFT JOIN users ON users.client_id = clients.id
     WHERE 1=1" . $clientScope . "
     GROUP BY clients.id, clients.name, clients.industry, clients.status,
              clients.primary_contact_name, clients.primary_contact_email
     ORDER BY clients.id DESC"
)->fetchAll();

function client_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper($part[0] ?? '');
    }
    return $initials !== '' ? $initials : 'CL';
}

function status_badge_class(string $status): string
{
    if ($status === 'over_limit') {
        return 'badge-danger';
    }
    if ($status === 'inactive') {
        return 'badge-inactive';
    }
    return 'badge-active';
}

function status_label(string $status): string
{
    if ($status === 'over_limit') {
        return 'Over Limit';
    }
    if ($status === 'inactive') {
        return 'Inactive';
    }
    return 'Active';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - LicensePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/clients.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>

    <?php render_sidebar('clients'); ?>
    <?php render_top_nav('Search clients...'); ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Client Directory</h1>
                <p class="page-subtitle">Manage organization profiles and active license counts.</p>
            </div>
            <?php if (can_manage_records()): ?>
                <div>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="bi bi-plus" style="margin-right: 2px;"></i> Register Client
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="toolbar">
            <div class="toolbar-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search clients by name or industry...">
            </div>
            <div class="toolbar-actions">
                <div class="dropdown">
                    <button class="btn-outline" id="clientStatusFilterButton" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span>All Statuses</span> <i class="bi bi-chevron-down ms-2" style="font-size: 10px;"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-status="all">All Statuses</a></li>
                        <li><a class="dropdown-item" href="#" data-status="active">Active</a></li>
                        <li><a class="dropdown-item" href="#" data-status="over_limit">Over Limit</a></li>
                        <li><a class="dropdown-item" href="#" data-status="inactive">Inactive</a></li>
                    </ul>
                </div>
                <button class="btn-outline" type="button" id="clearClientFilters">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>

        <div class="row" id="clientsGrid">
            <?php foreach ($clients as $client): ?>
                <?php
                    $totalLicenses = (int) ($client['total_licenses'] ?? 0);
                    $activeUsers = (int) ($client['active_users'] ?? 0);
                    $utilization = $totalLicenses > 0 ? min(100, (int) round(($activeUsers / $totalLicenses) * 100)) : 0;
                    $overLimit = $activeUsers > $totalLicenses;
                ?>
                <div
                    class="col-md-4 mb-4 client-grid-item"
                    data-status="<?php echo h($client['status']); ?>"
                    data-client-id="<?php echo (int) $client['id']; ?>"
                    data-name="<?php echo h($client['name']); ?>"
                    data-industry="<?php echo h($client['industry']); ?>"
                    data-status-label="<?php echo h(status_label($client['status'])); ?>"
                    data-total-licenses="<?php echo $totalLicenses; ?>"
                    data-active-users="<?php echo $activeUsers; ?>"
                    data-utilization="<?php echo $utilization; ?>"
                    data-contact-name="<?php echo h($client['primary_contact_name'] ?: 'Not set'); ?>"
                    data-contact-email="<?php echo h($client['primary_contact_email'] ?: 'Not set'); ?>"
                >
                    <div class="client-card">
                        <div class="client-header">
                            <div class="client-logo-wrapper">
                                <div class="client-logo bg-blue-light"><?php echo htmlspecialchars(client_initials($client['name']), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="client-info">
                                    <h3><?php echo htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($client['industry'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <span class="badge-status <?php echo status_badge_class($client['status']); ?>"><?php echo htmlspecialchars(status_label($client['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-label">Total Licenses</div>
                                <div class="stat-value"><?php echo number_format($totalLicenses); ?></div>
                            </div>
                            <div class="stat-box<?php echo $overLimit ? ' danger' : ''; ?>">
                                <div class="stat-label">Active Users</div>
                                <div class="stat-value"><?php echo number_format($activeUsers); ?></div>
                            </div>
                        </div>

                        <div class="utilization">
                            <div class="utilization-header<?php echo $overLimit ? ' danger' : ''; ?>">
                                <span>Utilization</span>
                                <span class="util-val"><?php echo $utilization; ?>%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill<?php echo $overLimit ? ' danger' : ''; ?>" style="width: <?php echo $utilization; ?>%;"></div>
                            </div>
                        </div>

                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="bi bi-person"></i>
                                <span><?php echo htmlspecialchars($client['primary_contact_name'] ?: 'Not set', ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-envelope"></i>
                                <span><?php echo htmlspecialchars($client['primary_contact_email'] ?: 'Not set', ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <div class="card-action">
                            <a href="#" class="view-client-details" data-bs-toggle="modal" data-bs-target="#clientDetailsModal">
                                View Client Details <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination-footer">
            <span id="clientsResultCount">Showing 1-<?php echo count($clients); ?> of <?php echo count($clients); ?> clients</span>
            <div class="pagination-controls">
                <button class="btn-page"><i class="bi bi-chevron-left" style="font-size: 10px;"></i></button>
                <button class="btn-page"><i class="bi bi-chevron-right" style="font-size: 10px;"></i></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content client-details-modal">
                <div class="modal-header">
                    <div class="client-details-heading">
                        <div class="client-details-logo" id="detailsClientInitials">CL</div>
                        <div>
                            <h5 class="modal-title" id="clientDetailsModalLabel">Client Details</h5>
                            <p class="client-details-subtitle" id="detailsIndustry">Industry</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="client-details-summary">
                        <div class="client-details-stat">
                            <span class="details-label">Status</span>
                            <span class="badge-status" id="detailsStatus">Active</span>
                        </div>
                        <div class="client-details-stat">
                            <span class="details-label">Total Licenses</span>
                            <strong id="detailsTotalLicenses">0</strong>
                        </div>
                        <div class="client-details-stat">
                            <span class="details-label">Active Users</span>
                            <strong id="detailsActiveUsers">0</strong>
                        </div>
                        <div class="client-details-stat">
                            <span class="details-label">Utilization</span>
                            <strong id="detailsUtilization">0%</strong>
                        </div>
                    </div>

                    <div class="details-section">
                        <div class="details-section-title">License Usage</div>
                        <div class="utilization">
                            <div class="utilization-header" id="detailsUtilizationHeader">
                                <span>Current utilization</span>
                                <span class="util-val" id="detailsUtilizationValue">0%</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" id="detailsUtilizationBar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="details-section">
                        <div class="details-section-title">Primary Contact</div>
                        <div class="client-details-contact">
                            <div class="contact-item">
                                <i class="bi bi-person"></i>
                                <span id="detailsContactName">Not set</span>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-envelope"></i>
                                <span id="detailsContactEmail">Not set</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (can_manage_records()): ?>
                        <button type="button" class="btn btn-primary" id="openEditClientModal">Edit Client</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClientModalLabel">Edit Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editClientForm" method="post" action="actions/clients_manage.php">
                    <input type="hidden" name="client_id" id="editClientId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="name" id="editClientName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Industry</label>
                                <input type="text" class="form-control" name="industry" id="editClientIndustry" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="editClientStatus">
                                    <option value="active">Active</option>
                                    <option value="over_limit">Over Limit</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primary Contact Name</label>
                                <input type="text" class="form-control" name="primary_contact_name" id="editClientContactName">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primary Contact Email</label>
                                <input type="email" class="form-control" name="primary_contact_email" id="editClientContactEmail">
                            </div>
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

    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Register Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addClientForm" method="post" action="actions/clients_add.php">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Industry</label>
                                <input type="text" class="form-control" name="industry" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="over_limit">Over Limit</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primary Contact Name</label>
                                <input type="text" class="form-control" name="primary_contact_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primary Contact Email</label>
                                <input type="email" class="form-control" name="primary_contact_email">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/clients.js"></script>
</body>
</html>
