<?php
require_once __DIR__ . '/includes/analytics.php';
require_once __DIR__ . '/includes/site-config.php';
require_once __DIR__ . '/includes/site-content.php';
require_once __DIR__ . '/includes/trusted-logo-svgs.php';
require_once __DIR__ . '/includes/security.php';

twaEnsureSession();
$formError = $_SESSION['form_error'] ?? '';
$formErrorSource = $_SESSION['form_error_source'] ?? '';
unset($_SESSION['form_error'], $_SESSION['form_error_source']);

$conn = getDbConnection();
recordWebsiteVisit('home');
$siteContent = initSiteContent();
$homepage = twaLoadHomepageSettings();
$sections = twaLoadSectionsSettings();
$siteServices = $siteContent['services'];
$siteTestimonials = $siteContent['testimonials'];
$siteFaq = $siteContent['faq'];
$sitePortfolio = $siteContent['portfolio'];
$siteTrustedClients = $siteContent['trusted_clients'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderSeoMeta(); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <div class="logo">
                <img src="<?= htmlspecialchars(twaPublicAssetUrl(SITE_LOGO)) ?>" alt="The Web Artist Logo" id="site-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            </div>
            <nav aria-label="Main navigation">
                <ul class="nav-links">
                    <li><a href="#home" class="nav-link active">Home</a></li>
                    <li><a href="#about" class="nav-link">About Us</a></li>
                    <li><a href="#services" class="nav-link">Services</a></li>
                    <?php if (!empty($sitePortfolio) && ($sections['nav_show_portfolio'] ?? '1') === '1'): ?>
                    <li><a href="#portfolio" class="nav-link">Portfolio</a></li>
                    <?php endif; ?>
                    <li><a href="#testimonials" class="nav-link">Testimonials</a></li>
                    <?php if (!empty($siteFaq) && ($sections['nav_show_faq'] ?? '1') === '1'): ?>
                    <li><a href="#faq" class="nav-link">FAQ</a></li>
                    <?php endif; ?>
                    <li><a href="#contact" class="nav-link">Contact</a></li>
                </ul>
            </nav>
            <div class="nav-actions">
                <a href="#home" class="btn-primary desktop-btn">Request Demo</a>
                <button class="mobile-menu-btn" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <!-- Floating shapes for modern aesthetic -->
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>

        <div class="container hero-container">
            <div class="hero-content animate slide-up">
                <span class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <?= htmlspecialchars($homepage['hero_badge']) ?>
                </span>
                <h1>
                    <?= htmlspecialchars($homepage['hero_title_line1']) ?>
                    <span class="text-gradient hero-gradient-text"><?= htmlspecialchars($homepage['hero_title_accent']) ?></span>
                    <span class="hero-headline-line"><?= htmlspecialchars($homepage['hero_title_line2']) ?></span>
                </h1>
                <p class="hero-subtitle"><?= htmlspecialchars($homepage['hero_subtitle']) ?></p>
                <div class="hero-tags">
                    <?php foreach ($homepage['hero_tags_list'] as $tag): ?>
                        <span class="hero-tag"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="hero-buttons">
                    <a href="#services" class="btn-primary btn-glow">
                        Explore Services
                        <svg class="btn-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#about" class="btn-outline">
                        Learn More
                    </a>
                </div>
                <div class="hero-trust">
                    <div class="hero-trust-item">
                        <span class="hero-trust-num text-gradient"><?= htmlspecialchars($homepage['hero_stat1_num']) ?></span>
                        <span class="hero-trust-label"><?= htmlspecialchars($homepage['hero_stat1_label']) ?></span>
                    </div>
                    <div class="hero-trust-divider"></div>
                    <div class="hero-trust-item">
                        <span class="hero-trust-num text-gradient"><?= htmlspecialchars($homepage['hero_stat2_num']) ?></span>
                        <span class="hero-trust-label"><?= htmlspecialchars($homepage['hero_stat2_label']) ?></span>
                    </div>
                    <div class="hero-trust-divider"></div>
                    <div class="hero-trust-item">
                        <span class="hero-trust-num text-gradient"><?= htmlspecialchars($homepage['hero_stat3_num']) ?></span>
                        <span class="hero-trust-label"><?= htmlspecialchars($homepage['hero_stat3_label']) ?></span>
                    </div>
                </div>

                
            </div>
            <div class="hero-form-container">
                <div class="hero-form-glow" aria-hidden="true"></div>
                <div class="glass-form hero-enquiry-form">
                    <div class="hero-form-top-accent" aria-hidden="true"></div>
                    <div class="hero-form-header">
                        
                        <div class="hero-form-heading">
                            <span class="hero-form-badge">
                                <span class="hero-form-badge-dot"></span>
                                <?= htmlspecialchars($homepage['hero_form_badge']) ?>
                            </span>
                            <h3><?= htmlspecialchars($homepage['hero_form_title']) ?></h3>
                            <p><?= htmlspecialchars($homepage['hero_form_subtitle']) ?></p>
                        </div>
                    </div>
                    <form action="submit.php" method="POST" id="enquiry-form" class="hero-form-fields">
                        <?= csrfField() ?>
                        <?= honeypotField() ?>
                        <input type="hidden" name="source" value="hero">
                        <?php if ($formError !== '' && $formErrorSource === 'hero'): ?><div class="form-error-banner"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
                        <div class="hero-form-row">
                            <div class="input-group input-with-icon">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input type="text" name="name" required placeholder="Your Name">
                            </div>
                            <div class="input-group input-with-icon">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                </span>
                                <input type="email" name="email" required placeholder="Your Email">
                            </div>
                        </div>
                        <div class="hero-form-row">
                            <div class="input-group input-with-icon">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </span>
                                <input type="tel" name="phone" required placeholder="Phone Number">
                            </div>
                            <div class="input-group input-with-icon">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                                </span>
                                <?php
                                $heroServiceOptions = [];

                                if (!empty($siteServices)) {
                                    foreach ($siteServices as $svc) {
                                        $heroServiceOptions[] = (string) $svc['title'];
                                    }
                                } else {
                                    $heroServiceOptions = [
                                        'Ecommerce Software',
                                        'MLM Software',
                                        'School Software',
                                        'Hospital Software',
                                        'Inventory Management',
                                        'Library Software',
                                        'Pharmacy Software',
                                        'AI Support System',
                                    ];
                                }

                                $heroServiceOptions[] = 'Appointment Booking Automation';
                                ?>
                                <select name="service" required>
                                    <?php foreach ($heroServiceOptions as $index => $serviceTitle): ?>
                                        <option value="<?= htmlspecialchars($serviceTitle) ?>" <?= $index === 0 ? 'selected' : '' ?>><?= htmlspecialchars($serviceTitle) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="input-group input-with-icon">
                            <span class="input-icon input-icon-top" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </span>
                            <textarea name="message" rows="3" placeholder="Any specific requirements? (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-100 hero-form-submit">
                            Submit Request
                            <svg class="btn-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </form>
                    <div class="hero-form-footer">
                        <div class="hero-form-perks">
                            <span class="hero-form-perk">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                No spam, ever
                            </span>
                            <span class="hero-form-perk">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                Reply within 24 hrs
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about">
        <div class="container about-container animate slide-up">
            <div class="about-text">
                <span class="about-badge">
                    <span class="about-badge-dot"></span>
                    <?= htmlspecialchars($homepage['about_badge']) ?>
                </span>
                <h2>
                    <?= htmlspecialchars($sections['about_title_prefix']) ?>
                    <span class="text-gradient about-title-accent"><?= htmlspecialchars($homepage['about_title_accent']) ?></span>
                    <span class="about-title-sub"><?= htmlspecialchars($homepage['about_title_sub']) ?></span>
                </h2>
                <p class="about-lead"><?= htmlspecialchars($homepage['about_lead']) ?></p>
                <p class="about-desc"><?= htmlspecialchars($homepage['about_desc']) ?></p>
                <ul class="about-features">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <li>
                        <span class="about-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>
                        <strong><?= htmlspecialchars($sections['about_feature' . $i . '_title']) ?></strong> — <?= htmlspecialchars($sections['about_feature' . $i . '_desc']) ?>
                    </li>
                    <?php endfor; ?>
                </ul>
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-icon-wrap">
                            <span class="stat-icon">💻</span>
                        </div>
                        <span class="stat-num text-gradient"><?= htmlspecialchars($homepage['hero_stat1_num']) ?></span>
                        <span class="stat-text"><?= htmlspecialchars($homepage['hero_stat1_label']) ?> Delivered</span>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-wrap">
                            <span class="stat-icon">🌟</span>
                        </div>
                        <span class="stat-num text-gradient"><?= htmlspecialchars($homepage['hero_stat2_num']) ?></span>
                        <span class="stat-text">Client <?= htmlspecialchars($homepage['hero_stat2_label']) ?></span>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-wrap">
                            <span class="stat-icon">🎧</span>
                        </div>
                        <span class="stat-num text-gradient"><?= htmlspecialchars($homepage['hero_stat3_num']) ?></span>
                        <span class="stat-text">Technical <?= htmlspecialchars($homepage['hero_stat3_label']) ?></span>
                    </div>
                </div>
                <div class="about-actions">
                    <a href="#services" class="btn-primary">Explore Services</a>
                    <a href="#contact" class="btn-outline">Get in Touch</a>
                </div>
            </div>
            <div class="about-image animate fade-in delay-1">
                <div class="image-wrapper">
                    <div class="abstract-shape"></div>
                    <div class="floating-card glass-panel">
                        <div class="card-icon">🚀</div>
                        <div class="card-text">
                            <h4><?= htmlspecialchars($sections['about_card_title']) ?></h4>
                            <p><?= htmlspecialchars($sections['about_card_text']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge badge-light"><?= htmlspecialchars($sections['services_badge']) ?></span>
                <h2><?= htmlspecialchars($sections['services_title']) ?></h2>
                <p class="subtitle"><?= htmlspecialchars($sections['services_subtitle']) ?></p>
            </div>
            
            <div class="services-grid">
                <?php if (!empty($siteServices)): ?>
                    <?php foreach ($siteServices as $i => $svc): ?>
                        <div class="service-card animate slide-up<?= $i % 3 === 1 ? ' delay-1' : ($i % 3 === 2 ? ' delay-2' : '') ?>">
                            <div class="card-glow"></div>
                            <div class="icon-wrapper"><div class="icon"><?= htmlspecialchars($svc['icon_emoji'] ?: '💻') ?></div></div>
                            <h3><?= htmlspecialchars($svc['title']) ?></h3>
                            <p><?= htmlspecialchars($svc['description']) ?></p>
                            <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <!-- Service 1 -->
                <div class="service-card animate slide-up">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">🛒</div></div>
                    <h3>Ecommerce Software</h3>
                    <p>Powerful platforms to sell your products online seamlessly.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 2 -->
                <div class="service-card animate slide-up delay-1">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">📈</div></div>
                    <h3>MLM Software</h3>
                    <p>Advanced multi-level marketing solutions for your network.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 3 -->
                <div class="service-card animate slide-up delay-2">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">🏫</div></div>
                    <h3>School Software</h3>
                    <p>Complete management systems for educational institutions.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 4 -->
                <div class="service-card animate slide-up">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">🏥</div></div>
                    <h3>Hospital Software</h3>
                    <p>Efficient patient and hospital management platforms.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 5 -->
                <div class="service-card animate slide-up delay-1">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">📦</div></div>
                    <h3>Inventory Management</h3>
                    <p>Track stock levels, orders, and sales effortlessly.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 6 -->
                <div class="service-card animate slide-up delay-2">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">📚</div></div>
                    <h3>Library Software</h3>
                    <p>Organize and automate library operations easily.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 7 -->
                <div class="service-card animate slide-up">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">💊</div></div>
                    <h3>Pharmacy Software</h3>
                    <p>Manage prescriptions, inventory, and billing seamlessly.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 8 -->
                <div class="service-card animate slide-up delay-1">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">🤖</div></div>
                    <h3>AI Support System</h3>
                    <p>Intelligent customer service solutions powered by AI.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <!-- Service 9 -->
                <div class="service-card animate slide-up delay-2">
                    <div class="card-glow"></div>
                    <div class="icon-wrapper"><div class="icon">📅</div></div>
                    <h3>Appointment Booking Automation</h3>
                    <p>Streamlined scheduling for your clients and staff.</p>
                    <a href="#contact" class="service-link">Learn More <span>&rarr;</span></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($siteTrustedClients)): ?>
    <section class="trusted-section">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge badge-light">TRUSTED BY</span>
                <h2>Companies That <span class="text-gradient">Trust Us</span></h2>
            </div>
            <div class="trusted-logos trusted-logos-section animate slide-up delay-1">
                <?php foreach ($siteTrustedClients as $index => $client): ?>
                    <?php
                    $logoKey = trustedClientLogoKey((string) ($client['logo_text'] ?? ''), (string) $client['name']);
                    $svg = trustedClientLogoSvg($logoKey, $index);
                    echo $svg ?? trustedClientLogoFallbackSvg(
                        (string) $client['name'],
                        (string) ($client['logo_text'] ?? ''),
                        $index
                    );
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($sitePortfolio)): ?>
    <section id="portfolio" class="portfolio-section">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge">OUR WORK</span>
                <h2>Featured <span class="text-gradient">Projects</span></h2>
                <p class="subtitle">Real solutions we've built for businesses across industries.</p>
            </div>
            <div class="portfolio-grid">
                <?php foreach ($sitePortfolio as $i => $project): ?>
                    <div class="portfolio-card animate slide-up<?= $i % 3 === 1 ? ' delay-1' : ($i % 3 === 2 ? ' delay-2' : '') ?>">
                        <div class="portfolio-card-top">
                            <span class="portfolio-category"><?= htmlspecialchars($project['category']) ?></span>
                            <?php if (!empty($project['image_url'])): ?>
                                <img src="<?= htmlspecialchars($project['image_url']) ?>" alt="<?= htmlspecialchars($project['title']) ?>" class="portfolio-image">
                            <?php else: ?>
                                <div class="portfolio-image-placeholder">📁</div>
                            <?php endif; ?>
                        </div>
                        <div class="portfolio-card-body">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <p><?= htmlspecialchars($project['description']) ?></p>
                            <?php if (!empty($project['project_url'])): ?>
                                <a href="<?= htmlspecialchars($project['project_url']) ?>" class="service-link" target="_blank" rel="noopener noreferrer">View Project <span>&rarr;</span></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge"><?= htmlspecialchars($sections['testimonials_badge']) ?></span>
                <h2><?= htmlspecialchars($sections['testimonials_title']) ?></h2>
                <p class="subtitle"><?= htmlspecialchars($sections['testimonials_subtitle']) ?></p>
            </div>
            
            <div class="testimonials-grid">
                <?php if (!empty($siteTestimonials)): ?>
                    <?php foreach ($siteTestimonials as $i => $item): ?>
                        <div class="testimonial-card animate slide-up<?= $i % 3 === 1 ? ' delay-1' : ($i % 3 === 2 ? ' delay-2' : '') ?>">
                            <div class="quote-icon">“</div>
                            <p class="feedback">"<?= htmlspecialchars($item['feedback']) ?>"</p>
                            <div class="client-info">
                                <div class="client-avatar"><?= htmlspecialchars($item['initials'] ?: strtoupper(substr($item['client_name'], 0, 2))) ?></div>
                                <div>
                                    <h4><?= htmlspecialchars($item['client_name']) ?></h4>
                                    <span><?= htmlspecialchars($item['company']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <!-- Testimonial 1 -->
                <div class="testimonial-card animate slide-up">
                    <div class="quote-icon">“</div>
                    <p class="feedback">"The Web Artist completely transformed our hospital management. Their software is incredibly intuitive, and their support team is top-notch!"</p>
                    <div class="client-info">
                        <div class="client-avatar">DR</div>
                        <div>
                            <h4>Dr. Rahul Sharma</h4>
                            <span>City Hospital</span>
                        </div>
                    </div>
                </div>
                <!-- Testimonial 2 -->
                <div class="testimonial-card animate slide-up delay-1">
                    <div class="quote-icon">“</div>
                    <p class="feedback">"Our ecommerce sales skyrocketed after switching to their platform. The modern UI and seamless checkout process made all the difference."</p>
                    <div class="client-info">
                        <div class="client-avatar">SP</div>
                        <div>
                            <h4>Sneha Patel</h4>
                            <span>RetailPro Online</span>
                        </div>
                    </div>
                </div>
                <!-- Testimonial 3 -->
                <div class="testimonial-card animate slide-up delay-2">
                    <div class="quote-icon">“</div>
                    <p class="feedback">"Managing our school operations is now a breeze. From attendance to fees, everything is automated. Highly recommended team!"</p>
                    <div class="client-info">
                        <div class="client-avatar">AK</div>
                        <div>
                            <h4>Arun Kumar</h4>
                            <span>Global Public School</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($siteFaq)): ?>
    <section id="faq" class="faq-section">
        <div class="faq-shape faq-shape-1" aria-hidden="true"></div>
        <div class="faq-shape faq-shape-2" aria-hidden="true"></div>
        <div class="container">
            <div class="faq-grid">
                <div class="faq-intro animate slide-up">
                    <span class="section-badge"><?= htmlspecialchars($sections['faq_badge']) ?></span>
                    <h2><?= htmlspecialchars($sections['faq_title']) ?></h2>
                    <p class="faq-intro-text"><?= htmlspecialchars($sections['faq_intro']) ?></p>
                    <a href="#contact" class="faq-contact-link">
                        Contact our team
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <div class="faq-stats">
                        <div class="faq-stat">
                            <strong><?= count($siteFaq) ?>+</strong>
                            <span>Topics covered</span>
                        </div>
                        <div class="faq-stat">
                            <strong>24/7</strong>
                            <span>Support available</span>
                        </div>
                    </div>
                </div>
                <div class="faq-list">
                    <?php foreach ($siteFaq as $index => $item): ?>
                        <?php $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?>
                        <div class="faq-item animate slide-up<?= $index === 0 ? ' active' : '' ?>">
                            <button type="button" class="faq-question" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
                                <span class="faq-q-num"><?= $num ?></span>
                                <span class="faq-q-text"><?= htmlspecialchars($item['question']) ?></span>
                                <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="faq-answer">
                                <p><?= nl2br(htmlspecialchars($item['answer'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="cta-shape cta-shape-1" aria-hidden="true"></div>
        <div class="cta-shape cta-shape-2" aria-hidden="true"></div>
        <div class="cta-shape cta-shape-3" aria-hidden="true"></div>
        <div class="container cta-container animate slide-up">
            <div class="cta-card">
                <span class="cta-badge">
                    <span class="cta-badge-dot"></span>
                    <?= htmlspecialchars($sections['cta_badge']) ?>
                </span>
                <h2>
                    <?= htmlspecialchars($sections['cta_title_line1']) ?>
                    <span class="text-gradient-alt cta-title-accent"><?= htmlspecialchars($sections['cta_title_accent']) ?></span>
                </h2>
                <p class="cta-subtitle"><?= htmlspecialchars($sections['cta_subtitle']) ?></p>
                <div class="cta-perks">
                    <span class="cta-perk">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?= htmlspecialchars($sections['cta_perk1']) ?>
                    </span>
                    <span class="cta-perk">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <?= htmlspecialchars($sections['cta_perk2']) ?>
                    </span>
                    <span class="cta-perk">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <?= htmlspecialchars($sections['cta_perk3']) ?>
                    </span>
                </div>
                <div class="cta-buttons">
                    <a href="#home" class="btn-primary btn-glow cta-btn-primary">
                        <?= htmlspecialchars($sections['cta_btn_primary']) ?>
                        <svg class="btn-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#contact" class="cta-btn-outline"><?= htmlspecialchars($sections['cta_btn_secondary']) ?></a>
                </div>
                <div class="cta-trust">
                    <span><?= htmlspecialchars($sections['cta_trust1']) ?></span>
                    <span class="cta-trust-dot" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($sections['cta_trust2']) ?></span>
                    <span class="cta-trust-dot" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($sections['cta_trust3']) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge"><?= htmlspecialchars($sections['contact_section_badge']) ?></span>
                <h2><?= htmlspecialchars($sections['contact_section_title']) ?></h2>
                <p class="subtitle"><?= htmlspecialchars($sections['contact_section_subtitle']) ?></p>
            </div>
            
            <div class="contact-container animate slide-up delay-1">
                <div class="contact-details">
                    <div class="contact-details-shape" aria-hidden="true"></div>

                    <span class="contact-details-badge">Get in Touch</span>
                    <h3>Contact Information</h3>
                    <p>Reach out to us directly through any of the channels below, or visit our office.</p>

                    <div class="contact-hours">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <?= htmlspecialchars($homepage['business_hours']) ?>
                    </div>

                    <div class="contact-info-list">
                        <?php if (($homepage['site_location_enabled'] ?? '0') === '1' && ($homepage['site_address'] !== '' || $homepage['site_address_line2'] !== '')): ?>
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <h4>Our Location</h4>
                                <p><?= htmlspecialchars($homepage['site_address']) ?><?= $homepage['site_address_line2'] !== '' ? '<br>' . htmlspecialchars($homepage['site_address_line2']) : '' ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <h4>Phone Number</h4>
                                <p><a href="tel:<?= SITE_PHONE_RAW ?>"><?= htmlspecialchars(SITE_PHONE) ?></a></p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <h4>Email Address</h4>
                                <p><a href="mailto:<?= htmlspecialchars(SITE_EMAIL) ?>"><?= htmlspecialchars(SITE_EMAIL) ?></a></p>
                            </div>
                        </div>
                    </div>

                    <div class="social-links">
                        <span class="social-label">Follow Us</span>
                        <div class="social-icons">
                            <?php
                            $socialSvgs = [
                                'linkedin' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                                'twitter' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                                'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                                'instagram' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
                            ];
                            foreach ($socialSvgs as $network => $svg):
                                $url = trim((string) ($sections['social_' . $network] ?? ''));
                                if ($url === '') continue;
                            ?>
                            <a href="<?= htmlspecialchars($url) ?>" class="social-icon" aria-label="<?= ucfirst($network) ?>" target="_blank" rel="noopener noreferrer"><?= $svg ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form-wrapper">
                    <div class="hero-form-glow contact-form-glow" aria-hidden="true"></div>
                    <div class="glass-form hero-enquiry-form contact-enquiry-form">
                        <div class="hero-form-top-accent" aria-hidden="true"></div>
                        <div class="hero-form-header">
                            <div class="hero-form-heading">
                                <span class="hero-form-badge">
                                    <span class="hero-form-badge-dot"></span>
                                    <?= htmlspecialchars($sections['contact_form_badge']) ?>
                                </span>
                                <h3><?= htmlspecialchars($sections['contact_form_title']) ?></h3>
                                <p><?= htmlspecialchars($sections['contact_form_subtitle']) ?></p>
                            </div>
                        </div>
                        <form action="submit.php" method="POST" id="contact-form" class="hero-form-fields">
                            <?= csrfField() ?>
                            <?= honeypotField('website_url_contact') ?>
                            <input type="hidden" name="source" value="contact">
                            <?php if ($formError !== '' && $formErrorSource === 'contact'): ?><div class="form-error-banner"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
                            <div class="hero-form-row">
                                <div class="input-group input-with-icon">
                                    <span class="input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <input type="text" name="name" required placeholder="Your Name">
                                </div>
                                <div class="input-group input-with-icon">
                                    <span class="input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    </span>
                                    <input type="email" name="email" required placeholder="Your Email">
                                </div>
                            </div>
                            <div class="input-group input-with-icon">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </span>
                                <input type="tel" name="phone" required placeholder="Phone Number">
                            </div>
                            <div class="input-group input-with-icon">
                                <span class="input-icon input-icon-top" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </span>
                                <textarea name="message" rows="4" required placeholder="How can we help you?"></textarea>
                            </div>
                            <button type="submit" class="btn-primary w-100 hero-form-submit">
                                Send Message
                                <svg class="btn-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                            </button>
                        </form>
                        <div class="hero-form-footer">
                            <div class="hero-form-perks">
                                <span class="hero-form-perk">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    Your data is secure
                                </span>
                                <span class="hero-form-perk">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    Reply within 24 hrs
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p><?= htmlspecialchars($sections['footer_text']) ?></p>
        </div>
    </footer>

    <?php require __DIR__ . '/includes/whatsapp-float.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
