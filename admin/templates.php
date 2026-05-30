<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
header('Location: settings.php?tab=templates' . (isset($_GET['edit']) ? '&edit_template=' . (int) $_GET['edit'] : ''));
exit;
