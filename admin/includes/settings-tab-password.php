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
        <form method="POST" class="admin-form" id="password-change-form" action="settings.php?tab=password">
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
