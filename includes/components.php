<?php

 
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

function user_initials_from_name(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    foreach (array_slice($parts ?: [], 0, 2) as $part) {
        $initials .= strtoupper($part[0] ?? '');
    }

    return $initials !== '' ? $initials : 'AU';
}

function render_sidebar(string $activePage): void
{
    if (function_exists('auth_user_role') && auth_user_role() === 'viewer') {
        $items = [
            'licenses' => ['href' => 'licence.php', 'icon' => 'bi-key', 'label' => 'Licenses'],
        ];
    } else {
        $items = [
            'dashboard' => ['href' => 'dashboard.php', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard'],
            'licenses' => ['href' => 'licence.php', 'icon' => 'bi-key', 'label' => 'Licenses'],
            'users' => ['href' => 'users.php', 'icon' => 'bi-people', 'label' => 'Users'],
            'clients' => ['href' => 'clients.php', 'icon' => 'bi-buildings', 'label' => 'Clients'],
        ];
    }
    ?>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="sidebar-title-group">
                <span class="sidebar-title">LicensePro</span>
                <span class="sidebar-title">Admin</span>
                <span class="sidebar-subtitle">Enterprise Console</span>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Main navigation">
            <?php foreach ($items as $key => $item): ?>
                <a href="<?php echo h($item['href']); ?>" class="nav-item-link<?php echo $activePage === $key ? ' active' : ''; ?>">
                    <i class="bi <?php echo h($item['icon']); ?>"></i> <?php echo h($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php
}

function render_top_nav(string $searchPlaceholder = 'Search...'): void
{
    $user = current_user();
    $name = $user['full_name'] ?? 'Admin User';
    $email = $user['email'] ?? 'admin@organization.com';
    $role = $user['role'] ?? 'admin';
    $initials = user_initials_from_name($name);
    ?>
    <div class="top-navbar">
        <div class="d-flex align-items-center">
            <span class="navbar-brand-text">LicensePro</span>
            <div class="search-container">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" id="globalSearch" placeholder="<?php echo h($searchPlaceholder); ?>" aria-label="Global search">
            </div>
        </div>

        <div class="top-nav-icons">
            <div class="dropdown account-dropdown">
                <button class="account-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <span class="user-avatar user-avatar-initials"><?php echo h($initials); ?></span>
                    <span class="account-menu-text">
                        <span class="account-name"><?php echo h($name); ?></span>
                        <span class="account-role"><?php echo h(ucfirst((string) $role)); ?></span>
                    </span>
                    <i class="bi bi-chevron-down account-chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end account-menu">
                    <li class="account-menu-header">
                        <span class="account-menu-name"><?php echo h($name); ?></span>
                        <span class="account-menu-email"><?php echo h($email); ?></span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item account-menu-item" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            Sign out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

function render_header(): void
{
    $user = current_user();
    $name = $user['full_name'] ?? 'Admin User';
    $email = $user['email'] ?? 'admin@organization.com';
    $role = $user['role'] ?? 'admin';
    $initials = user_initials_from_name($name);
    ?>
    <div class="top-navbar">
        <div class="d-flex align-items-center">
            <span class="navbar-brand-text">LicensePro</span>
        </div>

        <div class="top-nav-icons">
            <div class="dropdown account-dropdown">
                <button class="account-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <span class="user-avatar user-avatar-initials"><?php echo h($initials); ?></span>
                    <span class="account-menu-text">
                        <span class="account-name"><?php echo h($name); ?></span>
                        <span class="account-role"><?php echo h(ucfirst((string) $role)); ?></span>
                    </span>
                    <i class="bi bi-chevron-down account-chevron"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end account-menu">
                    <li class="account-menu-header">
                        <span class="account-menu-name"><?php echo h($name); ?></span>
                        <span class="account-menu-email"><?php echo h($email); ?></span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item account-menu-item" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            Sign out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}
