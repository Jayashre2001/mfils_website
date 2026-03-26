<?php
// ==========================================================
// PSB Weekly Transfer Reminder — Cron Script
// Schedule: Every Saturday at 9:00 AM
// Crontab:  0 9 * * 6 /usr/bin/php /var/www/html/admin/psb_weekly_reminder.php
// ==========================================================

// ── Bootstrap (adjust path if needed) ──
require_once __DIR__ . '/../includes/functions.php';
$pdo = db();

// ── Only run on Saturday (6 = Saturday in PHP date('w')) ──
// Remove this check if you want to test manually
if (php_sapi_name() !== 'cli') {
    // If called via browser (not CLI), only allow on Saturdays
    if (date('w') !== '6') {
        die('This script runs only on Saturdays.');
    }
}

// ── Check if reminder already sent today (avoid duplicate emails) ──
$today = date('Y-m-d');
$already = $pdo->prepare("SELECT id FROM psb_reminder_log WHERE sent_date = ? AND status = 'sent'");
$already->execute([$today]);
if ($already->fetch()) {
    echo "[SKIP] Reminder already sent today ($today).\n";
    exit;
}

// ── Fetch PSB summary data ──

// Total pending PSB = total commissions NOT yet transferred (status != 'credited')
$pending_amount = $pdo->query(
    "SELECT COALESCE(SUM(commission_amt), 0) FROM commissions WHERE status != 'credited'"
)->fetchColumn();

$pending_count = $pdo->query(
    "SELECT COUNT(*) FROM commissions WHERE status != 'credited'"
)->fetchColumn();

// Per-level breakdown
$level_breakdown = $pdo->query(
    "SELECT level, COUNT(*) as cnt, COALESCE(SUM(commission_amt), 0) as total
     FROM commissions
     WHERE status != 'credited'
     GROUP BY level ORDER BY level"
)->fetchAll(PDO::FETCH_ASSOC);

// Total wallet balance across all users
$total_wallet = $pdo->query(
    "SELECT COALESCE(SUM(wallet), 0) FROM users"
)->fetchColumn();

// New commissions earned this week
$week_amount = $pdo->query(
    "SELECT COALESCE(SUM(commission_amt), 0) FROM commissions
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

$week_count = $pdo->query(
    "SELECT COUNT(*) FROM commissions
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

// Top earners this week
$top_earners = $pdo->query(
    "SELECT u.username, u.email, SUM(c.commission_amt) as earned, COUNT(c.id) as txns
     FROM commissions c
     JOIN users u ON u.id = c.beneficiary_id
     WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY c.beneficiary_id
     ORDER BY earned DESC
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Admin email (set your admin email below) ──
$admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@mfills.com';
$app_url     = defined('APP_URL')     ? APP_URL     : 'http://localhost';
$app_name    = 'Mfills Admin Panel';

// ── Build level breakdown rows ──
$level_rows_html = '';
$level_rows_text = '';
$level_names = [
    1 => 'Level 1 – Direct (15%)',
    2 => 'Level 2 (8%)',
    3 => 'Level 3 (6%)',
    4 => 'Level 4 (4%)',
    5 => 'Level 5 (3%)',
    6 => 'Level 6 (2%)',
    7 => 'Level 7 (2%)',
];
foreach ($level_breakdown as $lvl) {
    $name  = $level_names[$lvl['level']] ?? 'Level ' . $lvl['level'];
    $amt   = '₹' . number_format($lvl['total'], 2);
    $count = number_format($lvl['cnt']);
    $level_rows_html .= "
        <tr>
            <td style='padding:10px 16px;border-bottom:1px solid #e8f0e9;font-weight:600;color:#1C3D24'>$name</td>
            <td style='padding:10px 16px;border-bottom:1px solid #e8f0e9;text-align:center;color:#6b8a72'>$count txns</td>
            <td style='padding:10px 16px;border-bottom:1px solid #e8f0e9;text-align:right;font-weight:700;color:#C8922A;font-size:1.05rem'>$amt</td>
        </tr>";
    $level_rows_text .= "  $name: $count txns → $amt\n";
}

// ── Top earners rows ──
$earner_rows_html = '';
foreach ($top_earners as $i => $e) {
    $medal = ['🥇','🥈','🥉','4.','5.'][$i] ?? ($i + 1) . '.';
    $earned = '₹' . number_format($e['earned'], 2);
    $earner_rows_html .= "
        <tr>
            <td style='padding:8px 16px;border-bottom:1px solid #e8f0e9;font-size:1.1rem'>$medal</td>
            <td style='padding:8px 16px;border-bottom:1px solid #e8f0e9;font-weight:600;color:#1C3D24'>{$e['username']}</td>
            <td style='padding:8px 16px;border-bottom:1px solid #e8f0e9;color:#6b8a72;font-size:.85rem'>{$e['email']}</td>
            <td style='padding:8px 16px;border-bottom:1px solid #e8f0e9;text-align:right;font-weight:700;color:#2a5c34'>$earned</td>
        </tr>";
}
if (empty($top_earners)) {
    $earner_rows_html = "<tr><td colspan='4' style='padding:16px;text-align:center;color:#6b8a72'>No new commissions this week</td></tr>";
}

// ── Format amounts ──
$fmt_pending   = '₹' . number_format($pending_amount, 2);
$fmt_wallet    = '₹' . number_format($total_wallet,   2);
$fmt_week      = '₹' . number_format($week_amount,    2);
$date_display  = date('l, d F Y');
$week_range    = date('d M', strtotime('-6 days')) . ' – ' . date('d M Y');

// ── Build HTML Email ──
$html_body = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f7f4;font-family:'Outfit',Arial,sans-serif">
  <div style="max-width:620px;margin:30px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(14,36,20,.10)">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#0e2414,#2a5c34);padding:32px 32px 24px;text-align:center">
      <div style="font-size:2rem;margin-bottom:8px">🔔</div>
      <h1 style="margin:0;font-size:1.5rem;color:#fff;font-weight:800;letter-spacing:-.01em">
        Weekly PSB Transfer Reminder
      </h1>
      <p style="margin:8px 0 0;color:#a8d4b0;font-size:.9rem">$date_display</p>
    </div>

    <!-- Alert Box -->
    <div style="background:#fffbf0;border-left:4px solid #C8922A;margin:24px 24px 0;padding:16px 20px;border-radius:0 8px 8px 0">
      <p style="margin:0;font-size:1rem;color:#7a5a10;font-weight:600">
        ⏰ Action Required: Transfer pending PSB to partner wallets
      </p>
      <p style="margin:6px 0 0;font-size:.85rem;color:#a07830">
        Please review and process PSB transfers before end of day today.
      </p>
    </div>

    <!-- Main Stats -->
    <div style="padding:24px">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">

        <div style="background:#fef9ee;border:1.5px solid #f0d890;border-radius:12px;padding:16px;text-align:center">
          <div style="font-size:1.5rem;margin-bottom:4px">💸</div>
          <div style="font-size:1.3rem;font-weight:800;color:#C8922A">$fmt_pending</div>
          <div style="font-size:.72rem;color:#a07830;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:4px">Pending PSB</div>
          <div style="font-size:.75rem;color:#b89040;margin-top:2px">$pending_count transactions</div>
        </div>

        <div style="background:#f0faf2;border:1.5px solid #b3e0bb;border-radius:12px;padding:16px;text-align:center">
          <div style="font-size:1.5rem;margin-bottom:4px">💰</div>
          <div style="font-size:1.3rem;font-weight:800;color:#2a5c34">$fmt_wallet</div>
          <div style="font-size:.72rem;color:#3a7a44;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:4px">Total Wallets</div>
          <div style="font-size:.75rem;color:#5a9a64;margin-top:2px">Across all members</div>
        </div>

        <div style="background:#f2f6ff;border:1.5px solid #b0c8f8;border-radius:12px;padding:16px;text-align:center">
          <div style="font-size:1.5rem;margin-bottom:4px">📅</div>
          <div style="font-size:1.3rem;font-weight:800;color:#1a4fa8">$fmt_week</div>
          <div style="font-size:.72rem;color:#2a5fb8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:4px">This Week's PSB</div>
          <div style="font-size:.75rem;color:#4a7fd8;margin-top:2px">$week_range</div>
        </div>

      </div>

      <!-- Level Breakdown -->
      <h3 style="margin:0 0 12px;font-size:1rem;color:#1C3D24;font-weight:700">📊 Pending PSB by Level</h3>
      <table style="width:100%;border-collapse:collapse;border:1px solid #dde8de;border-radius:10px;overflow:hidden">
        <thead>
          <tr style="background:#f0f7f1">
            <th style="padding:10px 16px;text-align:left;font-size:.78rem;color:#6b8a72;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Level</th>
            <th style="padding:10px 16px;text-align:center;font-size:.78rem;color:#6b8a72;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Transactions</th>
            <th style="padding:10px 16px;text-align:right;font-size:.78rem;color:#6b8a72;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Amount</th>
          </tr>
        </thead>
        <tbody>
          $level_rows_html
          <tr style="background:#fafdf8">
            <td style="padding:12px 16px;font-weight:800;color:#1C3D24">TOTAL</td>
            <td style="padding:12px 16px;text-align:center;font-weight:700;color:#1C3D24">$pending_count txns</td>
            <td style="padding:12px 16px;text-align:right;font-weight:800;color:#C8922A;font-size:1.1rem">$fmt_pending</td>
          </tr>
        </tbody>
      </table>

      <!-- Top Earners This Week -->
      <h3 style="margin:24px 0 12px;font-size:1rem;color:#1C3D24;font-weight:700">🏆 Top Earners This Week</h3>
      <table style="width:100%;border-collapse:collapse;border:1px solid #dde8de;border-radius:10px;overflow:hidden">
        <thead>
          <tr style="background:#f0f7f1">
            <th style="padding:8px 16px;text-align:left;font-size:.78rem;color:#6b8a72;font-weight:700">#</th>
            <th style="padding:8px 16px;text-align:left;font-size:.78rem;color:#6b8a72;font-weight:700">Partner</th>
            <th style="padding:8px 16px;text-align:left;font-size:.78rem;color:#6b8a72;font-weight:700">Email</th>
            <th style="padding:8px 16px;text-align:right;font-size:.78rem;color:#6b8a72;font-weight:700">Earned</th>
          </tr>
        </thead>
        <tbody>
          $earner_rows_html
        </tbody>
      </table>

      <!-- CTA Button -->
      <div style="text-align:center;margin:28px 0 8px">
        <a href="$app_url/admin/commissions.php"
           style="display:inline-block;background:linear-gradient(135deg,#2a5c34,#3a8a4a);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:1rem;letter-spacing:.02em">
          👉 Open Commissions Panel →
        </a>
      </div>
      <p style="text-align:center;font-size:.8rem;color:#9aaa9a;margin-top:12px">
        Or go to: <a href="$app_url/admin/wallets.php" style="color:#2a5c34">Wallets Page</a>
      </p>
    </div>

    <!-- Footer -->
    <div style="background:#f4f7f4;padding:20px 32px;text-align:center;border-top:1px solid #dde8de">
      <p style="margin:0;font-size:.78rem;color:#8a9a8a">
        This is an automated weekly reminder from <strong>$app_name</strong>.<br>
        Sent every Saturday · <a href="$app_url/admin/" style="color:#2a5c34">Go to Dashboard</a>
      </p>
    </div>

  </div>
</body>
</html>
HTML;

// ── Plain text version ──
$text_body = <<<TEXT
=== WEEKLY PSB TRANSFER REMINDER ===
$date_display

ACTION REQUIRED: Transfer pending PSB to partner wallets.

SUMMARY
-------
Pending PSB Amount : $fmt_pending ($pending_count transactions)
Total Wallet Balance: $fmt_wallet
This Week's PSB    : $fmt_week ($week_count txns, $week_range)

PENDING PSB BY LEVEL
--------------------
$level_rows_text
TOTAL: $fmt_pending

Go to Commissions Panel: $app_url/admin/commissions.php
Go to Wallets Page     : $app_url/admin/wallets.php
Dashboard              : $app_url/admin/

---
Automated reminder from $app_name · Runs every Saturday
TEXT;

// ── Send Email (PHP mail) ──
$subject = "⏰ [Saturday Reminder] PSB Transfer Pending – $fmt_pending | $app_name";
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: $app_name <noreply@mfills.com>\r\n";
$headers .= "Reply-To: $admin_email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mail_sent = mail($admin_email, $subject, $html_body, $headers);

// ── Log reminder in DB ──
$pdo->prepare(
    "INSERT INTO psb_reminder_log (sent_date, pending_amount, pending_count, admin_email, status, created_at)
     VALUES (?, ?, ?, ?, ?, NOW())"
)->execute([
    $today,
    $pending_amount,
    $pending_count,
    $admin_email,
    $mail_sent ? 'sent' : 'failed'
]);

// ── Output (visible in cron logs) ──
if ($mail_sent) {
    echo "[OK] PSB reminder sent to $admin_email on $today.\n";
    echo "[OK] Pending PSB: $fmt_pending ($pending_count transactions)\n";
} else {
    echo "[ERROR] Failed to send email to $admin_email.\n";
    echo "[INFO] Check your PHP mail() configuration (SMTP/sendmail).\n";
}
