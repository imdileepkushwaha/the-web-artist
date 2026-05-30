<?php
/** @var string $passwordFieldId */
/** @var string $passwordFieldLabel */
/** @var string|null $passwordFieldName */
/** @var string|null $passwordAutocomplete */
/** @var bool|null $passwordRequired */
/** @var int|null $passwordMinLength */

$fieldId = $passwordFieldId ?? 'password';
$fieldLabel = $passwordFieldLabel ?? 'Password';
$fieldName = $passwordFieldName ?? $fieldId;
$fieldAutocomplete = $passwordAutocomplete ?? 'current-password';
$fieldRequired = !isset($passwordRequired) || $passwordRequired;
$fieldMinLength = isset($passwordMinLength) ? (int) $passwordMinLength : 0;
?>
<div class="form-group">
    <label for="<?= sanitize($fieldId) ?>"><?= sanitize($fieldLabel) ?></label>
    <div class="password-input-wrap">
        <input type="password"
               id="<?= sanitize($fieldId) ?>"
               name="<?= sanitize($fieldName) ?>"
               class="password-input"
               autocomplete="<?= sanitize($fieldAutocomplete) ?>"
               <?= $fieldRequired ? 'required' : '' ?>
               <?= $fieldMinLength > 0 ? 'minlength="' . $fieldMinLength . '"' : '' ?>>
        <button type="button"
                class="password-toggle"
                aria-label="Show password"
                aria-pressed="false">
            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
    </div>
</div>
<?php
unset($passwordFieldId, $passwordFieldLabel, $passwordFieldName, $passwordAutocomplete, $passwordRequired, $passwordMinLength);
?>
