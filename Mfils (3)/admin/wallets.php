<?php
// admin/wallets.php
require_once __DIR__ . '/config.php';
$pageTitle = 'Wallets';
$pdo = db();

$q    = trim($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page']??1));
$lim  = 20; $off = ($page-1)*$lim;

$where  = $q ? "WHERE u.username LIKE ? OR u.email LIKE ?" : '';
$params = $q ? ["%$q%","%$q%"] : [];

$cStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$cStmt->execute($params);
$total = (int)$cStmt->fetchColumn();
$pages = max(1,ceil($total/$lim));

$stmt = $pdo->prepare(
  "SELECT u.id,u.username,u.email,u.wallet,
     (SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id=u.id) as earned,
     (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as orders,
     u.created_at
   FROM users u $where
   ORDER BY u.wallet DESC LIMIT $lim OFFSET $off"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total_wallet = $pdo->query("SELECT COALESCE(SUM(wallet),0) FROM users")->fetchColumn();
$total_earned = $pdo->query("SELECT COALESCE(SUM(commission_amt),0) FROM commissions")->fetchColumn();

require_once '_layout.php';
?>

<div class="stats-row" style="margin-bottom:1.5rem">
  <div class="stat-card" style="border-left-color:var(--gold-d)">
    <div class="sc-icon">💰</div>
    <div class="sc-label">Total Wallet Balances</div>
    <div class="sc-value" style="font-size:1.4rem"><?= inr($total_wallet) ?></div>
    <div class="sc-sub">Across all members</div>
  </div>
  <div class="stat-card" style="border-left-color:var(--success)">
    <div class="sc-icon">💸</div>
    <div class="sc-label">Total Commissions Earned</div>
    <div class="sc-value" style="font-size:1.4rem"><?= inr($total_earned) ?></div>
    <div class="sc-sub">All time</div>
  </div>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem">
  <h2 style="font-size:1.15rem;color:var(--maroon)">Member Wallets</h2>
  <form method="GET" style="display:flex;gap:.6rem">
    <div class="search-box">🔍 <input type="text" name="q" placeholder="Search member…" value="<?= e($q) ?>"></div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if($q): ?><a href="wallets.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<div class="tcard">
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>#</th><th>Member</th><th>Current Balance</th>
      <th>Total Earned (Commissions)</th><th>Total Orders</th><th>Joined</th>
    </tr></thead>
    <tbody>
    <?php if (empty($users)): ?>
      <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--muted)">No wallet data</td></tr>
    <?php else: foreach ($users as $u): ?>
    <tr>
      <td style="color:var(--muted);font-size:.78rem"><?= $u['id'] ?></td>
      <td>
        <div style="font-weight:600"><?= e($u['username']) ?></div>
        <div style="font-size:.75rem;color:var(--muted)"><?= e($u['email']) ?></div>
      </td>
      <td>
        <span style="font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;color:<?= $u['wallet']>0?'var(--gold-d)':'var(--muted)' ?>">
          <?= inr($u['wallet']) ?>
        </span>
      </td>
      <td style="color:var(--success);font-weight:600"><?= inr($u['earned']) ?></td>
      <td style="text-align:center"><span class="badge badge-blue"><?= $u['orders'] ?></span></td>
      <td style="font-size:.78rem;color:var(--muted)"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
  <?php if ($pages>1): ?>
  <div class="pager">
    <span class="pager-info">Showing <?= $off+1 ?>–<?= min($off+$lim,$total) ?> of <?= $total ?></span>
    <?php for($p=1;$p<=$pages;$p++): ?>
      <a href="?page=<?= $p ?>&q=<?= urlencode($q) ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once '_footer.php'; ?>
