<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();

if (isset($_GET['download'])) {
    $sql = generateDatabaseBackup($conn);
    logActivity($conn, 'database_backup', 'system', null, 'Downloaded database backup');

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="twa-backup-' . date('Y-m-d-His') . '.sql"');
    echo $sql;
    exit;
}

$pageTitle = 'Backup';
$activePage = 'backup.php';
require __DIR__ . '/includes/header.php';
?>

<div class="panel backup-card">
    <?php
    $panelTitle = 'Database Backup';
    $panelMeta = 'Export all site data as a SQL file';
    $panelIconSvg = panelIconSvg('backup');
    $panelIconColor = 'green';
    require __DIR__ . '/includes/panel-header.php';
    ?>
    <div class="panel-body">
        <div class="backup-hero">
            <div class="backup-hero-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </div>
            <div>
                <h3>Download full database backup</h3>
                <p>Creates a .sql file with all enquiries, settings, content, users, and analytics data. Keep backups in a secure location.</p>
            </div>
        </div>

        <ul class="backup-checklist">
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Enquiries & notes
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                FAQ, services & testimonials
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Admin users & settings
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Visit analytics & activity logs
            </li>
        </ul>

        <a href="backup.php?download=1" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download Backup (.sql)
        </a>

        <div class="backup-warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Password hashes are included in the backup. Store the file securely and never share it publicly.</span>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
