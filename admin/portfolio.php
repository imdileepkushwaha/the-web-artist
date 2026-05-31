<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$section = $_GET['section'] ?? 'projects';
if (!in_array($section, ['projects', 'trusted'], true)) {
    $section = 'projects';
}

$editProjectId = (int) ($_GET['edit_project'] ?? 0);
$editTrustedId = (int) ($_GET['edit_trusted'] ?? 0);
$editProject = $editProjectId ? getPortfolioProjectById($conn, $editProjectId) : null;
$editTrusted = $editTrustedId ? getTrustedClientById($conn, $editTrustedId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_project') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $projectUrl = trim($_POST['project_url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = !empty($_POST['is_active']);

        if ($title === '' || $description === '') {
            flashMessage('error', 'Title and description are required.');
        } elseif ($id && updatePortfolioProject($conn, $id, $title, $category, $description, $imageUrl, $projectUrl, $sortOrder, $isActive)) {
            logActivity($conn, 'portfolio_update', 'portfolio', $id);
            flashMessage('success', 'Project updated.');
        } elseif (!$id && createPortfolioProject($conn, $title, $category, $description, $imageUrl, $projectUrl, $sortOrder, $isActive)) {
            logActivity($conn, 'portfolio_create', 'portfolio', null);
            flashMessage('success', 'Project created.');
        } else {
            flashMessage('error', 'Unable to save project.');
        }
    }

    if ($action === 'delete_project' && ($id = (int) ($_POST['id'] ?? 0))) {
        deletePortfolioProject($conn, $id);
        logActivity($conn, 'portfolio_delete', 'portfolio', $id);
        flashMessage('success', 'Project deleted.');
    }

    if ($action === 'save_trusted') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $logoText = trim($_POST['logo_text'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = !empty($_POST['is_active']);

        if ($name === '') {
            flashMessage('error', 'Client name is required.');
        } elseif ($id && updateTrustedClient($conn, $id, $name, $logoText, $sortOrder, $isActive)) {
            logActivity($conn, 'trusted_update', 'trusted', $id);
            flashMessage('success', 'Client updated.');
        } elseif (!$id && createTrustedClient($conn, $name, $logoText, $sortOrder, $isActive)) {
            logActivity($conn, 'trusted_create', 'trusted', null);
            flashMessage('success', 'Client added.');
        } else {
            flashMessage('error', 'Unable to save client.');
        }
    }

    if ($action === 'delete_trusted' && ($id = (int) ($_POST['id'] ?? 0))) {
        deleteTrustedClient($conn, $id);
        logActivity($conn, 'trusted_delete', 'trusted', $id);
        flashMessage('success', 'Client removed.');
    }

    header('Location: portfolio.php?section=' . urlencode($section));
    exit;
}

$projects = getPortfolioProjects($conn);
$trustedClients = getTrustedClients($conn);
$pageTitle = 'Portfolio';
$activePage = 'portfolio.php';
require __DIR__ . '/includes/header.php';
?>

<div class="portfolio-tabs">
    <a href="portfolio.php?section=projects" class="portfolio-tab <?= $section === 'projects' ? 'active' : '' ?>">Projects</a>
    <a href="portfolio.php?section=trusted" class="portfolio-tab <?= $section === 'trusted' ? 'active' : '' ?>">Trusted By</a>
</div>

<?php if ($section === 'projects'): ?>
<div class="cms-grid">
    <div class="panel cms-list-panel">
        <?php
        $panelTitle = 'Portfolio Projects';
        $panelMeta = count($projects) . ' project' . (count($projects) === 1 ? '' : 's');
        $panelIconSvg = panelIconSvg('services');
        $panelIconColor = 'purple';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <div class="cms-list">
                <?php foreach ($projects as $item): ?>
                    <div class="cms-list-item">
                        <div class="cms-list-main">
                            <div class="cms-list-icon">📁</div>
                            <div class="cms-list-text">
                                <strong><?= sanitize($item['title']) ?></strong>
                                <div class="cms-list-meta">
                                    <span class="sort-order-badge">#<?= (int) $item['sort_order'] ?></span>
                                    <span><?= sanitize($item['category']) ?></span>
                                    <span class="cms-status <?= $item['is_active'] ? 'active' : 'hidden' ?>">
                                        <span class="cms-status-dot"></span>
                                        <?= $item['is_active'] ? 'Active' : 'Hidden' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="cms-list-actions">
                            <a href="portfolio.php?section=projects&edit_project=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panel cms-form-panel">
        <?php
        $panelTitle = $editProject ? 'Edit Project' : 'Add Project';
        $panelMeta = 'Showcase work on the homepage';
        $panelIconSvg = panelIconSvg('services');
        $panelIconColor = 'purple';
        $panelAccent = true;
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_project">
                <input type="hidden" name="id" value="<?= (int) ($editProject['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="title">Project Title</label>
                    <input type="text" id="title" name="title" required value="<?= sanitize($editProject['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?= sanitize($editProject['category'] ?? '') ?>" placeholder="Healthcare, Ecommerce">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required><?= sanitize($editProject['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url" value="<?= sanitize($editProject['image_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label for="project_url">Project URL</label>
                    <input type="url" id="project_url" name="project_url" value="<?= sanitize($editProject['project_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <?php
                $sortOrderValue = (int) ($editProject['sort_order'] ?? 0);
                require __DIR__ . '/includes/sort-order-field.php';
                ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?= ($editProject['is_active'] ?? 1) ? 'checked' : '' ?>>
                        Show on website
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Project</button>
                    <?php if ($editProject): ?><a href="portfolio.php?section=projects" class="btn btn-secondary">Cancel</a><?php endif; ?>
                </div>
            </form>
            <?php if ($editProject): ?>
            <form method="POST" class="admin-form" style="margin-top:16px;" data-confirm-delete>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_project">
                <input type="hidden" name="id" value="<?= (int) $editProject['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete Project</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="cms-grid">
    <div class="panel cms-list-panel">
        <?php
        $panelTitle = 'Trusted By';
        $panelMeta = count($trustedClients) . ' client' . (count($trustedClients) === 1 ? '' : 's');
        $panelIconSvg = panelIconSvg('testimonials');
        $panelIconColor = 'green';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <div class="cms-list">
                <?php foreach ($trustedClients as $item): ?>
                    <div class="cms-list-item">
                        <div class="cms-list-main">
                            <div class="cms-list-icon"><?= sanitize($item['logo_text'] ?: strtoupper(substr($item['name'], 0, 2))) ?></div>
                            <div class="cms-list-text">
                                <strong><?= sanitize($item['name']) ?></strong>
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
                            <a href="portfolio.php?section=trusted&edit_trusted=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panel cms-form-panel">
        <?php
        $panelTitle = $editTrusted ? 'Edit Client' : 'Add Client';
        $panelMeta = 'Logo badges on homepage';
        $panelIconSvg = panelIconSvg('testimonials');
        $panelIconColor = 'green';
        $panelAccent = true;
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_trusted">
                <input type="hidden" name="id" value="<?= (int) ($editTrusted['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="name">Client Name</label>
                    <input type="text" id="name" name="name" required value="<?= sanitize($editTrusted['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="logo_text">Logo Key</label>
                    <input type="text" id="logo_text" name="logo_text" maxlength="30" value="<?= sanitize($editTrusted['logo_text'] ?? '') ?>" placeholder="technova">
                    <p class="form-hint">Use: <code>technova</code>, <code>healthsync</code>, <code>educore</code>, or <code>retailpro</code> for SVG logos. Leave blank for auto fallback.</p>
                </div>
                <?php
                $sortOrderValue = (int) ($editTrusted['sort_order'] ?? 0);
                require __DIR__ . '/includes/sort-order-field.php';
                ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?= ($editTrusted['is_active'] ?? 1) ? 'checked' : '' ?>>
                        Show on website
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Client</button>
                    <?php if ($editTrusted): ?><a href="portfolio.php?section=trusted" class="btn btn-secondary">Cancel</a><?php endif; ?>
                </div>
            </form>
            <?php if ($editTrusted): ?>
            <form method="POST" class="admin-form" style="margin-top:16px;" data-confirm-delete>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_trusted">
                <input type="hidden" name="id" value="<?= (int) $editTrusted['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete Client</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
