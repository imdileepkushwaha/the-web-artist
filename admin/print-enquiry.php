<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$conn = getAdminDb();
$id = (int) ($_GET['id'] ?? 0);
$enquiry = getEnquiryById($conn, $id);

if (!$enquiry) {
    flashMessage('error', 'Enquiry not found.');
    header('Location: enquiries.php');
    exit;
}

$notes = getEnquiryNotes($conn, $id);
$assignedUser = !empty($enquiry['assigned_to']) ? getAdminUserById($conn, (int) $enquiry['assigned_to']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enquiry #<?= $id ?> | Print</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        body { background: #fff; padding: 40px; }
        .print-header { display: flex; justify-content: space-between; margin-bottom: 32px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; }
        .print-actions { margin-bottom: 24px; }
        @media print { .print-actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()" class="btn btn-primary btn-sm">Print</button>
        <a href="enquiry.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <div class="print-header">
        <div>
            <h1>Enquiry #<?= $id ?></h1>
            <p>The Web Artist — Customer Enquiry</p>
        </div>
        <div style="text-align:right;">
            <p>Printed: <?= date('d M Y, h:i A') ?></p>
            <p>By: <?= sanitize(adminDisplayName()) ?></p>
        </div>
    </div>

    <div class="detail-grid" style="grid-template-columns:1fr;">
        <div class="detail-card">
            <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value"><?= sanitize(enquiryStatuses()[$enquiry['status']]) ?></div></div>
            <div class="detail-row"><div class="detail-label">Source</div><div class="detail-value"><?= sanitize(enquirySources()[$enquiry['source'] ?? 'contact'] ?? 'Contact') ?></div></div>
            <div class="detail-row"><div class="detail-label">Name</div><div class="detail-value"><?= sanitize($enquiry['name']) ?></div></div>
            <div class="detail-row"><div class="detail-label">Email</div><div class="detail-value"><?= sanitize($enquiry['email']) ?></div></div>
            <div class="detail-row"><div class="detail-label">Phone</div><div class="detail-value"><?= sanitize($enquiry['phone']) ?></div></div>
            <div class="detail-row"><div class="detail-label">Service</div><div class="detail-value"><?= sanitize($enquiry['service']) ?></div></div>
            <?php if ($assignedUser): ?>
                <div class="detail-row"><div class="detail-label">Assigned To</div><div class="detail-value"><?= sanitize($assignedUser['name'] ?: $assignedUser['username']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($enquiry['follow_up_date'])): ?>
                <div class="detail-row"><div class="detail-label">Follow Up</div><div class="detail-value"><?= sanitize($enquiry['follow_up_date']) ?></div></div>
            <?php endif; ?>
            <div class="detail-row"><div class="detail-label">Submitted</div><div class="detail-value"><?= sanitize(formatEnquiryDate($enquiry['created_at'])) ?></div></div>
            <div class="detail-row"><div class="detail-label">Message</div><div class="detail-value"><div class="message-box"><?= sanitize($enquiry['message'] ?: '—') ?></div></div></div>
        </div>

        <?php if (!empty($notes)): ?>
            <div class="detail-card">
                <h2>Admin Notes</h2>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-meta"><?= sanitize($note['created_by']) ?> · <?= sanitize(formatEnquiryDate($note['created_at'])) ?></div>
                        <div class="note-text"><?= sanitize($note['note']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
