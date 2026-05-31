<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$openAddUserModal = isset($_GET['add']);
$openEditUserId = (int) ($_GET['edit'] ?? 0);
$openResetUserId = (int) ($_GET['reset'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'viewer';

        if ($username === '' || $password === '') {
            flashMessage('error', 'Username and password are required.');
            header('Location: users.php?add=1');
            exit;
        }

        if (mb_strlen($password, 'UTF-8') < 6) {
            flashMessage('error', 'Password must be at least 6 characters.');
            header('Location: users.php?add=1');
            exit;
        }

        if (createAdminUser($conn, $username, $password, $name, $email ?: null, $role)) {
            logActivity($conn, 'user_create', 'user', null, "Created user: {$username}");
            flashMessage('success', 'User created successfully.');
        } else {
            flashMessage('error', 'Unable to create user. Username may already exist.');
            header('Location: users.php?add=1');
            exit;
        }
    }

    if ($action === 'update' && ($userId = (int) ($_POST['user_id'] ?? 0))) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'viewer';

        if ($userId === adminUserId() && $role !== 'admin') {
            flashMessage('error', 'You cannot change your own role from admin.');
            header('Location: users.php?edit=' . $userId);
            exit;
        }

        if (updateAdminUser($conn, $userId, $name, $email ?: null, $role)) {
            logActivity($conn, 'user_update', 'user', $userId);
            flashMessage('success', 'User updated successfully.');
        } else {
            flashMessage('error', 'Unable to update user.');
            header('Location: users.php?edit=' . $userId);
            exit;
        }
    }

    if ($action === 'reset_password' && ($userId = (int) ($_POST['user_id'] ?? 0))) {
        $password = (string) ($_POST['password'] ?? '');

        if (mb_strlen($password, 'UTF-8') < 6) {
            flashMessage('error', 'Password must be at least 6 characters.');
            header('Location: users.php?reset=' . $userId);
            exit;
        }

        if (updateAdminUserPassword($conn, $userId, $password)) {
            logActivity($conn, 'user_password_reset', 'user', $userId);
            flashMessage('success', 'Password reset successfully.');
        } else {
            flashMessage('error', 'Unable to reset password.');
            header('Location: users.php?reset=' . $userId);
            exit;
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
$editUser = $openEditUserId ? getAdminUserById($conn, $openEditUserId) : null;
$resetUser = $openResetUserId ? getAdminUserById($conn, $openResetUserId) : null;
$pageTitle = 'Users';
$activePage = 'users.php';
require __DIR__ . '/includes/header.php';
?>

<div class="users-page">
    <div class="panel users-panel">
        <div class="panel-header panel-header-with-search">
            <div class="panel-header-left">
                <div class="panel-header-icon">
                    <div class="panel-icon blue"><?= panelIconSvg('users') ?></div>
                    <div>
                        <h2>Admin Users</h2>
                        <p class="panel-meta"><?= count($users) ?> team member<?= count($users) === 1 ? '' : 's' ?></p>
                    </div>
                </div>
            </div>
            <div class="panel-header-actions">
                <button type="button" class="btn btn-primary btn-add-user" data-modal-open="addUserModal" aria-haspopup="dialog">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add User
                </button>
            </div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="data-table users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state compact">No users found. Click <strong>Add User</strong> to create one.</div>
                                </td>
                            </tr>
                        <?php else: ?>
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
                                    <td><?= sanitize(formatEnquiryDate($user['created_at'] ?? null)) ?></td>
                                    <td>
                                        <div class="users-action-group">
                                            <a href="users.php?edit=<?= (int) $user['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="users.php?reset=<?= (int) $user['id'] ?>" class="btn btn-secondary btn-sm">Reset Password</a>
                                            <?php if ((int) $user['id'] !== adminUserId()): ?>
                                                <form method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm"><?= $user['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                                </form>
                                            <?php else: ?>
                                                <span class="table-sub">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="admin-modal" id="addUserModal" hidden aria-hidden="true">
    <div class="admin-modal-backdrop" data-modal-close></div>
    <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
        <div class="admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon green"><?= panelIconSvg('user-add') ?></div>
                <div>
                    <h2 id="addUserModalTitle">Add User</h2>
                    <p>Create a new admin or viewer account</p>
                </div>
            </div>
            <button type="button" class="admin-modal-close" data-modal-close aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="admin-modal-body">
            <form method="POST" class="admin-form" id="addUserForm">
                <input type="hidden" name="action" value="create">
                <div class="users-form-grid">
                    <div class="form-group">
                        <label for="add_username">Username</label>
                        <input type="text" id="add_username" name="username" required autocomplete="off" placeholder="e.g. john">
                    </div>
                    <div class="form-group">
                        <label for="add_name">Full Name</label>
                        <input type="text" id="add_name" name="name" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label for="add_email">Email</label>
                        <input type="email" id="add_email" name="email" placeholder="john@example.com">
                    </div>
                    <div class="form-group">
                        <label for="add_role">Role</label>
                        <select id="add_role" name="role">
                            <option value="admin">Admin — full access</option>
                            <option value="viewer">Viewer — read only</option>
                        </select>
                    </div>
                    <div class="users-form-grid-full">
                        <?php
                        $passwordFieldId = 'add_password';
                        $passwordFieldName = 'password';
                        $passwordFieldLabel = 'Password';
                        $passwordAutocomplete = 'new-password';
                        $passwordMinLength = 6;
                        require __DIR__ . '/includes/password-input-field.php';
                        ?>
                    </div>
                </div>
                <div class="form-actions admin-modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editUser): ?>
<div class="admin-modal is-open" id="editUserModal" aria-hidden="false">
    <div class="admin-modal-backdrop" data-modal-close></div>
    <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="editUserModalTitle">
        <div class="admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon blue"><?= panelIconSvg('users') ?></div>
                <div>
                    <h2 id="editUserModalTitle">Edit User</h2>
                    <p><?= sanitize($editUser['username']) ?></p>
                </div>
            </div>
            <a href="users.php" class="admin-modal-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </a>
        </div>
        <div class="admin-modal-body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                <div class="users-form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?= sanitize($editUser['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Full Name</label>
                        <input type="text" id="edit_name" name="name" value="<?= sanitize($editUser['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" value="<?= sanitize($editUser['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" <?= (int) $editUser['id'] === adminUserId() ? 'disabled' : '' ?>>
                            <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="viewer" <?= $editUser['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                        </select>
                        <?php if ((int) $editUser['id'] === adminUserId()): ?>
                            <input type="hidden" name="role" value="admin">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions admin-modal-actions">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>document.body.classList.add('modal-open');</script>
<?php endif; ?>

<?php if ($resetUser): ?>
<div class="admin-modal is-open" id="resetUserModal" aria-hidden="false">
    <div class="admin-modal-backdrop" data-modal-close></div>
    <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="resetUserModalTitle">
        <div class="admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon green"><?= panelIconSvg('password') ?></div>
                <div>
                    <h2 id="resetUserModalTitle">Reset Password</h2>
                    <p><?= sanitize($resetUser['name'] ?: $resetUser['username']) ?></p>
                </div>
            </div>
            <a href="users.php" class="admin-modal-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </a>
        </div>
        <div class="admin-modal-body">
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?= (int) $resetUser['id'] ?>">
                <?php
                $passwordFieldId = 'reset_password';
                $passwordFieldName = 'password';
                $passwordFieldLabel = 'New Password';
                $passwordAutocomplete = 'new-password';
                $passwordMinLength = 6;
                require __DIR__ . '/includes/password-input-field.php';
                ?>
                <div class="form-actions admin-modal-actions">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>document.body.classList.add('modal-open');</script>
<?php endif; ?>

<?php if ($openAddUserModal): ?>
<script>document.body.dataset.openModal = 'addUserModal';</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
