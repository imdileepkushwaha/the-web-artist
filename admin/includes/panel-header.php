<?php
/**
 * @var string $panelTitle
 * @var string|null $panelMeta
 * @var string|null $panelIconSvg
 * @var string $panelIconColor purple|green|orange|blue
 * @var bool $panelAccent
 * @var string|null $panelHeaderClass
 * @var string|null $panelLinkHref
 * @var string|null $panelLinkLabel
 */

$panelMeta = $panelMeta ?? null;
$panelIconSvg = $panelIconSvg ?? null;
$panelIconColor = trim($panelIconColor ?? '');
$panelAccent = !empty($panelAccent);
$panelHeaderClass = trim('panel-header' . ($panelAccent ? ' panel-header-accent' : '') . (!empty($panelHeaderClass) ? ' ' . $panelHeaderClass : ''));
$panelLinkHref = $panelLinkHref ?? null;
$panelLinkLabel = $panelLinkLabel ?? 'View all →';
$iconClass = 'panel-icon' . ($panelIconColor !== '' ? ' ' . $panelIconColor : '');
?>
<div class="<?= sanitize($panelHeaderClass) ?>">
    <?php if ($panelIconSvg): ?>
        <div class="panel-header-icon">
            <div class="<?= sanitize($iconClass) ?>"><?= $panelIconSvg ?></div>
            <div>
                <h2><?= sanitize($panelTitle) ?></h2>
                <?php if ($panelMeta !== null && $panelMeta !== ''): ?>
                    <p class="panel-meta"><?= $panelMeta ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div>
            <h2><?= sanitize($panelTitle) ?></h2>
            <?php if ($panelMeta !== null && $panelMeta !== ''): ?>
                <p class="panel-meta"><?= $panelMeta ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($panelLinkHref): ?>
        <a href="<?= sanitize($panelLinkHref) ?>" class="panel-link"><?= sanitize($panelLinkLabel) ?></a>
    <?php endif; ?>
</div>
<?php
unset($panelTitle, $panelMeta, $panelIconSvg, $panelIconColor, $panelAccent, $panelHeaderClass, $panelLinkHref, $panelLinkLabel);
?>
