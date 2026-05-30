<?php

require_once __DIR__ . '/includes/site-config.php';

$pageTitle = 'Thank You | ' . SITE_NAME;
$pageDescription = 'Your enquiry has been submitted successfully. The Web Artist team will contact you shortly.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderSeoMeta($pageTitle, $pageDescription, 'thank-you.php'); ?>
    <meta name="robots" content="noindex, follow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="utility-page">
    <div class="utility-wrap">
        <div class="utility-card animate is-visible">
            <div class="utility-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h1>Thank You!</h1>
            <p>Your request has been successfully submitted. Our team will review it and get back to you within 24 hours.</p>
            <div class="utility-actions">
                <a href="index.php" class="btn-primary">Back to Home</a>
                <a href="https://wa.me/<?= SITE_PHONE_RAW ?>?text=Hi%2C%20I%20just%20submitted%20an%20enquiry." class="btn-outline utility-wa" target="_blank" rel="noopener">Chat on WhatsApp</a>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/includes/whatsapp-float.php'; ?>
</body>
</html>
