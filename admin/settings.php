<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$settings = getAllSettings($conn);

$allowedTabs = ['site', 'password', 'email', 'templates'];
$activeTab = $_GET['tab'] ?? 'site';
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'site';
}

function settingsRedirect(string $tab, array $extra = []): void
{
    $params = array_merge(['tab' => $tab], $extra);
    header('Location: settings.php?' . http_build_query($params));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'site';

    if ($action === 'password') {
        $current = trim($_POST['current_password'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $account = resolveCurrentAdminUser($conn);

        if ($current === '') {
            flashMessage('error', 'Please enter your current password.');
        } elseif ($newPass === '' || strlen($newPass) < 6) {
            flashMessage('error', 'New password must be at least 6 characters.');
        } elseif ($newPass !== $confirm) {
            flashMessage('error', 'Passwords do not match.');
        } elseif (!$account) {
            flashMessage('error', 'Unable to find your admin account.');
        } elseif (!verifyAdminCurrentPassword($account, $current)) {
            flashMessage('error', 'Current password is incorrect.');
        } elseif (updateAdminUserPassword($conn, (int) $account['id'], $newPass)) {
            $_SESSION[ADMIN_SESSION_USER_ID] = (int) $account['id'];
            logActivity($conn, 'password_change', 'user', (int) $account['id']);
            flashMessage('success', 'Password updated successfully.');
        } else {
            flashMessage('error', 'Unable to update password.');
        }

        settingsRedirect('password');
    }

    if ($action === 'email') {
        setSetting($conn, 'smtp_enabled', isset($_POST['smtp_enabled']) ? '1' : '0');
        setSetting($conn, 'smtp_host', trim($_POST['smtp_host'] ?? ''));
        setSetting($conn, 'smtp_port', (string) max(1, (int) ($_POST['smtp_port'] ?? 587)));
        setSetting($conn, 'smtp_encryption', trim($_POST['smtp_encryption'] ?? 'tls'));
        setSetting($conn, 'smtp_username', trim($_POST['smtp_username'] ?? ''));

        $smtpPassword = trim($_POST['smtp_password'] ?? '');
        if ($smtpPassword !== '') {
            setSetting($conn, 'smtp_password', $smtpPassword);
        }

        logActivity($conn, 'settings_update', 'settings', null, 'SMTP settings updated');
        flashMessage('success', 'SMTP settings saved successfully.');
        settingsRedirect('email');
    }

    if ($action === 'template_save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');

        if ($name === '' || $subject === '' || $body === '') {
            flashMessage('error', 'All template fields are required.');
        } elseif ($id && updateEmailTemplate($conn, $id, $name, $subject, $body)) {
            flashMessage('success', 'Template updated.');
            settingsRedirect('templates');
        } elseif (!$id && createEmailTemplate($conn, $name, $subject, $body)) {
            flashMessage('success', 'Template created.');
            settingsRedirect('templates');
        } else {
            flashMessage('error', 'Unable to save template.');
            settingsRedirect('templates', $id ? ['edit_template' => $id] : []);
        }
    }

    if ($action === 'template_delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && deleteEmailTemplate($conn, $id)) {
            flashMessage('success', 'Template deleted.');
        } else {
            flashMessage('error', 'Unable to delete template.');
        }
        settingsRedirect('templates');
    }

    $notifyEmail = trim($_POST['notify_email'] ?? '');
    $siteEmail = trim($_POST['site_email'] ?? '');
    $sitePhone = trim($_POST['site_phone'] ?? '');

    if ($siteEmail === '' && $notifyEmail !== '') {
        $siteEmail = $notifyEmail;
    }

    setSetting($conn, 'admin_email', trim($_POST['admin_email'] ?? ''));
    setSetting($conn, 'email_from_name', trim($_POST['email_from_name'] ?? ''));
    setSetting($conn, 'notify_email', $notifyEmail);
    setSetting($conn, 'notify_enquiries_enabled', isset($_POST['notify_enquiries_enabled']) ? '1' : '0');
    setSetting($conn, 'site_email', $siteEmail);
    setSetting($conn, 'site_phone', $sitePhone);
    setSetting($conn, 'session_timeout_minutes', (string) max(5, (int) ($_POST['session_timeout_minutes'] ?? 30)));

    logActivity($conn, 'settings_update', 'settings', null, 'Site settings updated');
    flashMessage('success', 'Settings saved successfully.');
    settingsRedirect('site');
}

$settings = getAllSettings($conn);
$emailTemplates = getEmailTemplates($conn);
$editTemplateId = (int) ($_GET['edit_template'] ?? 0);
$editTemplate = $editTemplateId ? getEmailTemplateById($conn, $editTemplateId) : null;

if ($editTemplateId) {
    $activeTab = 'templates';
}

$pageTitle = 'Settings';
$activePage = 'settings.php';
require __DIR__ . '/includes/header.php';

$settingsTabs = [
    'site' => [
        'label' => 'Site & Notifications',
        'desc' => 'Contact details & alerts',
        'icon' => panelIconSvg('site'),
    ],
    'password' => [
        'label' => 'Change Password',
        'desc' => 'Account security',
        'icon' => panelIconSvg('password'),
    ],
    'email' => [
        'label' => 'Email Setup',
        'desc' => 'SMTP server settings',
        'icon' => panelIconSvg('email'),
    ],
    'templates' => [
        'label' => 'Templates',
        'desc' => 'Quick reply emails',
        'icon' => panelIconSvg('templates'),
    ],
];
?>

<div class="settings-tabs-layout">
    <aside class="settings-tab-nav" aria-label="Settings sections">
        <?php foreach ($settingsTabs as $tabKey => $tab): ?>
            <a href="settings.php?tab=<?= urlencode($tabKey) ?>"
               class="settings-tab-link <?= $activeTab === $tabKey ? 'is-active' : '' ?>"
               data-settings-tab="<?= sanitize($tabKey) ?>">
                <span class="settings-tab-icon"><?= $tab['icon'] ?></span>
                <span class="settings-tab-text">
                    <strong><?= sanitize($tab['label']) ?></strong>
                    <span><?= sanitize($tab['desc']) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </aside>

    <div class="settings-tab-panels">
        <div class="settings-tab-panel <?= $activeTab === 'site' ? 'is-active' : '' ?>" data-settings-panel="site">
            <?php require __DIR__ . '/includes/settings-tab-site.php'; ?>
        </div>

        <div class="settings-tab-panel <?= $activeTab === 'password' ? 'is-active' : '' ?>" data-settings-panel="password">
            <?php require __DIR__ . '/includes/settings-tab-password.php'; ?>
        </div>

        <div class="settings-tab-panel <?= $activeTab === 'email' ? 'is-active' : '' ?>" data-settings-panel="email">
            <?php require __DIR__ . '/includes/settings-tab-email.php'; ?>
        </div>

        <div class="settings-tab-panel <?= $activeTab === 'templates' ? 'is-active' : '' ?>" data-settings-panel="templates">
            <?php require __DIR__ . '/includes/settings-tab-templates.php'; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
