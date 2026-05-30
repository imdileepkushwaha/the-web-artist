<?php
/** @var int|string $sessionTimeoutValue */
/** @var string $sessionTimeoutInputId */

$sessionTimeoutValue = max(5, min(480, (int) ($sessionTimeoutValue ?? 30)));
$sessionTimeoutInputId = $sessionTimeoutInputId ?? 'session_timeout_minutes';
?>
<div class="form-group session-timeout-group">
    <label for="<?= sanitize($sessionTimeoutInputId) ?>">Session Timeout</label>
    <div class="session-timeout-control">
        <button type="button" class="session-timeout-btn session-timeout-decrease" aria-label="Decrease timeout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
        </button>
        <div class="session-timeout-input-wrap">
            <span class="session-timeout-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <input type="number"
                   id="<?= sanitize($sessionTimeoutInputId) ?>"
                   name="session_timeout_minutes"
                   class="session-timeout-input"
                   value="<?= $sessionTimeoutValue ?>"
                   min="5"
                   max="480"
                   step="5"
                   inputmode="numeric">
            <span class="session-timeout-suffix">min</span>
        </div>
        <button type="button" class="session-timeout-btn session-timeout-increase" aria-label="Increase timeout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        </button>
    </div>
    <div class="session-timeout-presets">
        <button type="button" class="session-timeout-preset" data-minutes="15">15 min</button>
        <button type="button" class="session-timeout-preset" data-minutes="30">30 min</button>
        <button type="button" class="session-timeout-preset" data-minutes="60">1 hour</button>
        <button type="button" class="session-timeout-preset" data-minutes="120">2 hours</button>
        <button type="button" class="session-timeout-preset" data-minutes="480">8 hours</button>
    </div>
    <span class="form-hint">Auto logout after inactivity. Allowed range: 5–480 minutes.</span>
</div>
