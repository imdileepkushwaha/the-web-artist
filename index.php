<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Web Artist - IT Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <div class="logo">
                <!-- Replace src with your attached logo -->
                <img src="images/twa-logo.png" alt="The Web Artist Logo" id="site-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            </div>
            <nav aria-label="Main navigation">
                <ul class="nav-links">
                    <li><a href="#home" class="nav-link active">Home</a></li>
                    <li><a href="#about" class="nav-link">About Us</a></li>
                    <li><a href="#services" class="nav-link">Services</a></li>
                    <li><a href="#testimonials" class="nav-link">Testimonials</a></li>
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
                    Trusted IT Solutions Partner
                </span>
                <h1>
                    Transform Your
                    <span class="text-gradient hero-gradient-text">Business</span>
                    <span class="hero-headline-line">With Smart Software</span>
                </h1>
                <p class="hero-subtitle">We deliver cutting-edge solutions — Ecommerce, School & Hospital Management, and AI Support Systems — built to scale with your growth.</p>
                <div class="hero-tags">
                    <span class="hero-tag">Ecommerce</span>
                    <span class="hero-tag">Healthcare</span>
                    <span class="hero-tag">Education</span>
                    <span class="hero-tag">AI Systems</span>
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
                        <span class="hero-trust-num text-gradient">50+</span>
                        <span class="hero-trust-label">Projects</span>
                    </div>
                    <div class="hero-trust-divider"></div>
                    <div class="hero-trust-item">
                        <span class="hero-trust-num text-gradient">98%</span>
                        <span class="hero-trust-label">Satisfaction</span>
                    </div>
                    <div class="hero-trust-divider"></div>
                    <div class="hero-trust-item">
                        <span class="hero-trust-num text-gradient">24/7</span>
                        <span class="hero-trust-label">Support</span>
                    </div>
                </div>
            </div>
            <div class="hero-form-container animate fade-in delay-1">
                <div class="glass-form">
                    <h3>Request a Demo</h3>
                    <p>Fill out the form below and we'll get back to you shortly.</p>
                    <form action="submit.php" method="POST" id="enquiry-form">
                        <div class="input-group">
                            <input type="text" name="name" required placeholder="Your Name">
                        </div>
                        <div class="input-group">
                            <input type="email" name="email" required placeholder="Your Email">
                        </div>
                        <div class="input-group">
                            <input type="tel" name="phone" required placeholder="Phone Number">
                        </div>
                        <div class="input-group">
                            <select name="service" required>
                                <option value="" disabled selected>Select a Service</option>
                                <option value="Ecommerce Software">Ecommerce Software</option>
                                <option value="MLM Software">MLM Software</option>
                                <option value="School Software">School Software</option>
                                <option value="Hospital Software">Hospital Software</option>
                                <option value="Inventory Management">Inventory Management</option>
                                <option value="Library Software">Library Software</option>
                                <option value="Pharmacy Software">Pharmacy Software</option>
                                <option value="AI Support System">AI Support System</option>
                                <option value="Appointment Booking Automation">Appointment Booking Automation</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <textarea name="message" rows="3" placeholder="Any specific requirements?"></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-100">Submit Request</button>
                    </form>
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
                    About Us
                </span>
                <h2>
                    Crafting Digital
                    <span class="text-gradient about-title-accent">Excellence</span>
                    <span class="about-title-sub">at The Web Artist</span>
                </h2>
                <p class="about-lead">We are a premier IT company dedicated to crafting high-quality, modern, and scalable software solutions for growing businesses.</p>
                <p class="about-desc">With years of experience, we empower organizations across healthcare, education, retail, and direct sales — turning ideas into reliable products through robust engineering and beautiful design.</p>
                <ul class="about-features">
                    <li>
                        <span class="about-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>
                        Custom-built for your workflow
                    </li>
                    <li>
                        <span class="about-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>
                        Scalable & future-ready architecture
                    </li>
                    <li>
                        <span class="about-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>
                        Dedicated support from day one
                    </li>
                </ul>
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-icon-wrap">
                            <span class="stat-icon">💻</span>
                        </div>
                        <span class="stat-num text-gradient">50+</span>
                        <span class="stat-text">Projects Delivered</span>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-wrap">
                            <span class="stat-icon">🌟</span>
                        </div>
                        <span class="stat-num text-gradient">98%</span>
                        <span class="stat-text">Client Satisfaction</span>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-wrap">
                            <span class="stat-icon">🎧</span>
                        </div>
                        <span class="stat-num text-gradient">24/7</span>
                        <span class="stat-text">Technical Support</span>
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
                            <h4>Innovation First</h4>
                            <p>Cutting-edge tech stack</p>
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
                <span class="section-badge badge-light">WHAT WE DO</span>
                <h2>Our Premium <span class="text-gradient">Services</span></h2>
                <p class="subtitle">Comprehensive software solutions tailored for your industry.</p>
            </div>
            
            <div class="services-grid">
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
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge">TESTIMONIALS</span>
                <h2>What Our <span class="text-gradient">Clients Say</span></h2>
                <p class="subtitle">Don't just take our word for it—hear from the businesses we've helped grow.</p>
            </div>
            
            <div class="testimonials-grid">
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
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container text-center animate slide-up">
            <h2>Ready to Elevate Your <span class="text-gradient">Business?</span></h2>
            <p class="subtitle">Join hundreds of satisfied clients and transform your operations with our modern IT solutions.</p>
            <div class="cta-buttons">
                <a href="#home" class="btn-primary btn-glow">Get Started Today</a>
                <a href="#contact" class="btn-outline">Contact Sales</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="section-header text-center animate slide-up">
                <span class="section-badge">CONTACT US</span>
                <h2>Get In <span class="text-gradient">Touch With Us</span></h2>
                <p class="subtitle">Have questions or need a custom solution? We're here to help.</p>
            </div>
            
            <div class="contact-container animate slide-up delay-1">
                <div class="contact-details">
                    <h3>Contact Information</h3>
                    <p>Reach out to us directly through any of the channels below, or visit our office.</p>
                    
                    <div class="contact-info-item">
                        <div class="contact-icon">📍</div>
                        <div>
                            <h4>Our Location</h4>
                            <p>123 Tech Park, Cyber City, Phase 2<br>New Delhi, India</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-icon">📞</div>
                        <div>
                            <h4>Phone Number</h4>
                            <p>+91 98765 43210</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-icon">✉️</div>
                        <div>
                            <h4>Email Address</h4>
                            <p>hello@thewebartist.com</p>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-icon">In</a>
                        <a href="#" class="social-icon">Tw</a>
                        <a href="#" class="social-icon">Fb</a>
                        <a href="#" class="social-icon">Ig</a>
                    </div>
                </div>
                
                <div class="contact-form-wrapper">
                    <div class="glass-form contact-form">
                        <h3>Send a Message</h3>
                        <form action="submit.php" method="POST" id="contact-form">
                            <div class="input-group">
                                <input type="text" name="name" required placeholder="Your Name">
                            </div>
                            <div class="input-group">
                                <input type="email" name="email" required placeholder="Your Email">
                            </div>
                            <div class="input-group">
                                <input type="tel" name="phone" required placeholder="Phone Number">
                            </div>
                            <div class="input-group">
                                <textarea name="message" rows="4" required placeholder="How can we help you?"></textarea>
                            </div>
                            <button type="submit" class="btn-primary w-100">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2026 The Web Artist. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
