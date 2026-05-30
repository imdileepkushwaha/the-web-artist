<?php

http_response_code(404);
require_once __DIR__ . '/includes/site-config.php';

$pageTitle = 'Page Not Found | ' . SITE_NAME;
$pageDescription = 'The page you are looking for could not be found. Return to The Web Artist homepage.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderSeoMeta($pageTitle, $pageDescription, '404.php'); ?>
    <meta name="robots" content="noindex, follow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="utility-page">
    <div class="utility-wrap">
        <div class="utility-card animate is-visible">
            <div class="utility-icon error">404</div>
            <h1>Page Not Found</h1>
            <p>Sorry, the page you are looking for doesn't exist or has been moved.</p>
            <div class="utility-actions">
                <a href="index.php" class="btn-primary">Go to Homepage</a>
                <a href="index.php#contact" class="btn-outline">Contact Us</a>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/includes/whatsapp-float.php'; ?>
</body>
</html>
