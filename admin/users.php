<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'viewer';

        if ($username === '' || $password === '') {
            flashMessage('error', 'Username and password are required.');
        } elseif (createAdminUser($conn, $username, $password, $name, $email ?: null, $role)) {
            logActivity($conn, 'user_create', 'user', null, "Created user: {$username}");
            flashMessage('success', 'User created successfully.');
        } else {
            flashMessage('error', 'Unable to create user. Username may already exist.');
        }
    }

    if ($action === 'toggle' && ($userId = (int) ($_POST['user_id'] ?? 0))) {
        if ($userId === adminUserId()) {
            flashMessage('error', 'You cannot deactivate your own account.');
        } else {
            $conn->prepare('UPDATE admin_users SET is_active = NOT is_active WHERE id = :id')->execute([':id' => $userId]);
            logActivity($conn, 'user_toggle', 'user', $userId);
            flashMessage('success', 'User status updated.');
        }
    }

    header('Location: users.php');
    exit;
}

$users = getAdminUsers($conn);
$pageTitle = 'Users';
$activePage = 'users.php';
require __DIR__ . '/includes/header.php';
?>

<div class="cms-grid">
    <div class="panel cms-list-panel">
        <?php
        $panelTitle = 'Admin Users';
        $panelMeta = count($users) . ' team member' . (count($users) === 1 ? '' : 's');
        $panelIconSvg = panelIconSvg('users');
        $panelIconColor = 'blue';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-cell-avatar"><?= strtoupper(substr($user['name'] ?: $user['username'], 0, 2)) ?></div>
                                        <div>
                                            <strong><?= sanitize($user['name'] ?: $user['username']) ?></strong>
                                            <div class="table-sub"><?= sanitize($user['username']) ?><?= $user['email'] ? ' · ' . sanitize($user['email']) : '' ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-viewer' ?>"><?= sanitize(ucfirst($user['role'])) ?></span></td>
                                <td><?= $user['is_active'] ? '<span class="badge badge-contacted">Active</span>' : '<span class="badge badge-closed">Inactive</span>' ?></td>
                                <td><?= sanitize(formatEnquiryDate($user['last_login'])) ?></td>
                                <td>
                                    <?php if ((int) $user['id'] !== adminUserId()): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm"><?= $user['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="table-sub">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel cms-form-panel">
        <?php
        $panelTitle = 'Add User';
        $panelMeta = 'Create a new admin or viewer account';
        $panelIconSvg = panelIconSvg('user-add');
        $panelIconColor = 'green';
        $panelAccent = true;
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="admin">Admin — full access</option>
                        <option value="viewer">Viewer — read only</option>
                    </select>
                    <span class="form-hint">Viewers cannot edit content or change settings.</span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
