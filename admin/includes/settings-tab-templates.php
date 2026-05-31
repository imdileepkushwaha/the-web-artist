<div class="settings-email-templates">
    <div class="panel settings-card">
        <?php
        $panelTitle = 'Quick Reply Templates';
        $panelMeta = count($emailTemplates) . ' template' . (count($emailTemplates) === 1 ? '' : 's') . ' for enquiry replies';
        $panelIconSvg = panelIconSvg('templates');
        $panelIconColor = 'orange';
        require __DIR__ . '/panel-header.php';
        ?>
        <div class="panel-body">
            <div class="info-banner info-banner-compact">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <div>Use placeholders: <code>{name}</code> <code>{email}</code> <code>{phone}</code> <code>{service}</code> <code>{message}</code> <code>{site_phone}</code></div>
            </div>
            <div class="cms-list email-template-list">
                <?php if (empty($emailTemplates)): ?>
                    <div class="empty-state compact">No templates yet. Create one using the form.</div>
                <?php else: ?>
                    <?php foreach ($emailTemplates as $item): ?>
                        <div class="cms-list-item">
                            <div class="cms-list-main">
                                <div class="cms-list-icon">✉️</div>
                                <div class="cms-list-text">
                                    <strong><?= sanitize($item['name']) ?></strong>
                                    <div class="cms-list-meta">
                                        <span><?= sanitize($item['subject']) ?></span>
                                        <?php if (!empty($item['allows_attachment'])): ?>
                                            <span class="badge badge-read">Attachment</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="cms-list-actions">
                                <a href="settings.php?tab=templates&amp;edit_template=<?= (int) $item['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Delete this template?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="template_delete">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel settings-card">
        <?php
        $panelTitle = $editTemplate ? 'Edit Template' : 'Add Template';
        $panelMeta = 'Used on enquiry detail quick replies';
        $panelIconSvg = panelIconSvg('templates');
        $panelIconColor = 'orange';
        $panelAccent = true;
        require __DIR__ . '/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="template_save">
                <input type="hidden" name="id" value="<?= (int) ($editTemplate['id'] ?? 0) ?>">
                <div class="form-group">
                    <label for="template_name">Template Name</label>
                    <input type="text" id="template_name" name="name" required value="<?= sanitize($editTemplate['name'] ?? '') ?>" placeholder="e.g. Follow Up">
                </div>
                <div class="form-group">
                    <label for="template_subject">Email Subject</label>
                    <input type="text" id="template_subject" name="subject" required value="<?= sanitize($editTemplate['subject'] ?? '') ?>" placeholder="Re: Your enquiry for {service}">
                </div>
                <div class="form-group">
                    <label for="template_body">Email Body</label>
                    <textarea id="template_body" name="body" rows="8" required placeholder="Hi {name}, ..."><?= sanitize($editTemplate['body'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="allows_attachment" value="1" <?= !empty($editTemplate['allows_attachment']) ? 'checked' : '' ?>>
                        Allow file attachment when sending (e.g. proposal PDF)
                    </label>
                    <span class="form-hint">When enabled, enquiry page shows an attachment field after this template is selected.</span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editTemplate ? 'Update Template' : 'Create Template' ?></button>
                    <?php if ($editTemplate): ?>
                        <a href="settings.php?tab=templates" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
