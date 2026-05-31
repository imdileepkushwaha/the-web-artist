<div class="panel settings-card">
    <?php
    $panelTitle = 'Change Password';
    $panelMeta = 'Update your admin account password';
    $panelIconSvg = panelIconSvg('password');
    $panelIconColor = 'purple';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <?php if (isset($_GET['required'])): ?>
            <div class="alert alert-warning" style="margin-bottom:16px;">You must change your default password before using the admin panel.</div>
        <?php endif; ?>
        <form method="POST" class="admin-form" id="password-change-form" action="<?= adminUrl('settings', ['tab' => 'password']) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="password">
            <?php
            unset($passwordFieldId, $passwordFieldLabel, $passwordFieldName, $passwordAutocomplete, $passwordRequired, $passwordMinLength);
            $passwordFieldId = 'current_password';
            $passwordFieldLabel = 'Current Password';
            $passwordAutocomplete = 'current-password';
            require __DIR__ . '/password-input-field.php';

            unset($passwordFieldId, $passwordFieldLabel, $passwordFieldName, $passwordAutocomplete, $passwordRequired, $passwordMinLength);
            $passwordFieldId = 'new_password';
            $passwordFieldLabel = 'New Password';
            $passwordAutocomplete = 'new-password';
            $passwordMinLength = 6;
            require __DIR__ . '/password-input-field.php';

            unset($passwordFieldId, $passwordFieldLabel, $passwordFieldName, $passwordAutocomplete, $passwordRequired, $passwordMinLength);
            $passwordFieldId = 'confirm_password';
            $passwordFieldLabel = 'Confirm Password';
            $passwordAutocomplete = 'new-password';
            $passwordMinLength = 6;
            require __DIR__ . '/password-input-field.php';
            unset($passwordFieldId, $passwordFieldLabel, $passwordFieldName, $passwordAutocomplete, $passwordRequired, $passwordMinLength);
            ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>
