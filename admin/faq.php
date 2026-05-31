<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$editId = (int) ($_GET['edit'] ?? 0);
$editItem = $editId ? getFaqItemById($conn, $editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = !empty($_POST['is_active']);

        if ($question === '' || $answer === '') {
            flashMessage('error', 'Question and answer are required.');
        } elseif ($id && updateFaqItem($conn, $id, $question, $answer, $sortOrder, $isActive)) {
            logActivity($conn, 'faq_update', 'faq', $id);
            flashMessage('success', 'FAQ updated.');
        } elseif (!$id && createFaqItem($conn, $question, $answer, $sortOrder, $isActive)) {
            logActivity($conn, 'faq_create', 'faq', null);
            flashMessage('success', 'FAQ created.');
        } else {
            flashMessage('error', 'Unable to save FAQ.');
        }
    }

    if ($action === 'delete' && ($id = (int) ($_POST['id'] ?? 0))) {
        deleteFaqItem($conn, $id);
        logActivity($conn, 'faq_delete', 'faq', $id);
        flashMessage('success', 'FAQ deleted.');
    }

    header('Location: faq.php');
    exit;
}

$items = getFaqItems($conn);
$pageTitle = 'Manage FAQ';
$activePage = 'faq.php';
require __DIR__ . '/includes/header.php';
?>

<div class="cms-grid">
    <div class="panel cms-list-panel">
        <?php
        $panelTitle = 'FAQ Items';
        $panelMeta = count($items) . ' question' . (count($items) === 1 ? '' : 's') . ' on website';
        $panelIconSvg = panelIconSvg('faq');
        $panelIconColor = 'purple';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <h3>No FAQ items yet</h3>
                    <p>Add your first question using the form on the right.</p>
                </div>
            <?php else: ?>
                <div class="cms-list">
                    <?php foreach ($items as $index => $item): ?>
                        <div class="cms-list-item">
                            <div class="cms-list-main">
                                <div class="cms-list-icon">❓</div>
                                <div class="cms-list-text">
                                    <strong><?= sanitize($item['question']) ?></strong>
                                    <div class="cms-list-meta">
                                        <span class="sort-order-badge">#<?= (int) $item['sort_order'] ?></span>
                                        <span class="cms-status <?= $item['is_active'] ? 'active' : 'hidden' ?>">
                                            <span class="cms-status-dot"></span>
                                            <?= $item['is_active'] ? 'Active' : 'Hidden' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="cms-list-actions">
                                <a href="faq.php?edit=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel cms-form-panel">
        <?php
        $panelTitle = $editItem ? 'Edit FAQ' : 'Add FAQ';
        $panelMeta = $editItem ? 'Update this question' : 'Create a new FAQ entry';
        $panelIconSvg = panelIconSvg('faq');
        $panelIconColor = 'purple';
        $panelAccent = true;
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editItem['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="question">Question</label>
                    <input type="text" id="question" name="question" required value="<?= sanitize($editItem['question'] ?? '') ?>" placeholder="e.g. What services do you offer?">
                </div>
                <div class="form-group">
                    <label for="answer">Answer</label>
                    <textarea id="answer" name="answer" rows="5" required placeholder="Write a clear, helpful answer..."><?= sanitize($editItem['answer'] ?? '') ?></textarea>
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
                    <button type="submit" class="btn btn-primary"><?= $editItem ? 'Update FAQ' : 'Add FAQ' ?></button>
                    <?php if ($editItem): ?><a href="faq.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
                </div>
            </form>
            <?php if ($editItem): ?>
                <form method="POST" data-confirm-delete style="margin-top:16px;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $editItem['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">Delete FAQ</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
