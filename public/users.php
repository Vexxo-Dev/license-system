<?php

 
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/components.php';
require __DIR__ . '/../includes/db.php';

require_manage_page_permission();

$db = db_connection();
$users = $db->query(
    'SELECT users.id, users.client_id, users.full_name, users.email, users.role, users.status, users.last_login_at,
            clients.name AS client_name
     FROM users
     LEFT JOIN clients ON clients.id = users.client_id
     ORDER BY users.id DESC'
)->fetchAll();
$clients = $db->query('SELECT id, name FROM clients ORDER BY name ASC')->fetchAll();
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper($part[0] ?? '');
    }
    return $initials !== '' ? $initials : 'US';
}

function role_badge(string $role): string
{
    if ($role === 'admin') {
        return 'badge-admin';
    }
    return 'badge-viewer';
}

function format_login(?string $timestamp): string
{
    if (!$timestamp) {
        return 'Never';
    }

    $time = strtotime($timestamp);
    if ($time === false) {
        return $timestamp;
    }

    return date('M d, Y h:i A', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - LicensePro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/users.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>

    <?php render_sidebar('users'); ?>
    <?php render_top_nav('Search users...'); ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">Manage system users, roles, and access status.</p>
            </div>
            <div>
                <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus" style="margin-right: 2px;"></i> Add User
                </button>
            </div>
        </div>

        <div class="users-card">
            <div class="table-toolbar">
                <div class="dropdown">
                    <button class="btn-outline dropdown-toggle" id="userRoleFilterButton" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-filter-left" style="font-size: 16px;"></i> <span>All Roles</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-role="all">All Roles</a></li>
                        <li><a class="dropdown-item" href="#" data-role="admin">Admin</a></li>
                        <li><a class="dropdown-item" href="#" data-role="viewer">Viewer</a></li>
                    </ul>
                </div>
                <div class="pagination-info" id="usersResultCount">
                    Showing 1-<?php echo count($users); ?> of <?php echo count($users); ?> users
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table mb-0">
                    <thead>
                        <tr>
                            <th>USER</th>
                            <th>CLIENT</th>
                            <th>ROLE</th>
                            <th>LAST LOGIN</th>
                            <th>STATUS</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach ($users as $user): ?>
                            <?php $isCurrentUser = (int) $user['id'] === $currentUserId; ?>
                            <tr
                                data-role="<?php echo h($user['role']); ?>"
                                data-status="<?php echo h($user['status']); ?>"
                                data-user-id="<?php echo (int) $user['id']; ?>"
                                data-client-id="<?php echo (int) ($user['client_id'] ?? 0); ?>"
                                data-client-name="<?php echo h($user['client_name'] ?? 'No client'); ?>"
                                data-full-name="<?php echo h($user['full_name']); ?>"
                                data-email="<?php echo h($user['email']); ?>"
                            >
                                <td>
                                    <div class="user-cell">
                                        <div class="avatar avatar-blue"><?php echo h(user_initials($user['full_name'])); ?></div>
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?php echo h($user['full_name']); ?>
                                                <?php if ($isCurrentUser): ?>
                                                    <span class="current-user-pill">You</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-email"><?php echo h($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted text-sm"><?php echo h($user['client_name'] ?? 'No client'); ?></td>
                                <td><span class="role-badge <?php echo role_badge($user['role']); ?>"><?php echo h(ucfirst($user['role'])); ?></span></td>
                                <td class="text-muted text-sm"><?php echo h(format_login($user['last_login_at'])); ?></td>
                                <td>
                                    <?php if ($user['status'] === 'pending'): ?>
                                        <span class="role-badge badge-pending">Pending</span>
                                    <?php else: ?>
                                        <form method="post" action="actions/users_manage.php" class="status-toggle-form">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                            <div class="form-check form-switch custom-switch">
                                                <input
                                                    class="form-check-input user-status-switch"
                                                    type="checkbox"
                                                    role="switch"
                                                    aria-label="Toggle user status"
                                                    <?php echo $user['status'] === 'active' ? 'checked' : ''; ?>
                                                    <?php echo $isCurrentUser ? 'disabled' : ''; ?>
                                                >
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown user-actions">
                                        <button class="btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button
                                                    class="dropdown-item edit-user-action"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                    Edit user
                                                </button>
                                            </li>
                                            <li>
                                                <form method="post" action="actions/users_manage.php">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                    <button class="dropdown-item" type="submit" <?php echo $isCurrentUser ? 'disabled' : ''; ?>>
                                                        <i class="bi <?php echo $user['status'] === 'active' ? 'bi-person-slash' : 'bi-person-check'; ?>"></i>
                                                        <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="post" action="actions/users_manage.php" class="delete-user-form">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                    <button class="dropdown-item text-danger" type="submit" <?php echo $isCurrentUser ? 'disabled' : ''; ?>>
                                                        <i class="bi bi-trash"></i>
                                                        Delete user
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="pagination-container">
                    <button class="btn-page"><i class="bi bi-chevron-left" style="font-size: 10px;"></i></button>
                    <button class="btn-page active">1</button>
                    <button class="btn-page">2</button>
                    <button class="btn-page">3</button>
                    <button class="btn-page"><i class="bi bi-chevron-right" style="font-size: 10px;"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm" method="post" action="actions/users_manage.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="editFullName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="editRole">
                                <option value="admin">Admin</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        <div class="mb-3" id="editUserClientDiv">
                            <label class="form-label">Client Company</label>
                            <select class="form-select" name="client_id" id="editUserClientId">
                                <option value="">No client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo (int) $client['id']; ?>"><?php echo h($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus">
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="text" class="form-control" name="password" id="editPassword" placeholder="Leave blank to keep current password">
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

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addUserForm" method="post" action="actions/users_add.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="addUserRole" name="role">
                                <option value="">-- Select a role --</option>
                                <option value="admin">Admin</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        <div class="mb-3" id="addUserClientDiv">
                            <label class="form-label">Client Company</label>
                            <select class="form-select" id="addUserClientId" name="client_id">
                                <option value="">No client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo (int) $client['id']; ?>"><?php echo h($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Temporary Password</label>
                            <input type="text" class="form-control" name="password" placeholder="Temp1234!">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/users.js"></script>
</body>
</html>
