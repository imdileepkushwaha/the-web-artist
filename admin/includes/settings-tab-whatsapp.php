<?php
$whatsappTemplates = getWhatsAppTemplates($conn);
$editWaId = (int) ($_GET['edit_whatsapp'] ?? 0);
$editWaTemplate = $editWaId ? getWhatsAppTemplateById($conn, $editWaId) : null;
?>
<div class="panel settings-card">
    <?php
    $panelTitle = 'WhatsApp Templates';
    $panelMeta = 'Quick reply messages on enquiry detail page';
    $panelIconSvg = panelIconSvg('templates');
    $panelIconColor = 'green';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <div class="info-banner-compact">
            <div>Use placeholders: <code>{name}</code> <code>{service}</code> <code>{admin_name}</code> <code>{phone}</code></div>
        </div>

        <?php if (!$editWaTemplate): ?>
        <div class="table-responsive" style="margin-bottom:24px;">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Preview</th><th></th></tr></thead>
                <tbody>
                    <?php if (empty($whatsappTemplates)): ?>
                        <tr><td colspan="3" class="empty-state">No templates yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($whatsappTemplates as $item): ?>
                            <tr>
                                <td><?= sanitize($item['name']) ?></td>
                                <td><?= sanitize(mb_substr($item['body'], 0, 80)) ?>…</td>
                                <td class="table-actions">
                                    <a href="settings.php?tab=whatsapp&amp;edit_whatsapp=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" style="display:inline;" data-confirm-delete="Delete this template?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="whatsapp_delete">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <form method="POST" class="admin-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="whatsapp_save">
            <input type="hidden" name="id" value="<?= (int) ($editWaTemplate['id'] ?? 0) ?>">
            <div class="form-group">
                <label for="wa_template_name">Template name</label>
                <input type="text" id="wa_template_name" name="name" required value="<?= sanitize($editWaTemplate['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="wa_template_body">Message</label>
                <textarea id="wa_template_body" name="body" rows="6" required><?= sanitize($editWaTemplate['body'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editWaTemplate ? 'Update Template' : 'Add Template' ?></button>
                <?php if ($editWaTemplate): ?><a href="settings.php?tab=whatsapp" class="btn btn-secondary">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>
