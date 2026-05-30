<?php
/** @var int $sortOrderValue */
/** @var string $sortOrderInputId */

$sortOrderValue = (int) ($sortOrderValue ?? 0);
$sortOrderInputId = $sortOrderInputId ?? 'sort_order';
?>
<div class="form-group sort-order-group">
    <label for="<?= sanitize($sortOrderInputId) ?>">Display Order</label>
    <div class="sort-order-control">
        <button type="button" class="sort-order-btn sort-order-decrease" aria-label="Decrease order number">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
        </button>
        <div class="sort-order-input-wrap">
            <span class="sort-order-prefix">#</span>
            <input type="number"
                   id="<?= sanitize($sortOrderInputId) ?>"
                   name="sort_order"
                   class="sort-order-input"
                   value="<?= $sortOrderValue ?>"
                   min="0"
                   max="999"
                   inputmode="numeric">
        </div>
        <button type="button" class="sort-order-btn sort-order-increase" aria-label="Increase order number">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        </button>
    </div>
    <span class="form-hint">Lower number appears first on the website.</span>
</div>
