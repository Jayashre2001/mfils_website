<?php
/**
 * ════════════════════════════════════════════════════
 * REGISTER.PHP — Changes needed for email integration
 * ════════════════════════════════════════════════════
 * 
 * Add this near the top of register.php (after functions.php include):
 * 
 *   require_once __DIR__ . '/includes/mailer.php';
 * 
 * Then replace the success block:
 * 
 * BEFORE:
 *   if ($result['success']) {
 *       setFlash('success', '🎉 Account created! Please login.');
 *       header('Location: ' . APP_URL . '/login.php'); exit;
 *   }
 * 
 * AFTER (replace with this):
 */

// ── PASTE THIS INTO YOUR register.php ──────────────────────────────────
if ($result['success']) {
    // Get the newly created user for MBPIN + referral code
    $newUser      = getUserByUsername($username);  // add this helper if not exists
    $newMbpin     = $newUser['mbpin']          ?? 'MBP-' . str_pad($newUser['id'] ?? 0, 6, '0', STR_PAD_LEFT);
    $newRefCode   = $newUser['referral_code']  ?? '';

    // Referrer name (for welcome email)
    $referrerName = '';
    if ($ref) {
        $referrer = getUserByReferralCode($ref); // add helper if not exists
        if ($referrer) $referrerName = $referrer['username'];
    }

    // Send welcome email (non-blocking — errors are logged, not shown to user)
    try {
        require_once __DIR__ . '/includes/mailer.php';
        sendWelcomeMail($email, $username, $newMbpin, $newRefCode, $referrerName);
    } catch (Throwable $e) {
        error_log('Welcome email failed: ' . $e->getMessage());
    }

    setFlash('success', '🎉 Account created! Check your email for your MBPIN. Please login.');
    header('Location: ' . APP_URL . '/login.php'); exit;
}
// ── END OF PASTE ────────────────────────────────────────────────────────

/**
 * ════════════════════════════════════════════════════
 * FUNCTIONS.PHP — Helper functions to add if missing
 * ════════════════════════════════════════════════════
 * 
 * Add these to /includes/functions.php if not already present:
 */

// Get user by username
function getUserByUsername(string $username): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Get user by referral code
function getUserByReferralCode(string $code): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE referral_code = ? LIMIT 1");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * ════════════════════════════════════════════════════
 * CONFIG.PHP — SMTP settings to add
 * ════════════════════════════════════════════════════
 * 
 * Add to /includes/config.php:
 * 
 *   // Email / SMTP settings
 *   define('MAIL_HOST',       'smtp.gmail.com');       // or your SMTP host
 *   define('MAIL_PORT',       587);                    // 587 for TLS, 465 for SSL
 *   define('MAIL_ENCRYPTION', 'tls');                  // 'tls' or 'ssl'
 *   define('MAIL_USERNAME',   'your@gmail.com');       // your email
 *   define('MAIL_PASSWORD',   'your_app_password');    // Gmail: use App Password
 *   define('MAIL_FROM',       'noreply@mfills.com');   // from address
 *   define('MAIL_FROM_NAME',  'Mfills Partner Portal');
 * 
 * ════════════════════════════════════════════════════
 * PHPMAILER INSTALL
 * ════════════════════════════════════════════════════
 * 
 * Option 1 — Composer (recommended):
 *   cd /your/project/root
 *   composer require phpmailer/phpmailer
 * 
 * Option 2 — Manual download:
 *   Download from https://github.com/PHPMailer/PHPMailer
 *   Place in /vendor/phpmailer/phpmailer/src/
 *   (PHPMailer.php, SMTP.php, Exception.php)
 * 
 * ════════════════════════════════════════════════════
 * DASHBOARD — Add links to new pages
 * ════════════════════════════════════════════════════
 * 
 * In dashboard.php, add these quick-action links:
 * 
 *   <a href="<?= APP_URL ?>/idcard.php">🪪 Download ID Card</a>
 *   <a href="<?= APP_URL ?>/kyc.php">📋 KYC Verification</a>
 *   <a href="<?= APP_URL ?>/notifications.php">🔔 Notifications</a>
 * 
 * ════════════════════════════════════════════════════
 * DATABASE — Run these SQL statements
 * ════════════════════════════════════════════════════
 */
?>

<!-- SQL to create required tables — run in phpMyAdmin or MySQL CLI -->
<!--

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT DEFAULT NULL COMMENT 'NULL = broadcast to all partners',
  type        ENUM('announcement','commission','kyc','system','promo') DEFAULT 'announcement',
  title       VARCHAR(150) NOT NULL,
  message     TEXT NOT NULL,
  icon        VARCHAR(10)  DEFAULT '📢',
  link_url    VARCHAR(255) DEFAULT NULL,
  link_text   VARCHAR(80)  DEFAULT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  priority    ENUM('low','normal','high','urgent') DEFAULT 'normal',
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  expires_at  DATETIME     DEFAULT NULL,
  INDEX(user_id), INDEX(is_read), INDEX(type)
);

-- KYC submissions table
CREATE TABLE IF NOT EXISTS kyc_submissions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  full_name       VARCHAR(100) NOT NULL,
  dob             DATE NOT NULL,
  gender          ENUM('male','female','other') NOT NULL,
  aadhaar_number  VARCHAR(12) NOT NULL,
  pan_number      VARCHAR(10)  DEFAULT NULL,
  address         TEXT NOT NULL,
  city            VARCHAR(60) NOT NULL,
  state           VARCHAR(60) NOT NULL,
  pincode         VARCHAR(6)  NOT NULL,
  bank_account    VARCHAR(20)  DEFAULT NULL,
  ifsc_code       VARCHAR(11)  DEFAULT NULL,
  bank_name       VARCHAR(80)  DEFAULT NULL,
  aadhaar_front   VARCHAR(255) DEFAULT NULL,
  aadhaar_back    VARCHAR(255) DEFAULT NULL,
  pan_photo       VARCHAR(255) DEFAULT NULL,
  selfie          VARCHAR(255) DEFAULT NULL,
  status          ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_note      TEXT         DEFAULT NULL,
  submitted_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  reviewed_at     DATETIME     DEFAULT NULL,
  INDEX(user_id), INDEX(status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample notifications (optional test data)
INSERT INTO notifications (user_id, type, title, message, icon, priority) VALUES
(NULL, 'announcement', 'Welcome to Mfills Partner Portal!', 
 'We are excited to have you on board. Explore MShop, track your earnings, and grow your network. Check your dashboard for your MBPIN and referral link.', 
 '🎉', 'high'),
(NULL, 'system', 'New Feature: ID Card Download', 
 'You can now download your official Mfills Business Partner ID Card from your dashboard. Visit the ID Card page to download or print your card.',
 '🪪', 'normal'),
(NULL, 'announcement', 'KYC Verification Now Available', 
 'Complete your KYC to enable wallet withdrawals. Submit your Aadhaar, PAN, and bank details through the KYC page in your dashboard.',
 '📋', 'high');

-->