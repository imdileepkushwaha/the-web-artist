<?php

function ensureAllAdminTables(PDO $conn): void
{
    ensureEnquiriesTable($conn);
    migrateEnquiriesExtendedColumns($conn);
    ensureAdminUsersTable($conn);
    ensureAdminSettingsTable($conn);
    ensureActivityLogTable($conn);
    ensureLoginHistoryTable($conn);
    ensureEmailTemplatesTable($conn);
    ensureFaqItemsTable($conn);
    ensureTestimonialsTable($conn);
    ensureServicesTable($conn);

    seedDefaultAdminUser($conn);
    seedDefaultSettings($conn);
    seedDefaultEmailTemplates($conn);
    seedDefaultFaqItems($conn);
    seedDefaultTestimonials($conn);
    seedDefaultServices($conn);
}

function migrateEnquiriesExtendedColumns(PDO $conn): void
{
    $columns = $conn->query('SHOW COLUMNS FROM enquiries')->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('source', $columns, true)) {
        $conn->exec("ALTER TABLE enquiries ADD COLUMN source ENUM('hero','contact') NOT NULL DEFAULT 'contact' AFTER message");
    }

    if (!in_array('assigned_to', $columns, true)) {
        $conn->exec('ALTER TABLE enquiries ADD COLUMN assigned_to INT(11) UNSIGNED NULL AFTER status');
    }

    if (!in_array('follow_up_date', $columns, true)) {
        $conn->exec('ALTER TABLE enquiries ADD COLUMN follow_up_date DATE NULL AFTER assigned_to');
    }
}

function ensureAdminUsersTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL DEFAULT '',
        email VARCHAR(100) NULL,
        role ENUM('admin','viewer') NOT NULL DEFAULT 'admin',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureAdminSettingsTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureActivityLogTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NULL,
        username VARCHAR(50) NOT NULL DEFAULT '',
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NULL,
        entity_id INT(11) UNSIGNED NULL,
        details TEXT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_entity (entity_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureLoginHistoryTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS login_history (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NULL,
        username VARCHAR(50) NOT NULL DEFAULT '',
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureEmailTemplatesTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS email_templates (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureFaqItemsTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS faq_items (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT(11) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureTestimonialsTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(100) NOT NULL,
        company VARCHAR(100) NOT NULL DEFAULT '',
        feedback TEXT NOT NULL,
        initials VARCHAR(10) NOT NULL DEFAULT '',
        sort_order INT(11) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureServicesTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS services (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        icon_emoji VARCHAR(10) NOT NULL DEFAULT '',
        sort_order INT(11) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function seedDefaultAdminUser(PDO $conn): void
{
    $count = (int) $conn->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $username = defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'admin';
    $password = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'admin123';

    $stmt = $conn->prepare('INSERT INTO admin_users (username, password_hash, name, email, role, is_active)
        VALUES (:username, :password_hash, :name, :email, :role, 1)');
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':name' => 'Administrator',
        ':email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@thewebartist.com',
        ':role' => 'admin',
    ]);
}

function seedDefaultSettings(PDO $conn): void
{
    $defaults = [
        'admin_email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@thewebartist.com',
        'notify_email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@thewebartist.com',
        'site_email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'hello@thewebartist.com',
        'site_phone' => defined('SITE_PHONE') ? SITE_PHONE : '+91 98765 43210',
        'email_from_name' => 'The Web Artist',
        'notify_enquiries_enabled' => '1',
        'smtp_enabled' => '0',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'session_timeout_minutes' => '30',
        'dark_mode' => '0',
    ];

    $stmt = $conn->prepare('INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES (:key, :value)');

    foreach ($defaults as $key => $value) {
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
}

function seedDefaultEmailTemplates(PDO $conn): void
{
    $count = (int) $conn->query('SELECT COUNT(*) FROM email_templates')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $templates = [
        [
            'name' => 'Follow Up',
            'subject' => 'Following up on your enquiry — The Web Artist',
            'body' => "Hi {{name}},\n\nThank you for reaching out to The Web Artist regarding {{service}}.\n\nWe wanted to follow up on your enquiry and see if you had any additional questions. Our team is ready to help you find the right solution for your business.\n\nPlease reply to this email or call us at {{site_phone}} to schedule a convenient time to talk.\n\nBest regards,\nThe Web Artist Team",
        ],
        [
            'name' => 'Proposal Sent',
            'subject' => 'Your project proposal from The Web Artist',
            'body' => "Hi {{name}},\n\nThank you for your interest in our {{service}} solution.\n\nWe have prepared a proposal based on your requirements. Please review the attached details and let us know if you would like to discuss any changes.\n\nWe look forward to partnering with you.\n\nBest regards,\nThe Web Artist Team",
        ],
        [
            'name' => 'Demo Scheduled',
            'subject' => 'Your demo is scheduled — The Web Artist',
            'body' => "Hi {{name}},\n\nYour product demo for {{service}} has been scheduled.\n\nOur team will walk you through the features and answer any questions you may have. If you need to reschedule, please reply to this email or contact us at {{site_phone}}.\n\nWe look forward to meeting with you!\n\nBest regards,\nThe Web Artist Team",
        ],
    ];

    $stmt = $conn->prepare('INSERT INTO email_templates (name, subject, body) VALUES (:name, :subject, :body)');

    foreach ($templates as $template) {
        $stmt->execute($template);
    }
}

function seedDefaultFaqItems(PDO $conn): void
{
    $count = (int) $conn->query('SELECT COUNT(*) FROM faq_items')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $items = [
        [
            'question' => 'What services does The Web Artist offer?',
            'answer' => 'We build custom software including Ecommerce platforms, School & Hospital Management systems, MLM software, Inventory Management, Pharmacy & Library solutions, AI support systems, and appointment booking automation.',
            'sort_order' => 1,
        ],
        [
            'question' => 'How long does a typical project take?',
            'answer' => 'Timeline depends on scope. A basic website or module takes 2–4 weeks; full custom software usually takes 6–12 weeks. We share a clear timeline after understanding your requirements.',
            'sort_order' => 2,
        ],
        [
            'question' => 'Do you provide support after delivery?',
            'answer' => 'Yes. We offer 24/7 technical support, bug fixes, updates, and optional AMC (Annual Maintenance Contract) plans so your software stays secure and up to date.',
            'sort_order' => 3,
        ],
        [
            'question' => 'Can the software be customized for my business?',
            'answer' => "Absolutely. Every solution is tailored to your workflow, branding, and business rules. We don't believe in one-size-fits-all software.",
            'sort_order' => 4,
        ],
        [
            'question' => 'How do I get started?',
            'answer' => 'Fill out the demo request form on our homepage or contact section, or message us on WhatsApp. We\'ll schedule a free consultation to discuss your needs.',
            'sort_order' => 5,
        ],
        [
            'question' => 'What are your pricing models?',
            'answer' => 'Pricing depends on features, integrations, and timeline. We offer flexible one-time project pricing and optional monthly support plans. Request a demo for a custom quote.',
            'sort_order' => 6,
        ],
    ];

    $stmt = $conn->prepare('INSERT INTO faq_items (question, answer, sort_order, is_active) VALUES (:question, :answer, :sort_order, 1)');

    foreach ($items as $item) {
        $stmt->execute($item);
    }
}

function seedDefaultTestimonials(PDO $conn): void
{
    $count = (int) $conn->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $items = [
        [
            'client_name' => 'Dr. Rahul Sharma',
            'company' => 'City Hospital',
            'feedback' => 'The Web Artist completely transformed our hospital management. Their software is incredibly intuitive, and their support team is top-notch!',
            'initials' => 'DR',
            'sort_order' => 1,
        ],
        [
            'client_name' => 'Sneha Patel',
            'company' => 'RetailPro Online',
            'feedback' => 'Our ecommerce sales skyrocketed after switching to their platform. The modern UI and seamless checkout process made all the difference.',
            'initials' => 'SP',
            'sort_order' => 2,
        ],
        [
            'client_name' => 'Arun Kumar',
            'company' => 'Global Public School',
            'feedback' => 'Managing our school operations is now a breeze. From attendance to fees, everything is automated. Highly recommended team!',
            'initials' => 'AK',
            'sort_order' => 3,
        ],
    ];

    $stmt = $conn->prepare('INSERT INTO testimonials (client_name, company, feedback, initials, sort_order, is_active)
        VALUES (:client_name, :company, :feedback, :initials, :sort_order, 1)');

    foreach ($items as $item) {
        $stmt->execute($item);
    }
}

function seedDefaultServices(PDO $conn): void
{
    $count = (int) $conn->query('SELECT COUNT(*) FROM services')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $items = [
        ['title' => 'Ecommerce Software', 'description' => 'Powerful platforms to sell your products online seamlessly.', 'icon_emoji' => '🛒', 'sort_order' => 1],
        ['title' => 'MLM Software', 'description' => 'Advanced multi-level marketing solutions for your network.', 'icon_emoji' => '📈', 'sort_order' => 2],
        ['title' => 'School Software', 'description' => 'Complete management systems for educational institutions.', 'icon_emoji' => '🏫', 'sort_order' => 3],
        ['title' => 'Hospital Software', 'description' => 'Efficient patient and hospital management platforms.', 'icon_emoji' => '🏥', 'sort_order' => 4],
        ['title' => 'Inventory Management', 'description' => 'Track stock levels, orders, and sales effortlessly.', 'icon_emoji' => '📦', 'sort_order' => 5],
        ['title' => 'Library Software', 'description' => 'Organize and automate library operations easily.', 'icon_emoji' => '📚', 'sort_order' => 6],
        ['title' => 'Pharmacy Software', 'description' => 'Manage prescriptions, inventory, and billing seamlessly.', 'icon_emoji' => '💊', 'sort_order' => 7],
        ['title' => 'AI Support System', 'description' => 'Intelligent customer service solutions powered by AI.', 'icon_emoji' => '🤖', 'sort_order' => 8],
        ['title' => 'Appointment Booking Automation', 'description' => 'Streamlined scheduling for your clients and staff.', 'icon_emoji' => '📅', 'sort_order' => 9],
    ];

    $stmt = $conn->prepare('INSERT INTO services (title, description, icon_emoji, sort_order, is_active)
        VALUES (:title, :description, :icon_emoji, :sort_order, 1)');

    foreach ($items as $item) {
        $stmt->execute($item);
    }
}
