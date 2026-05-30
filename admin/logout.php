<?php
require_once __DIR__ . '/includes/auth.php';

if (isAdminLoggedIn()) {
    $conn = getAdminDb();
    logActivity($conn, 'logout', 'user', adminUserId());
}

logoutAdmin();
header('Location: login.php');
exit;
