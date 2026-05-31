<div class="panel settings-card">
    <?php
    $panelTitle = 'Homepage Content';
    $panelMeta = 'Hero, about section, contact info & WhatsApp';
    $panelIconSvg = panelIconSvg('site');
    $panelIconColor = 'purple';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <form method="POST" class="admin-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="homepage">

            <div class="settings-section-divider settings-section-divider-first">
                <h3>Hero Section</h3>
                <p>Main headline and trust stats on the homepage.</p>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="hero_badge">Badge Text</label>
                    <input type="text" id="hero_badge" name="hero_badge" value="<?= sanitize($settings['hero_badge'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_tags">Tags (comma separated)</label>
                    <input type="text" id="hero_tags" name="hero_tags" value="<?= sanitize($settings['hero_tags'] ?? '') ?>" placeholder="Ecommerce, Healthcare, Education">
                </div>
                <div class="form-group">
                    <label for="hero_title_line1">Title Line 1</label>
                    <input type="text" id="hero_title_line1" name="hero_title_line1" value="<?= sanitize($settings['hero_title_line1'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_title_accent">Title Accent (gradient)</label>
                    <input type="text" id="hero_title_accent" name="hero_title_accent" value="<?= sanitize($settings['hero_title_accent'] ?? '') ?>">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="hero_title_line2">Title Line 2</label>
                    <input type="text" id="hero_title_line2" name="hero_title_line2" value="<?= sanitize($settings['hero_title_line2'] ?? '') ?>">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="hero_subtitle">Subtitle</label>
                    <textarea id="hero_subtitle" name="hero_subtitle" rows="2"><?= sanitize($settings['hero_subtitle'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="hero_stat1_num">Stat 1 Number</label>
                    <input type="text" id="hero_stat1_num" name="hero_stat1_num" value="<?= sanitize($settings['hero_stat1_num'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_stat1_label">Stat 1 Label</label>
                    <input type="text" id="hero_stat1_label" name="hero_stat1_label" value="<?= sanitize($settings['hero_stat1_label'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_stat2_num">Stat 2 Number</label>
                    <input type="text" id="hero_stat2_num" name="hero_stat2_num" value="<?= sanitize($settings['hero_stat2_num'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_stat2_label">Stat 2 Label</label>
                    <input type="text" id="hero_stat2_label" name="hero_stat2_label" value="<?= sanitize($settings['hero_stat2_label'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_stat3_num">Stat 3 Number</label>
                    <input type="text" id="hero_stat3_num" name="hero_stat3_num" value="<?= sanitize($settings['hero_stat3_num'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_stat3_label">Stat 3 Label</label>
                    <input type="text" id="hero_stat3_label" name="hero_stat3_label" value="<?= sanitize($settings['hero_stat3_label'] ?? '') ?>">
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>Demo Form</h3>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="hero_form_badge">Form Badge</label>
                    <input type="text" id="hero_form_badge" name="hero_form_badge" value="<?= sanitize($settings['hero_form_badge'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="hero_form_title">Form Title</label>
                    <input type="text" id="hero_form_title" name="hero_form_title" value="<?= sanitize($settings['hero_form_title'] ?? '') ?>">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="hero_form_subtitle">Form Subtitle</label>
                    <input type="text" id="hero_form_subtitle" name="hero_form_subtitle" value="<?= sanitize($settings['hero_form_subtitle'] ?? '') ?>">
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>About Section</h3>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="about_badge">Section Badge</label>
                    <input type="text" id="about_badge" name="about_badge" value="<?= sanitize($settings['about_badge'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="about_title_accent">Title Accent</label>
                    <input type="text" id="about_title_accent" name="about_title_accent" value="<?= sanitize($settings['about_title_accent'] ?? '') ?>">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="about_title_sub">Title Subline</label>
                    <input type="text" id="about_title_sub" name="about_title_sub" value="<?= sanitize($settings['about_title_sub'] ?? '') ?>">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="about_lead">Lead Paragraph</label>
                    <textarea id="about_lead" name="about_lead" rows="2"><?= sanitize($settings['about_lead'] ?? '') ?></textarea>
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="about_desc">Description</label>
                    <textarea id="about_desc" name="about_desc" rows="3"><?= sanitize($settings['about_desc'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>Contact & WhatsApp</h3>
            </div>

            <div class="settings-form-grid">
                <div class="form-group settings-form-grid-full">
                    <label for="business_hours">Business Hours</label>
                    <input type="text" id="business_hours" name="business_hours" value="<?= sanitize($settings['business_hours'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="site_address">Address Line 1</label>
                    <input type="text" id="site_address" name="site_address" value="<?= sanitize($settings['site_address'] ?? '') ?>" placeholder="123 Tech Park, Cyber City">
                </div>
                <div class="form-group">
                    <label for="site_address_line2">Address Line 2</label>
                    <input type="text" id="site_address_line2" name="site_address_line2" value="<?= sanitize($settings['site_address_line2'] ?? '') ?>" placeholder="New Delhi, India">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label class="checkbox-label settings-checkbox-label">
                        <input type="checkbox" name="site_location_enabled" value="1" <?= ($settings['site_location_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <span>Show location on contact section</span>
                    </label>
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="whatsapp_default_message">WhatsApp Default Message</label>
                    <input type="text" id="whatsapp_default_message" name="whatsapp_default_message" value="<?= sanitize($settings['whatsapp_default_message'] ?? '') ?>">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label class="checkbox-label settings-checkbox-label">
                        <input type="checkbox" name="follow_up_email_reminder" value="1" <?= ($settings['follow_up_email_reminder'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span>Send daily follow-up reminder email to notification inbox</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Homepage Content</button>
            </div>
        </form>
    </div>
</div>
