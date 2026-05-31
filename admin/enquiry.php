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

if ($enquiry['status'] === 'new') {
    markEnquiryAsRead($conn, $id);
    $enquiry = getEnquiryById($conn, $id);
}

$adminUsers = getAdminUsers($conn, true);
$emailTemplates = getEmailTemplates($conn);
$assignedUser = !empty($enquiry['assigned_to']) ? getAdminUserById($conn, (int) $enquiry['assigned_to']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isViewer()) {
    verifyCsrfToken();
    $action = $_POST['action'] ?? 'update';

    if ($action === 'delete') {
        deleteEnquiry($conn, $id);
        logActivity($conn, 'enquiry_delete', 'enquiry', $id);
        flashMessage('success', 'Enquiry deleted successfully.');
        header('Location: enquiries.php');
        exit;
    }

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');

        if ($note === '') {
            flashMessage('error', 'Please enter a note before submitting.');
        } elseif (addEnquiryNote($conn, $id, $note, adminDisplayName())) {
            logActivity($conn, 'note_add', 'enquiry', $id);
            flashMessage('success', 'Note added successfully.');
        } else {
            flashMessage('error', 'Unable to add note.');
        }

        header('Location: enquiry.php?id=' . $id);
        exit;
    }

    if ($action === 'send_email') {
        $subject = trim($_POST['email_subject'] ?? '');
        $body = trim($_POST['email_body'] ?? '');

        if ($subject === '' || $body === '') {
            flashMessage('error', 'Subject and message are required.');
            header('Location: enquiry.php?id=' . $id . '&email=1');
            exit;
        }

        $result = sendSystemEmail($enquiry['email'], $subject, $body, getSetting($conn, 'admin_email'));

        if ($result['success']) {
            addEnquiryNote($conn, $id, 'Email sent: ' . $subject, adminDisplayName());
            logActivity($conn, 'email_send', 'enquiry', $id, $subject);
            flashMessage('success', 'Email sent successfully.');
        } else {
            flashMessage('error', $result['message'] ?? 'Unable to send email.');
            header('Location: enquiry.php?id=' . $id . '&email=1');
            exit;
        }

        header('Location: enquiry.php?id=' . $id);
        exit;
    }

    if ($action === 'update') {
        $status = $_POST['status'] ?? $enquiry['status'];
        $assignedTo = ($_POST['assigned_to'] ?? '') !== '' ? (int) $_POST['assigned_to'] : null;
        $followUpDate = trim($_POST['follow_up_date'] ?? '');
        $clearFollowUp = empty($followUpDate);

        if (updateEnquiryExtended($conn, $id, $status, $assignedTo, $clearFollowUp ? null : $followUpDate, $assignedTo === null && ($_POST['assigned_to'] ?? '') === '', $clearFollowUp)) {
            logActivity($conn, 'enquiry_update', 'enquiry', $id, "Status: {$status}");
            flashMessage('success', 'Enquiry updated successfully.');
            header('Location: enquiry.php?id=' . $id);
            exit;
        }

        flashMessage('error', 'Unable to update enquiry.');
        header('Location: enquiry.php?id=' . $id);
        exit;
    }
}

$notes = getEnquiryNotes($conn, $id);
$statusClass = statusBadgeClass($enquiry['status']);
$heroStatusClass = 'enquiry-hero--' . sanitize($enquiry['status']);
$sitePhone = getSetting($conn, 'site_phone', '+91 98765 43210');
$whatsappUrl = buildWhatsAppUrl($enquiry['phone'] ?: $sitePhone, applyMessageTemplate(getSetting($conn, 'whatsapp_default_message', 'Hi'), $enquiry, adminDisplayName()));
$whatsappTemplates = getWhatsAppQuickTemplates();
$openEmailModal = isset($_GET['email']);

$pageTitle = 'Enquiry #' . $id;
$activePage = 'enquiry.php';
require __DIR__ . '/includes/header.php';
?>

<div class="enquiry-page">
    <div class="enquiry-hero <?= $heroStatusClass ?>">
        <div class="enquiry-hero-bg"></div>
        <div class="enquiry-hero-content">
            <a href="enquiries.php" class="enquiry-back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Enquiries
            </a>

            <div class="enquiry-hero-main">
                <div class="enquiry-hero-avatar"><?= sanitize(getInitials($enquiry['name'])) ?></div>
                <div class="enquiry-hero-text">
                    <div class="enquiry-hero-title-row">
                        <h1><?= sanitize($enquiry['name']) ?></h1>
                        <span class="enquiry-id-badge">#<?= $id ?></span>
                    </div>
                    <p class="enquiry-hero-service"><?= sanitize($enquiry['service']) ?></p>
                    <div class="enquiry-hero-badges">
                        <span class="badge badge-dot <?= $statusClass ?>"><?= sanitize(enquiryStatuses()[$enquiry['status']]) ?></span>
                        <span class="badge <?= sourceBadgeClass($enquiry['source'] ?? 'contact') ?>"><?= sanitize(enquirySources()[$enquiry['source'] ?? 'contact'] ?? 'Contact') ?></span>
                        <span class="enquiry-hero-date">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= sanitize(formatEnquiryDate($enquiry['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="enquiry-hero-actions">
                <?php if (!isViewer()): ?>
                <button type="button" class="enquiry-action-btn enquiry-action-btn-primary" data-modal-open="sendEmailModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Send Email
                </button>
                <?php endif; ?>
                <a href="<?= sanitize($whatsappUrl) ?>" class="enquiry-action-btn enquiry-action-btn-whatsapp" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
                <a href="tel:<?= sanitize($enquiry['phone']) ?>" class="enquiry-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    Call
                </a>
                <a href="print-enquiry.php?id=<?= $id ?>" class="enquiry-action-btn" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print
                </a>
            </div>
        </div>
    </div>

    <div class="enquiry-detail-grid">
        <div class="enquiry-main">
            <div class="panel enquiry-info-panel">
                <?php
                $panelTitle = 'Contact Information';
                $panelMeta = 'Client details and enquiry metadata';
                $panelIconSvg = panelIconSvg('enquiries');
                $panelIconColor = 'blue';
                require __DIR__ . '/includes/panel-header.php';
                ?>
                <div class="panel-body">
                    <div class="enquiry-info-grid">
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Full Name</span>
                                <strong><?= sanitize($enquiry['name']) ?></strong>
                            </div>
                        </div>
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Email</span>
                                <a href="mailto:<?= sanitize($enquiry['email']) ?>"><?= sanitize($enquiry['email']) ?></a>
                            </div>
                        </div>
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Phone</span>
                                <a href="tel:<?= sanitize($enquiry['phone']) ?>"><?= sanitize($enquiry['phone']) ?></a>
                            </div>
                        </div>
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon enquiry-info-icon-purple">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Service</span>
                                <strong><?= sanitize($enquiry['service']) ?></strong>
                            </div>
                        </div>
                        <?php if ($assignedUser): ?>
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon enquiry-info-icon-green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Assigned To</span>
                                <strong><?= sanitize($assignedUser['name'] ?: $assignedUser['username']) ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($enquiry['follow_up_date'])): ?>
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon enquiry-info-icon-orange">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Follow Up</span>
                                <strong><?= sanitize(date('d M Y', strtotime($enquiry['follow_up_date']))) ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($enquiry['updated_at']) && $enquiry['updated_at'] !== $enquiry['created_at']): ?>
                        <div class="enquiry-info-tile">
                            <div class="enquiry-info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                            </div>
                            <div>
                                <span class="enquiry-info-label">Last Updated</span>
                                <strong><?= sanitize(formatEnquiryDate($enquiry['updated_at'])) ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="panel enquiry-message-panel">
                <?php
                $panelTitle = 'Client Message';
                $panelMeta = 'Original enquiry submission';
                $panelIconSvg = panelIconSvg('testimonials');
                $panelIconColor = 'purple';
                require __DIR__ . '/includes/panel-header.php';
                ?>
                <div class="panel-body">
                    <?php
                    $messageText = trim($enquiry['message'] ?? '');
                    $isEmptyMessage = $messageText === '';
                    ?>
                    <div class="enquiry-message-box<?= $isEmptyMessage ? ' is-empty' : '' ?>">
                        <div class="enquiry-message-quote" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621-.537 2.958 1.369 4.305 2.122 4.841C6.67 16.981 4 18.553 4 20.753V21h8v-1c0-2.663-4.533-4.005-7.417-3.679z"/><path d="M15.583 17.321C14.553 16.227 14 15 14 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621-.537 2.958 1.369 4.305 2.122 4.841C18.67 16.981 16 18.553 16 20.753V21h8v-1c0-2.663-4.533-4.005-7.417-3.679z"/></svg>
                        </div>
                        <div class="enquiry-message-content">
                            <p class="enquiry-message-text"><?= sanitize($isEmptyMessage ? 'No message provided by the client.' : $messageText) ?></p>
                            <?php if (!$isEmptyMessage): ?>
                            <div class="enquiry-message-footer">
                                <span class="enquiry-message-label">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    Client submission
                                </span>
                                <span class="enquiry-message-chars"><?= number_format(mb_strlen($messageText)) ?> characters</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="enquiry-sidebar">
            <div class="panel enquiry-workflow-panel">
                <?php
                $panelTitle = 'Manage Workflow';
                $panelMeta = 'Status, assignment & follow-up';
                $panelIconSvg = panelIconSvg('pipeline');
                $panelIconColor = 'green';
                $panelAccent = true;
                require __DIR__ . '/includes/panel-header.php';
                ?>
                <div class="panel-body">
                    <?php if (!isViewer()): ?>
                    <form method="POST" action="<?= adminUrl('enquiry', ['id' => $id]) ?>" class="admin-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update">

                        <div class="form-group">
                            <label for="status">Update Status</label>
                            <select id="status" name="status" required>
                                <?php foreach (enquiryStatuses() as $value => $label): ?>
                                    <option value="<?= sanitize($value) ?>" <?= $enquiry['status'] === $value ? 'selected' : '' ?>><?= sanitize($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="assigned_to">Assign To</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($adminUsers as $user): ?>
                                    <option value="<?= (int) $user['id'] ?>" <?= (int) ($enquiry['assigned_to'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>><?= sanitize($user['name'] ?: $user['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php
                        $followUpDateValue = $enquiry['follow_up_date'] ?? '';
                        require __DIR__ . '/includes/follow-up-date-field.php';
                        ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="width:100%;">Save Changes</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="enquiry-viewer-notice">You have view-only access. Contact an admin to update this enquiry.</div>
                    <?php endif; ?>

                    <?php if (!empty($emailTemplates)): ?>
                    <div class="enquiry-templates">
                        <h3>Email Templates</h3>
                        <div class="enquiry-template-list">
                            <?php foreach ($emailTemplates as $template): ?>
                                <?php
                                $subject = applyEmailTemplate($template['subject'], $enquiry);
                                $body = applyEmailTemplate($template['body'], $enquiry);
                                ?>
                                <button type="button"
                                        class="enquiry-template-chip enquiry-template-chip-btn"
                                        data-template="<?= htmlspecialchars(json_encode(['subject' => $subject, 'body' => $body], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <?= sanitize($template['name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="enquiry-templates">
                        <h3>WhatsApp Quick Replies</h3>
                        <div class="enquiry-template-list">
                            <?php foreach ($whatsappTemplates as $template): ?>
                                <?php
                                $waMessage = applyMessageTemplate($template['message'], $enquiry, adminDisplayName());
                                $waLink = buildWhatsAppUrl($enquiry['phone'] ?: $sitePhone, $waMessage);
                                ?>
                                <a href="<?= sanitize($waLink) ?>" class="enquiry-template-chip enquiry-template-chip-wa" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                                    <?= sanitize($template['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel enquiry-notes-panel">
                <?php
                $panelTitle = 'Admin Notes';
                $panelMeta = count($notes) . ' note' . (count($notes) === 1 ? '' : 's') . ' on this enquiry';
                $panelIconSvg = panelIconSvg('templates');
                $panelIconColor = 'orange';
                require __DIR__ . '/includes/panel-header.php';
                ?>
                <div class="panel-body">
                    <?php if (empty($notes)): ?>
                        <div class="enquiry-notes-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <p>No notes yet. Add your first follow-up note below.</p>
                        </div>
                    <?php else: ?>
                        <div class="enquiry-notes-list">
                            <?php foreach ($notes as $note): ?>
                                <div class="enquiry-note-item">
                                    <div class="enquiry-note-avatar"><?= strtoupper(substr($note['created_by'], 0, 1)) ?></div>
                                    <div class="enquiry-note-body">
                                        <div class="enquiry-note-meta">
                                            <strong><?= sanitize($note['created_by']) ?></strong>
                                            <time><?= sanitize(formatEnquiryDate($note['created_at'])) ?></time>
                                        </div>
                                        <p><?= sanitize($note['note']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!isViewer()): ?>
                    <form method="POST" action="<?= adminUrl('enquiry', ['id' => $id]) ?>" class="admin-form enquiry-note-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_note">
                        <div class="form-group">
                            <label for="note">Add New Note</label>
                            <textarea id="note" name="note" rows="3" required placeholder="e.g. Called client, sent proposal, waiting for response..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Add Note</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!isViewer()): ?>
            <div class="panel enquiry-danger-panel">
                <div class="panel-body">
                    <div class="enquiry-danger-box">
                        <div>
                            <strong>Delete Enquiry</strong>
                            <p>Permanently remove this enquiry and all related notes.</p>
                        </div>
                        <form method="POST" action="<?= adminUrl('enquiry', ['id' => $id]) ?>" data-confirm-delete>
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php if (!isViewer()): ?>
<div class="admin-modal" id="sendEmailModal" hidden aria-hidden="true">
    <div class="admin-modal-backdrop" data-modal-close></div>
    <div class="admin-modal-dialog admin-modal-dialog-wide" role="dialog" aria-modal="true" aria-labelledby="sendEmailModalTitle">
        <div class="admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon green"><?= panelIconSvg('email') ?></div>
                <div>
                    <h2 id="sendEmailModalTitle">Send Email</h2>
                    <p>To: <?= sanitize($enquiry['email']) ?></p>
                </div>
            </div>
            <button type="button" class="admin-modal-close" data-modal-close aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="admin-modal-body">
            <form method="POST" class="admin-form" id="sendEmailForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="send_email">
                <div class="form-group">
                    <label for="email_subject">Subject</label>
                    <input type="text" id="email_subject" name="email_subject" required value="Re: Your enquiry for <?= sanitize($enquiry['service']) ?>">
                </div>
                <div class="form-group">
                    <label for="email_body">Message</label>
                    <textarea id="email_body" name="email_body" rows="8" required placeholder="Write your message..."></textarea>
                </div>
                <div class="form-actions admin-modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($openEmailModal): ?>
<script>document.body.dataset.openModal = 'sendEmailModal';</script>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
