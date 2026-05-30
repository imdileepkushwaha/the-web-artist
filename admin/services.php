<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$editId = (int) ($_GET['edit'] ?? 0);
$editItem = $editId ? getServiceById($conn, $editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $iconEmoji = trim($_POST['icon_emoji'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = !empty($_POST['is_active']);

        if ($title === '' || $description === '') {
            flashMessage('error', 'Title and description are required.');
        } elseif ($id && updateService($conn, $id, $title, $description, $iconEmoji, $sortOrder, $isActive)) {
            logActivity($conn, 'service_update', 'service', $id);
            flashMessage('success', 'Service updated.');
        } elseif (!$id && createService($conn, $title, $description, $iconEmoji, $sortOrder, $isActive)) {
            logActivity($conn, 'service_create', 'service', null);
            flashMessage('success', 'Service created.');
        } else {
            flashMessage('error', 'Unable to save service.');
        }
    }

    if ($action === 'delete' && ($id = (int) ($_POST['id'] ?? 0))) {
        deleteService($conn, $id);
        logActivity($conn, 'service_delete', 'service', $id);
        flashMessage('success', 'Service deleted.');
    }

    header('Location: services.php');
    exit;
}

$items = getServices($conn);
$pageTitle = 'Services';
$activePage = 'services.php';
require __DIR__ . '/includes/header.php';
?>

<div class="cms-grid">
    <div class="panel cms-list-panel">
        <?php
        $panelTitle = 'Services';
        $panelMeta = count($items) . ' service' . (count($items) === 1 ? '' : 's') . ' listed';
        $panelIconSvg = panelIconSvg('services');
        $panelIconColor = 'blue';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <div class="cms-list">
                <?php foreach ($items as $item): ?>
                    <div class="cms-list-item">
                        <div class="cms-list-main">
                            <div class="cms-list-icon"><?= sanitize($item['icon_emoji'] ?: '💻') ?></div>
                            <div class="cms-list-text">
                                <strong><?= sanitize($item['title']) ?></strong>
                                <div class="cms-list-meta">
                                    <span class="sort-order-badge">#<?= (int) $item['sort_order'] ?></span>
                                    <span><?= sanitize(mb_strimwidth($item['description'], 0, 60, '…')) ?></span>
                                    <span class="cms-status <?= $item['is_active'] ? 'active' : 'hidden' ?>">
                                        <span class="cms-status-dot"></span>
                                        <?= $item['is_active'] ? 'Active' : 'Hidden' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="cms-list-actions">
                            <a href="services.php?edit=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panel cms-form-panel">
        <?php
        $panelTitle = $editItem ? 'Edit Service' : 'Add Service';
        $panelMeta = 'Title, emoji icon & description';
        $panelIconSvg = panelIconSvg('services');
        $panelIconColor = 'blue';
        $panelAccent = true;
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editItem['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="icon_emoji">Icon (emoji)</label>
                    <input type="text" id="icon_emoji" name="icon_emoji" value="<?= sanitize($editItem['icon_emoji'] ?? '💻') ?>" maxlength="4">
                </div>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required value="<?= sanitize($editItem['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required><?= sanitize($editItem['description'] ?? '') ?></textarea>
                </div>
                <?php
                $sortOrderValue = (int) ($editItem['sort_order'] ?? 0);
                require __DIR__ . '/includes/sort-order-field.php';
                ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        Show on website
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Service</button>
                    <?php if ($editItem): ?><a href="services.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
