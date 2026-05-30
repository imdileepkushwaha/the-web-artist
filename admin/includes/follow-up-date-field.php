<?php
/** @var string|null $followUpDateValue */
/** @var string $followUpDateInputId */

$followUpDateValue = $followUpDateValue ?? '';
$followUpDateInputId = $followUpDateInputId ?? 'follow_up_date';
$hasFollowUpDate = $followUpDateValue !== '';
?>
<div class="form-group follow-up-date-group">
    <label for="<?= sanitize($followUpDateInputId) ?>">Follow-up Date</label>
    <div class="follow-up-date-control">
        <span class="follow-up-date-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </span>
        <input type="date"
               id="<?= sanitize($followUpDateInputId) ?>"
               name="follow_up_date"
               class="follow-up-date-input"
               value="<?= sanitize($followUpDateValue) ?>">
        <button type="button"
                class="follow-up-date-clear"
                aria-label="Clear follow-up date"
                <?= $hasFollowUpDate ? '' : 'hidden' ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="follow-up-date-presets">
        <button type="button" class="follow-up-date-preset" data-days="1">Tomorrow</button>
        <button type="button" class="follow-up-date-preset" data-days="3">In 3 days</button>
        <button type="button" class="follow-up-date-preset" data-days="7">In 1 week</button>
    </div>
    <span class="form-hint">Set a reminder date or leave empty to clear follow-up.</span>
</div>
