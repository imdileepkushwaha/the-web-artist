<?php

require_once __DIR__ . '/../config/database.php';

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // Change before production — fallback disabled on server
define('TWA_ALLOW_CONFIG_LOGIN', twaDetectAppEnvironment() === 'local');
define('ADMIN_SESSION_KEY', 'twa_admin_logged_in');
define('ADMIN_SESSION_USER', 'twa_admin_user');
define('ADMIN_SESSION_USER_ID', 'twa_admin_user_id');
define('ADMIN_SESSION_ROLE', 'twa_admin_role');
define('ENQUIRIES_PER_PAGE', 10);

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/includes/url.php';
require_once __DIR__ . '/includes/helpers.php';
