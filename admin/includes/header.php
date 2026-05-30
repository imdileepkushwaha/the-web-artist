<?php
$pageTitle = $pageTitle ?? 'Admin Panel';
$activePage = $activePage ?? '';

$notificationCount = 0;
$notifications = [];
$darkModeDefault = '0';

if (function_exists('isAdminLoggedIn') && isAdminLoggedIn() && function_exists('getAdminDb')) {
    $topbarConn = getAdminDb();
    $notifications = getAdminNotifications($topbarConn, 8);
    $notificationCount = getNewEnquiryNotificationCount($topbarConn);
    $darkModeDefault = getSetting($topbarConn, 'dark_mode', '0') ?: '0';
}

function navActive(string $page, string $activePage = ''): string
{
    if ($activePage !== '' && $activePage === $page) {
        return 'active';
    }

    return activeNav($page);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title><?= sanitize($pageTitle) ?> | The Web Artist Admin</title>
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script>window.ADMIN_DARK_DEFAULT = <?= json_encode($darkModeDefault === '1') ?>;</script>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">TWA</div>
            <div>
                <strong>The Web Artist</strong>
                <span>Admin Panel</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group">
                <span class="nav-section-label">Main</span>
                <div class="nav-group-items">
                    <a href="index.php" class="nav-item <?= navActive('index.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                        <span class="nav-item-label">Dashboard</span>
                    </a>
                    <a href="enquiries.php" class="nav-item <?= navActive('enquiries.php', $activePage) || navActive('enquiry.php', $activePage) ? 'active' : '' ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg></span>
                        <span class="nav-item-label">Enquiries</span>
                        <?php if ($notificationCount > 0): ?>
                            <span class="nav-item-badge"><?= $notificationCount > 9 ? '9+' : $notificationCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="analytics.php" class="nav-item <?= navActive('analytics.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg></span>
                        <span class="nav-item-label">Analytics</span>
                    </a>
                </div>
            </div>

            <div class="nav-group">
                <span class="nav-section-label">Content</span>
                <div class="nav-group-items">
                    <a href="faq.php" class="nav-item <?= navActive('faq.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                        <span class="nav-item-label">FAQ</span>
                    </a>
                    <a href="testimonials.php" class="nav-item <?= navActive('testimonials.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                        <span class="nav-item-label">Testimonials</span>
                    </a>
                    <a href="services.php" class="nav-item <?= navActive('services.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span>
                        <span class="nav-item-label">Services</span>
                    </a>
                </div>
            </div>

            <?php if (isAdminRole()): ?>
            <div class="nav-group">
                <span class="nav-section-label">System</span>
                <div class="nav-group-items">
                    <a href="settings.php" class="nav-item <?= navActive('settings.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                        <span class="nav-item-label">Settings</span>
                    </a>
                    <a href="users.php" class="nav-item <?= navActive('users.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                        <span class="nav-item-label">Users</span>
                    </a>
                    <a href="activity.php" class="nav-item <?= navActive('activity.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
                        <span class="nav-item-label">Activity</span>
                    </a>
                    <a href="backup.php" class="nav-item <?= navActive('backup.php', $activePage) ?>">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
                        <span class="nav-item-label">Backup</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="nav-group nav-group-last">
                <span class="nav-section-label">Website</span>
                <div class="nav-group-items">
                    <a href="../index.php" class="nav-item nav-item-external" target="_blank" rel="noopener noreferrer">
                        <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
                        <span class="nav-item-label">View Website</span>
                        <span class="nav-item-trail" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7"/><path d="M7 7h10v10"/></svg></span>
                    </a>
                </div>
            </div>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <span></span><span></span><span></span>
                </button>
                <div class="topbar-title">
                    <h1><?= sanitize($pageTitle) ?></h1>
                </div>
            </div>

            <div class="topbar-tools">
                <div class="global-search-wrap" id="globalSearchWrap">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="search" id="globalSearch" placeholder="Search enquiries, FAQ..." autocomplete="off" aria-label="Global search">
                    <div class="global-search-results" id="globalSearchResults" hidden></div>
                </div>

                <!-- <button type="button" class="topbar-icon-btn" id="darkModeToggle" aria-label="Toggle dark mode" title="Dark mode">
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <svg class="icon-sun icon-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                </button> -->

                <button type="button" class="topbar-icon-btn" id="fullscreenToggle" aria-label="Toggle fullscreen" title="Fullscreen">
                    <svg class="icon-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    <svg class="icon-compress icon-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>
                </button>

                <div class="topbar-dropdown" id="notificationDropdown">
                    <button type="button" class="topbar-icon-btn topbar-notify-btn" id="notificationBtn" aria-label="Notifications" aria-expanded="false" data-count="<?= (int) $notificationCount ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span class="notify-badge" id="notifyBadge" <?= $notificationCount === 0 ? 'hidden' : '' ?>><?= $notificationCount > 9 ? '9+' : $notificationCount ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-notifications">
                        <div class="dropdown-header">
                            <h3>Notifications</h3>
                            <span class="dropdown-badge" id="notifyHeaderBadge" <?= $notificationCount === 0 ? 'hidden' : '' ?>><?= $notificationCount ?> new</span>
                        </div>
                        <div class="dropdown-body" id="notificationList">
                            <?php if (empty($notifications)): ?>
                                <div class="dropdown-empty">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                    <p>No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="enquiry.php?id=<?= (int) $notification['id'] ?>" class="notification-item <?= $notification['status'] === 'new' ? 'unread is-new' : '' ?>" data-id="<?= (int) $notification['id'] ?>">
                                        <div class="notification-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                                        </div>
                                        <div class="notification-content">
                                            <?php if ($notification['status'] === 'new'): ?>
                                                <span class="notification-new-label">New</span>
                                            <?php endif; ?>
                                            <strong><?= sanitize($notification['name']) ?></strong>
                                            <span><?= sanitize($notification['service']) ?></span>
                                            <time><?= sanitize(timeAgo($notification['created_at'])) ?></time>
                                        </div>
                                        <?php if ($notification['status'] === 'new'): ?>
                                            <span class="notification-dot"></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-footer">
                            <a href="enquiries.php?status=new">View all new enquiries →</a>
                        </div>
                    </div>
                </div>

                <div class="topbar-dropdown" id="profileDropdown">
                    <button type="button" class="topbar-profile-btn" aria-label="Profile menu" aria-expanded="false">
                        <div class="topbar-avatar"><?= strtoupper(substr(adminDisplayName(), 0, 1)) ?></div>
                        <div class="topbar-profile-info">
                            <strong><?= sanitize(adminDisplayName()) ?></strong>
                            <span><?= isAdminRole() ? 'Administrator' : 'Viewer' ?></span>
                        </div>
                        <svg class="profile-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dropdown-menu dropdown-profile">
                        <div class="profile-dropdown-header">
                            <div class="topbar-avatar lg"><?= strtoupper(substr(adminDisplayName(), 0, 1)) ?></div>
                            <div>
                                <strong><?= sanitize(adminDisplayName()) ?></strong>
                                <span><?= isAdminRole() ? 'Administrator' : 'Viewer' ?></span>
                            </div>
                        </div>
                        <div class="dropdown-links">
                            <a href="index.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                Dashboard
                            </a>
                            <a href="enquiries.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                                Enquiries
                            </a>
                            <a href="analytics.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                Analytics
                            </a>
                            <?php if (isAdminRole()): ?>
                            <a href="settings.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
                                Settings
                            </a>
                            <?php endif; ?>
                            <a href="../index.php" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                View Website
                            </a>
                        </div>
                        <div class="dropdown-footer">
                            <a href="logout.php" class="profile-logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
            <?php endif; ?>
