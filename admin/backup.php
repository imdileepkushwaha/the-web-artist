<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$backupDir = dirname(__DIR__) . '/storage/backups';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    if (($_POST['action'] ?? '') === 'restore') {
        if (empty($_FILES['backup_file']['tmp_name'])) {
            flashMessage('error', 'Please choose a .sql backup file.');
            header('Location: ' . adminUrl('backup'));
            exit;
        }

        $sql = (string) file_get_contents($_FILES['backup_file']['tmp_name']);
        $result = restoreDatabaseFromSql($conn, $sql);

        if ($result['success']) {
            logActivity($conn, 'database_restore', 'system', null, 'Restored from uploaded backup');
            flashMessage('success', $result['message']);
        } else {
            flashMessage('error', $result['message']);
        }

        header('Location: ' . adminUrl('backup'));
        exit;
    }

    if (($_POST['action'] ?? '') === 'schedule') {
        setSetting($conn, 'backup_schedule_enabled', isset($_POST['backup_schedule_enabled']) ? '1' : '0');
        setSetting($conn, 'backup_schedule_days', (string) max(1, (int) ($_POST['backup_schedule_days'] ?? 7)));
        flashMessage('success', 'Backup schedule saved.');
        header('Location: ' . adminUrl('backup'));
        exit;
    }
}

if (isset($_GET['download'])) {
    $sql = generateDatabaseBackup($conn);
    logActivity($conn, 'database_backup', 'system', null, 'Downloaded database backup');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="twa-backup-' . date('Y-m-d-His') . '.sql"');
    header('Content-Length: ' . strlen($sql));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $sql;
    exit;
}

$settings = getAllSettings($conn);
$autoBackups = [];
if (is_dir($backupDir)) {
    foreach (glob($backupDir . '/auto-*.sql') ?: [] as $file) {
        $autoBackups[] = ['name' => basename($file), 'size' => filesize($file), 'time' => filemtime($file)];
    }
    usort($autoBackups, static fn($a, $b) => $b['time'] <=> $a['time']);
}

$pageTitle = 'Backup';
$activePage = 'backup';
require __DIR__ . '/includes/header.php';
?>

<div class="panel backup-card">
    <?php
    $panelTitle = 'Database Backup';
    $panelMeta = 'Export, restore and schedule automatic backups';
    $panelIconSvg = panelIconSvg('backup');
    $panelIconColor = 'green';
    require __DIR__ . '/includes/panel-header.php';
    ?>
    <div class="panel-body">
        <div class="backup-layout">
            <div class="backup-col backup-col-main">
                <div class="backup-hero">
                    <div class="backup-hero-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </div>
                    <div>
                        <h3>Download full database backup</h3>
                        <p>Creates a .sql file with all enquiries, settings, content, users, and analytics data.</p>
                    </div>
                </div>

                <a href="?download=1" class="btn btn-primary backup-download-btn">
                    Download Backup (.sql)
                </a>

                <form method="POST" class="admin-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="schedule">
                    <div class="settings-section-divider"><h3>Automatic Backups</h3><p>Runs when an admin opens the dashboard (no server cron required).</p></div>
                    <label class="checkbox-label settings-checkbox-label"><input type="checkbox" name="backup_schedule_enabled" value="1" <?= ($settings['backup_schedule_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> <span>Enable scheduled auto-backup</span></label>
                    <div class="form-group" style="margin-top:12px;">
                        <label for="backup_schedule_days">Every (days)</label>
                        <input type="number" id="backup_schedule_days" name="backup_schedule_days" min="1" max="30" value="<?= sanitize($settings['backup_schedule_days'] ?? '7') ?>" style="max-width:120px;">
                    </div>
                    <?php if (!empty($settings['backup_last_run'])): ?>
                        <p class="form-hint">Last auto backup: <?= sanitize($settings['backup_last_run']) ?></p>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-secondary">Save Schedule</button>
                </form>

                <?php if (!empty($autoBackups)): ?>
                    <div class="settings-section-divider"><h3>Auto-backup files</h3></div>
                    <ul class="backup-checklist">
                        <?php foreach (array_slice($autoBackups, 0, 5) as $file): ?>
                            <li><?= sanitize($file['name']) ?> — <?= date('d M Y, h:i A', $file['time']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="backup-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Password hashes are included in backups. Store files securely.</span>
                </div>
            </div>

            <div class="backup-col backup-col-restore">
                <form method="POST" class="admin-form backup-restore-panel" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="restore">
                    <div class="backup-restore-header">
                        <div class="backup-restore-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </div>
                        <div>
                            <h3>Restore Database</h3>
                            <p>Upload a previously exported .sql file. This will overwrite current data.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="backup_file">Backup file (.sql)</label>
                        <input type="file" id="backup_file" name="backup_file" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block" data-confirm="Restore will overwrite all current database data. Continue?">Restore Database</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
