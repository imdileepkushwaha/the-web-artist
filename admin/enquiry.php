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
                <a href="mailto:<?= sanitize($enquiry['email']) ?>?subject=Re:%20Your%20enquiry%20for%20<?= rawurlencode($enquiry['service']) ?>" class="enquiry-action-btn enquiry-action-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Email
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
                    <form method="POST" action="enquiry.php?id=<?= $id ?>" class="admin-form">
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
                        <h3>Quick Reply Templates</h3>
                        <div class="enquiry-template-list">
                            <?php foreach ($emailTemplates as $template): ?>
                                <?php
                                $subject = applyEmailTemplate($template['subject'], $enquiry);
                                $body = applyEmailTemplate($template['body'], $enquiry);
                                $mailto = 'mailto:' . rawurlencode($enquiry['email']) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
                                ?>
                                <a href="<?= sanitize($mailto) ?>" class="enquiry-template-chip">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <?= sanitize($template['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
                    <form method="POST" action="enquiry.php?id=<?= $id ?>" class="admin-form enquiry-note-form">
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
                        <form method="POST" action="enquiry.php?id=<?= $id ?>" data-confirm-delete>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
