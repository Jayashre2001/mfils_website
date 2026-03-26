<?php
// admin/settings.php
require_once __DIR__ . '/config.php';
$pageTitle = 'Settings';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new']     ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if ($current !== ADMIN_PASSWORD) {
            setAdminFlash('error', 'Current password is wrong.');
        } elseif (strlen($new) < 6) {
            setAdminFlash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            setAdminFlash('error', 'Passwords do not match.');
        } else {
            // Update config.php
            $configPath = __DIR__ . '/../includes/config.php';
            $content = file_get_contents($configPath);
            $content = preg_replace(
                "/define\s*\(\s*'ADMIN_PASSWORD'\s*,\s*'[^']*'\s*\)/",
                "define('ADMIN_PASSWORD', '$new')",
                $content
            );
            file_put_contents($configPath, $content);
            setAdminFlash('success', 'Password updated. Please login again.');
            adminLogout();
        }
        header('Location: settings.php'); exit;
    }
}

// System info
$info = [
    'PHP Version'        => phpversion(),
    'MySQL Version'      => $pdo->query("SELECT VERSION()")->fetchColumn(),
    'App Name'           => APP_NAME,
    'App URL'            => APP_URL,
    'DB Name'            => DB_NAME,
    'Total Members'      => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'Total Orders'       => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'Total Products'     => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'Server Time (IST)'  => date('d M Y, H:i:s'),
];

require_once '_layout.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

<!-- Change Password -->
<div class="tcard" style="padding:1.5rem">
  <h3 style="font-size:1rem;color:var(--maroon);margin-bottom:1.25rem">🔐 Change Admin Password</h3>
  <form method="POST">
    <input type="hidden" name="action" value="change_password">
    <div class="f-group">
      <label class="f-label">Current Password</label>
      <input type="password" name="current" class="f-control" required>
    </div>
    <div class="f-group">
      <label class="f-label">New Password</label>
      <input type="password" name="new" class="f-control" minlength="6" required>
    </div>
    <div class="f-group">
      <label class="f-label">Confirm New Password</label>
      <input type="password" name="confirm" class="f-control" required>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:.5rem">Update Password</button>
  </form>
</div>

<!-- System Info -->
<div class="tcard" style="padding:1.5rem">
  <h3 style="font-size:1rem;color:var(--maroon);margin-bottom:1.25rem">🖥️ System Information</h3>
  <div style="display:grid;gap:.6rem">
    <?php foreach ($info as $k => $v): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .9rem;background:var(--cream);border-radius:6px;font-size:.85rem">
      <span style="color:var(--muted);font-weight:600"><?= $k ?></span>
      <span style="font-weight:500;text-align:right;max-width:60%"><?= e((string)$v) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Quick Links -->
<div class="tcard" style="padding:1.5rem">
  <h3 style="font-size:1rem;color:var(--maroon);margin-bottom:1.25rem">🔗 Quick Actions</h3>
  <div style="display:grid;gap:.75rem">
    <a href="<?= APP_URL ?>" target="_blank" class="btn btn-gold">🌐 Visit Main Site</a>
    <a href="commission_rates.php" class="btn btn-primary">⚙️ Edit Commission Rates</a>
    <a href="users.php" class="btn btn-outline">👥 Manage Members</a>
    <a href="withdrawals.php" class="btn btn-outline">🏦 Review Withdrawals</a>
    <a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-danger" onclick="return confirm('Logout?')">🚪 Logout</a>
  </div>
</div>

<!-- Credentials reminder -->
<div class="tcard" style="padding:1.5rem">
  <h3 style="font-size:1rem;color:var(--maroon);margin-bottom:1.25rem">⚠️ Config Reminder</h3>
  <div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:1rem;font-size:.85rem;color:#92400E">
    <p style="margin-bottom:.5rem"><strong>Admin credentials</strong> are stored in <code>includes/config.php</code>:</p>
    <ul style="padding-left:1.25rem;line-height:1.9">
      <li><code>ADMIN_USERNAME</code> – current: <strong><?= e(ADMIN_USERNAME) ?></strong></li>
      <li><code>ADMIN_PASSWORD</code> – change via form on left</li>
      <li><code>APP_URL</code> – current: <strong><?= e(APP_URL) ?></strong></li>
    </ul>
    <p style="margin-top:.5rem">For production, use a strong password and consider moving credentials to environment variables.</p>
  </div>
</div>

</div>

<?php require_once '_footer.php'; ?>
