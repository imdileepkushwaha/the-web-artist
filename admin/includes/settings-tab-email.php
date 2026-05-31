<div class="panel settings-card email-setup-panel">
    <?php
    $panelTitle = 'SMTP Configuration';
    $panelMeta = 'Outgoing mail server for enquiry alerts and system emails';
    $panelIconSvg = panelIconSvg('email');
    $panelIconColor = 'blue';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <form method="POST" class="admin-form" id="email-settings-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="email">
            <p class="settings-section-intro">Configure SMTP for reliable email delivery on live servers (Gmail, hosting mail, SendGrid, etc.). When disabled, PHP <code>mail()</code> is used.</p>

            <div class="form-group">
                <label class="checkbox-label settings-checkbox-label">
                    <input type="checkbox" name="smtp_enabled" value="1" id="smtp_enabled" <?= ($settings['smtp_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <span>Enable SMTP</span>
                </label>
            </div>

            <div class="smtp-fields <?= ($settings['smtp_enabled'] ?? '0') === '1' ? 'is-visible' : '' ?>" id="smtp-fields">
                <div class="settings-form-grid">
                    <div class="form-group">
                        <label for="smtp_host">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?= sanitize($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label for="smtp_port">SMTP Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" min="1" max="65535" value="<?= sanitize($settings['smtp_port'] ?? '587') ?>" placeholder="587">
                    </div>
                    <div class="form-group">
                        <label for="smtp_encryption">Encryption</label>
                        <select id="smtp_encryption" name="smtp_encryption">
                            <?php foreach (['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label): ?>
                                <option value="<?= sanitize($value) ?>" <?= ($settings['smtp_encryption'] ?? 'tls') === $value ? 'selected' : '' ?>><?= sanitize($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="smtp_username">SMTP Username</label>
                        <input type="text" id="smtp_username" name="smtp_username" value="<?= sanitize($settings['smtp_username'] ?? '') ?>" placeholder="your-email@gmail.com" autocomplete="off">
                    </div>
                    <div class="form-group settings-form-grid-full">
                        <label for="smtp_password">SMTP Password</label>
                        <div class="password-input-wrap">
                            <input type="password" id="smtp_password" name="smtp_password" class="password-input" placeholder="<?= ($settings['smtp_password'] ?? '') !== '' ? '•••••••• (leave blank to keep)' : 'App password or SMTP password' ?>" autocomplete="new-password">
                            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <span class="form-hint">Leave blank to keep the current password.</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save SMTP Settings</button>
            </div>
        </form>

        <form method="POST" class="admin-form" style="margin-top:20px;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="test_email">
            <div class="settings-section-divider"><h3>Send Test Email</h3><p>Uses current SMTP / mail settings to send a test to your notification email.</p></div>
            <button type="submit" class="btn btn-secondary">Send Test Email</button>
        </form>
    </div>
</div>
