<?php
// admin/commissions.php
require_once __DIR__ . '/config.php';
$pageTitle = 'Commissions';
$pdo = db();

$page = max(1,(int)($_GET['page']??1));
$lim  = 25; $off = ($page-1)*$lim;

$total = (int)$pdo->query("SELECT COUNT(*) FROM commissions")->fetchColumn();
$pages = max(1,ceil($total/$lim));

$rows = $pdo->prepare(
  "SELECT c.*,
     b.username as beneficiary,
     buyer.username as buyer_name,
     p.name as product_name,
     o.amount as order_amount
   FROM commissions c
   JOIN users b    ON b.id    = c.beneficiary_id
   JOIN users buyer ON buyer.id = c.buyer_id
   JOIN orders o   ON o.id    = c.order_id
   JOIN products p ON p.id    = o.product_id
   ORDER BY c.created_at DESC LIMIT $lim OFFSET $off"
);
$rows->execute();
$comms = $rows->fetchAll();

// Level summary
$lvl = $pdo->query(
  "SELECT level, COUNT(*) as cnt, SUM(commission_amt) as total, AVG(rate) as avg_rate
   FROM commissions GROUP BY level ORDER BY level"
)->fetchAll();

// Total paid
$total_paid = $pdo->query("SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE status='credited'")->fetchColumn();

require_once '_layout.php';
?>

<div class="stats-row">
  <div class="stat-card" style="border-left-color:var(--gold-d)">
    <div class="sc-icon">💸</div>
    <div class="sc-label">Total Commissions</div>
    <div class="sc-value" style="font-size:1.4rem"><?= inr($total_paid) ?></div>
    <div class="sc-sub"><?= number_format($total) ?> transactions</div>
  </div>
  <?php foreach ($lvl as $l): ?>
  <div class="stat-card" style="border-left-color:<?= $l['level']==1?'var(--gold-d)':'var(--maroon)' ?>">
    <div class="sc-label">Level <?= $l['level'] ?> (<?= pct($l['avg_rate']) ?>)</div>
    <div class="sc-value" style="font-size:1.3rem"><?= inr($l['total']) ?></div>
    <div class="sc-sub"><?= number_format($l['cnt']) ?> txns</div>
  </div>
  <?php endforeach; ?>
</div>

<div class="tcard">
  <div class="tcard-head">
    <h3>Commission History</h3>
    <span style="font-size:.82rem;color:var(--muted)"><?= number_format($total) ?> total records</span>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>#</th><th>Earner</th><th>From (Buyer)</th><th>Product</th>
      <th>Level</th><th>Rate</th><th>Commission</th><th>Order Value</th><th>Date</th>
    </tr></thead>
    <tbody>
    <?php if (empty($comms)): ?>
      <tr><td colspan="9" style="text-align:center;padding:2.5rem;color:var(--muted)">No commissions yet</td></tr>
    <?php else: foreach ($comms as $c): ?>
    <tr>
      <td style="color:var(--muted);font-size:.78rem"><?= $c['id'] ?></td>
      <td style="font-weight:600"><?= e($c['beneficiary']) ?></td>
      <td><?= e($c['buyer_name']) ?></td>
      <td style="font-size:.82rem;color:var(--muted)"><?= e($c['product_name']) ?></td>
      <td><span class="badge badge-blue">L<?= $c['level'] ?></span></td>
      <td><span class="badge badge-gold"><?= pct($c['rate']) ?></span></td>
      <td style="font-weight:700;color:var(--success);font-size:1rem"><?= inr($c['commission_amt']) ?></td>
      <td style="color:var(--muted)"><?= inr($c['order_amount']) ?></td>
      <td style="font-size:.78rem;color:var(--muted);white-space:nowrap"><?= date('d M Y, h:i A',strtotime($c['created_at'])) ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
  <?php if ($pages>1): ?>
  <div class="pager">
    <span class="pager-info">Page <?= $page ?> of <?= $pages ?></span>
    <?php for($p=1;$p<=$pages;$p++): ?>
      <a href="?page=<?= $p ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once '_footer.php'; ?>
