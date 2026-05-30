<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$editId = (int) ($_GET['edit'] ?? 0);
$editItem = $editId ? getTestimonialById($conn, $editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $clientName = trim($_POST['client_name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $feedback = trim($_POST['feedback'] ?? '');
        $initials = trim($_POST['initials'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = !empty($_POST['is_active']);

        if ($clientName === '' || $feedback === '') {
            flashMessage('error', 'Client name and feedback are required.');
        } elseif ($id && updateTestimonial($conn, $id, $clientName, $company, $feedback, $initials, $sortOrder, $isActive)) {
            logActivity($conn, 'testimonial_update', 'testimonial', $id);
            flashMessage('success', 'Testimonial updated.');
        } elseif (!$id && createTestimonial($conn, $clientName, $company, $feedback, $initials, $sortOrder, $isActive)) {
            logActivity($conn, 'testimonial_create', 'testimonial', null);
            flashMessage('success', 'Testimonial created.');
        } else {
            flashMessage('error', 'Unable to save testimonial.');
        }
    }

    if ($action === 'delete' && ($id = (int) ($_POST['id'] ?? 0))) {
        deleteTestimonial($conn, $id);
        logActivity($conn, 'testimonial_delete', 'testimonial', $id);
        flashMessage('success', 'Testimonial deleted.');
    }

    header('Location: testimonials.php');
    exit;
}

$items = getTestimonials($conn);
$pageTitle = 'Testimonials';
$activePage = 'testimonials.php';
require __DIR__ . '/includes/header.php';
?>

<div class="cms-grid">
    <div class="panel cms-list-panel">
        <?php
        $panelTitle = 'Testimonials';
        $panelMeta = count($items) . ' client review' . (count($items) === 1 ? '' : 's');
        $panelIconSvg = panelIconSvg('testimonials');
        $panelIconColor = 'green';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <h3>No testimonials yet</h3>
                    <p>Add client feedback using the form on the right.</p>
                </div>
            <?php else: ?>
                <div class="cms-list">
                    <?php foreach ($items as $item): ?>
                        <div class="cms-list-item">
                            <div class="cms-list-main">
                                <div class="cms-list-icon"><?= sanitize($item['initials'] ?: strtoupper(substr($item['client_name'], 0, 2))) ?></div>
                                <div class="cms-list-text">
                                    <strong><?= sanitize($item['client_name']) ?></strong>
                                    <div class="cms-list-meta">
                                        <span class="sort-order-badge">#<?= (int) $item['sort_order'] ?></span>
                                        <span><?= sanitize($item['company'] ?: 'No company') ?></span>
                                        <span class="cms-status <?= $item['is_active'] ? 'active' : 'hidden' ?>">
                                            <span class="cms-status-dot"></span>
                                            <?= $item['is_active'] ? 'Active' : 'Hidden' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="cms-list-actions">
                                <a href="testimonials.php?edit=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel cms-form-panel">
        <?php
        $panelTitle = $editItem ? 'Edit Testimonial' : 'Add Testimonial';
        $panelMeta = 'Client name, company & feedback';
        $panelIconSvg = panelIconSvg('testimonials');
        $panelIconColor = 'green';
        $panelAccent = true;
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editItem['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="client_name">Client Name</label>
                    <input type="text" id="client_name" name="client_name" required value="<?= sanitize($editItem['client_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="company">Company</label>
                    <input type="text" id="company" name="company" value="<?= sanitize($editItem['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="initials">Avatar Initials</label>
                    <input type="text" id="initials" name="initials" maxlength="3" value="<?= sanitize($editItem['initials'] ?? '') ?>" placeholder="e.g. RK">
                </div>
                <div class="form-group">
                    <label for="feedback">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="4" required placeholder="What did the client say?"><?= sanitize($editItem['feedback'] ?? '') ?></textarea>
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
                    <button type="submit" class="btn btn-primary">Save Testimonial</button>
                    <?php if ($editItem): ?><a href="testimonials.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
