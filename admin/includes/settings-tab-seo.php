<div class="panel settings-card">
    <?php
    $panelTitle = 'SEO Settings';
    $panelMeta = 'Search engine meta tags & analytics';
    $panelIconSvg = panelIconSvg('pages');
    $panelIconColor = 'green';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="seo">

            <div class="settings-section-divider settings-section-divider-first">
                <h3>Meta Tags</h3>
                <p>Controls how your site appears in search results and social shares.</p>
            </div>

            <div class="settings-form-grid">
                <div class="form-group settings-form-grid-full">
                    <label for="seo_title">Page Title</label>
                    <input type="text" id="seo_title" name="seo_title" value="<?= sanitize($settings['seo_title'] ?? '') ?>" placeholder="The Web Artist - IT Solutions">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="seo_description">Meta Description</label>
                    <textarea id="seo_description" name="seo_description" rows="3" placeholder="Brief description for search engines"><?= sanitize($settings['seo_description'] ?? '') ?></textarea>
                    <span class="form-hint">Recommended: 150–160 characters.</span>
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="seo_keywords">Keywords</label>
                    <input type="text" id="seo_keywords" name="seo_keywords" value="<?= sanitize($settings['seo_keywords'] ?? '') ?>" placeholder="web development, software company">
                </div>
                <div class="form-group settings-form-grid-full">
                    <label for="og_image_url">OG Image URL</label>
                    <input type="url" id="og_image_url" name="og_image_url" value="<?= sanitize($settings['og_image_url'] ?? '') ?>" placeholder="https://yoursite.com/images/og-image.png">
                    <span class="form-hint">Used when sharing on social media. Leave empty for default logo.</span>
                </div>
            </div>

            <div class="settings-section-divider">
                <h3>Analytics</h3>
            </div>

            <div class="settings-form-grid">
                <div class="form-group settings-form-grid-full">
                    <label for="google_analytics_id">Google Analytics ID</label>
                    <input type="text" id="google_analytics_id" name="google_analytics_id" value="<?= sanitize($settings['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX or UA-XXXXXXXXX-X">
                    <span class="form-hint">Tracking script is added to the public website when set.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save SEO Settings</button>
            </div>
        </form>
    </div>
</div>
