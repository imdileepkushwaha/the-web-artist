<div class="panel settings-card">
    <?php
    $panelTitle = 'Site & Notifications';
    $panelMeta = 'Public contact details, email alerts and session';
    $panelIconSvg = panelIconSvg('site');
    $panelIconColor = 'blue';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <?php
        $siteLogoPath = trim($settings['site_logo'] ?? 'images/twa-logo.png');
        if ($siteLogoPath === '') {
            $siteLogoPath = 'images/twa-logo.png';
        }
        $siteLogoPreview = preg_match('#^https?://#i', $siteLogoPath) ? $siteLogoPath : '../' . ltrim(str_replace('\\', '/', $siteLogoPath), '/');
        ?>
        <form method="POST" class="admin-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="site">

            <div class="settings-section-divider settings-section-divider-first">
                <h3>Website Logo</h3>
                <p>Logo shown in the website navigation bar.</p>
            </div>

            <div class="settings-logo-block">
                <div class="settings-logo-preview">
                    <img src="<?= sanitize($siteLogoPreview) ?>" alt="Current website logo" id="siteLogoPreview">
                </div>
                <div class="settings-logo-fields">
                    <div class="form-group">
                        <label for="site_logo_file">Upload New Logo</label>
                        <input type="file" id="site_logo_file" name="site_logo_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
                        <span class="form-hint">PNG, JPG, WEBP, GIF or SVG. Max 2 MB.</span>
                    </div>
                    <div class="form-group">
                        <label for="site_logo">Or Logo Path / URL</label>
                        <input type="text" id="site_logo" name="site_logo" value="<?= sanitize($siteLogoPath) ?>" placeholder="images/twa-logo.png">
                        <span class="form-hint">Relative path (e.g. images/logo.png) or full URL.</span>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label settings-checkbox-label">
                            <input type="checkbox" name="reset_site_logo" value="1">
                            <span>Reset to default logo</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>Website Contact</h3>
                <p>Details shown on the public website.</p>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="site_email">Contact Email (Website)</label>
                    <input type="email" id="site_email" name="site_email" value="<?= sanitize($settings['site_email'] ?? ($settings['notify_email'] ?? '')) ?>" placeholder="hello@thewebartist.com">
                    <span class="form-hint">Shown on the public website contact section.</span>
                </div>
                <div class="form-group">
                    <label for="site_phone">Site Phone (Website)</label>
                    <input type="text" id="site_phone" name="site_phone" value="<?= sanitize($settings['site_phone'] ?? '') ?>" placeholder="+91 9876543210">
                    <span class="form-hint">Shown on the website and used for WhatsApp links.</span>
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>Email Notifications</h3>
                <p>Sender and inbox for enquiry alert emails.</p>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="admin_email">Sender Email (From)</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?= sanitize($settings['admin_email'] ?? '') ?>" placeholder="noreply@thewebartist.com">
                    <span class="form-hint">Used as the sender address for system emails.</span>
                </div>
                <div class="form-group">
                    <label for="email_from_name">Sender Name</label>
                    <input type="text" id="email_from_name" name="email_from_name" value="<?= sanitize($settings['email_from_name'] ?? 'The Web Artist') ?>" placeholder="The Web Artist">
                </div>
                <div class="form-group">
                    <label for="notify_email">Notification Email</label>
                    <input type="email" id="notify_email" name="notify_email" value="<?= sanitize($settings['notify_email'] ?? '') ?>" placeholder="admin@thewebartist.com">
                    <span class="form-hint">Receives alerts when a new enquiry is submitted.</span>
                </div>
                <div class="form-group">
                    <label class="checkbox-label settings-checkbox-label">
                        <input type="checkbox" name="notify_enquiries_enabled" value="1" <?= ($settings['notify_enquiries_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span>Send email when a new enquiry is submitted</span>
                    </label>
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>Admin Session</h3>
            </div>

            <?php
            $sessionTimeoutValue = (int) ($settings['session_timeout_minutes'] ?? 30);
            require __DIR__ . '/session-timeout-field.php';
            ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
