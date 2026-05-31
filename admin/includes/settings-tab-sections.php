<div class="panel settings-card">
    <?php
    $panelTitle = 'Website Sections';
    $panelMeta = 'Navigation, section headings, CTA, social links & footer';
    $panelIconSvg = panelIconSvg('pages');
    $panelIconColor = 'purple';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <form method="POST" class="admin-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="sections">

            <div class="settings-section-divider settings-section-divider-first">
                <h3>Navigation</h3>
                <p>Show optional links when portfolio or FAQ content exists.</p>
            </div>
            <div class="settings-form-grid">
                <label class="checkbox-label settings-checkbox-label"><input type="checkbox" name="nav_show_portfolio" value="1" <?= ($settings['nav_show_portfolio'] ?? '1') === '1' ? 'checked' : '' ?>> <span>Show Portfolio link</span></label>
                <label class="checkbox-label settings-checkbox-label"><input type="checkbox" name="nav_show_faq" value="1" <?= ($settings['nav_show_faq'] ?? '1') === '1' ? 'checked' : '' ?>> <span>Show FAQ link</span></label>
            </div>

            <div class="settings-section-divider"><h3>Services Section</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="services_badge">Badge</label><input type="text" id="services_badge" name="services_badge" value="<?= sanitize($settings['services_badge'] ?? '') ?>"></div>
                <div class="form-group"><label for="services_title">Title</label><input type="text" id="services_title" name="services_title" value="<?= sanitize($settings['services_title'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="services_subtitle">Subtitle</label><input type="text" id="services_subtitle" name="services_subtitle" value="<?= sanitize($settings['services_subtitle'] ?? '') ?>"></div>
            </div>

            <div class="settings-section-divider"><h3>Testimonials Section</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="testimonials_badge">Badge</label><input type="text" id="testimonials_badge" name="testimonials_badge" value="<?= sanitize($settings['testimonials_badge'] ?? '') ?>"></div>
                <div class="form-group"><label for="testimonials_title">Title</label><input type="text" id="testimonials_title" name="testimonials_title" value="<?= sanitize($settings['testimonials_title'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="testimonials_subtitle">Subtitle</label><input type="text" id="testimonials_subtitle" name="testimonials_subtitle" value="<?= sanitize($settings['testimonials_subtitle'] ?? '') ?>"></div>
            </div>

            <div class="settings-section-divider"><h3>FAQ Section</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="faq_badge">Badge</label><input type="text" id="faq_badge" name="faq_badge" value="<?= sanitize($settings['faq_badge'] ?? '') ?>"></div>
                <div class="form-group"><label for="faq_title">Title</label><input type="text" id="faq_title" name="faq_title" value="<?= sanitize($settings['faq_title'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="faq_subtitle">Subtitle</label><input type="text" id="faq_subtitle" name="faq_subtitle" value="<?= sanitize($settings['faq_subtitle'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="faq_intro">Intro panel text</label><textarea id="faq_intro" name="faq_intro" rows="2"><?= sanitize($settings['faq_intro'] ?? '') ?></textarea></div>
            </div>

            <div class="settings-section-divider"><h3>About Extras</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="about_title_prefix">Title prefix</label><input type="text" id="about_title_prefix" name="about_title_prefix" value="<?= sanitize($settings['about_title_prefix'] ?? '') ?>"></div>
                <div class="form-group"><label for="about_card_title">Floating card title</label><input type="text" id="about_card_title" name="about_card_title" value="<?= sanitize($settings['about_card_title'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="about_card_text">Floating card text</label><input type="text" id="about_card_text" name="about_card_text" value="<?= sanitize($settings['about_card_text'] ?? '') ?>"></div>
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="form-group"><label for="about_feature<?= $i ?>_title">Feature <?= $i ?> title</label><input type="text" id="about_feature<?= $i ?>_title" name="about_feature<?= $i ?>_title" value="<?= sanitize($settings['about_feature' . $i . '_title'] ?? '') ?>"></div>
                <div class="form-group"><label for="about_feature<?= $i ?>_desc">Feature <?= $i ?> description</label><input type="text" id="about_feature<?= $i ?>_desc" name="about_feature<?= $i ?>_desc" value="<?= sanitize($settings['about_feature' . $i . '_desc'] ?? '') ?>"></div>
                <?php endfor; ?>
            </div>

            <div class="settings-section-divider"><h3>Call to Action</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="cta_badge">Badge</label><input type="text" id="cta_badge" name="cta_badge" value="<?= sanitize($settings['cta_badge'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_title_line1">Title line 1</label><input type="text" id="cta_title_line1" name="cta_title_line1" value="<?= sanitize($settings['cta_title_line1'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_title_accent">Title accent</label><input type="text" id="cta_title_accent" name="cta_title_accent" value="<?= sanitize($settings['cta_title_accent'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="cta_subtitle">Subtitle</label><textarea id="cta_subtitle" name="cta_subtitle" rows="2"><?= sanitize($settings['cta_subtitle'] ?? '') ?></textarea></div>
                <div class="form-group"><label for="cta_perk1">Perk 1</label><input type="text" id="cta_perk1" name="cta_perk1" value="<?= sanitize($settings['cta_perk1'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_perk2">Perk 2</label><input type="text" id="cta_perk2" name="cta_perk2" value="<?= sanitize($settings['cta_perk2'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_perk3">Perk 3</label><input type="text" id="cta_perk3" name="cta_perk3" value="<?= sanitize($settings['cta_perk3'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_btn_primary">Primary button</label><input type="text" id="cta_btn_primary" name="cta_btn_primary" value="<?= sanitize($settings['cta_btn_primary'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_btn_secondary">Secondary button</label><input type="text" id="cta_btn_secondary" name="cta_btn_secondary" value="<?= sanitize($settings['cta_btn_secondary'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_trust1">Trust stat 1</label><input type="text" id="cta_trust1" name="cta_trust1" value="<?= sanitize($settings['cta_trust1'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_trust2">Trust stat 2</label><input type="text" id="cta_trust2" name="cta_trust2" value="<?= sanitize($settings['cta_trust2'] ?? '') ?>"></div>
                <div class="form-group"><label for="cta_trust3">Trust stat 3</label><input type="text" id="cta_trust3" name="cta_trust3" value="<?= sanitize($settings['cta_trust3'] ?? '') ?>"></div>
            </div>

            <div class="settings-section-divider"><h3>Contact Section</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="contact_section_badge">Section badge</label><input type="text" id="contact_section_badge" name="contact_section_badge" value="<?= sanitize($settings['contact_section_badge'] ?? '') ?>"></div>
                <div class="form-group"><label for="contact_section_title">Section title</label><input type="text" id="contact_section_title" name="contact_section_title" value="<?= sanitize($settings['contact_section_title'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="contact_section_subtitle">Section subtitle</label><input type="text" id="contact_section_subtitle" name="contact_section_subtitle" value="<?= sanitize($settings['contact_section_subtitle'] ?? '') ?>"></div>
                <div class="form-group"><label for="contact_form_badge">Form badge</label><input type="text" id="contact_form_badge" name="contact_form_badge" value="<?= sanitize($settings['contact_form_badge'] ?? '') ?>"></div>
                <div class="form-group"><label for="contact_form_title">Form title</label><input type="text" id="contact_form_title" name="contact_form_title" value="<?= sanitize($settings['contact_form_title'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="contact_form_subtitle">Form subtitle</label><input type="text" id="contact_form_subtitle" name="contact_form_subtitle" value="<?= sanitize($settings['contact_form_subtitle'] ?? '') ?>"></div>
            </div>

            <div class="settings-section-divider"><h3>Social & Footer</h3></div>
            <div class="settings-form-grid">
                <div class="form-group"><label for="social_linkedin">LinkedIn URL</label><input type="url" id="social_linkedin" name="social_linkedin" value="<?= sanitize($settings['social_linkedin'] ?? '') ?>"></div>
                <div class="form-group"><label for="social_twitter">Twitter / X URL</label><input type="url" id="social_twitter" name="social_twitter" value="<?= sanitize($settings['social_twitter'] ?? '') ?>"></div>
                <div class="form-group"><label for="social_facebook">Facebook URL</label><input type="url" id="social_facebook" name="social_facebook" value="<?= sanitize($settings['social_facebook'] ?? '') ?>"></div>
                <div class="form-group"><label for="social_instagram">Instagram URL</label><input type="url" id="social_instagram" name="social_instagram" value="<?= sanitize($settings['social_instagram'] ?? '') ?>"></div>
                <div class="form-group settings-form-full"><label for="footer_text">Footer text</label><input type="text" id="footer_text" name="footer_text" value="<?= sanitize($settings['footer_text'] ?? '') ?>"></div>
            </div>

            <div class="form-actions"><button type="submit" class="btn btn-primary">Save Sections</button></div>
        </form>
    </div>
</div>
