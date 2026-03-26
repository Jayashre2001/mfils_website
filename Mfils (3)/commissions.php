<?php
// commissions.php
$pageTitle = 'Commissions – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$userId = currentUserId();
$user   = getUser($userId);
$pdo    = db();

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM commissions WHERE beneficiary_id = ?');
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $limit));

// ✅ FIXED: Fetch o.bv (Business Volume) instead of o.amount for display
$stmt = $pdo->prepare(
    'SELECT c.*, u.username AS buyer, p.name AS product,
            o.amount AS order_amount,
            o.bv    AS order_bv
     FROM commissions c
     JOIN orders   o ON o.id = c.order_id
     JOIN users    u ON u.id = c.buyer_id
     JOIN products p ON p.id = o.product_id
     WHERE c.beneficiary_id = ?
     ORDER BY c.created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->execute([$userId, $limit, $offset]);
$rows = $stmt->fetchAll();

// Summary by level
$sumStmt = $pdo->prepare(
    'SELECT level, COUNT(*) AS cnt, SUM(commission_amt) AS total
     FROM commissions WHERE beneficiary_id = ? GROUP BY level ORDER BY level'
);
$sumStmt->execute([$userId]);
$summary = $sumStmt->fetchAll();

// Lifetime total PSB
$lifetimeStmt = $pdo->prepare('SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id = ?');
$lifetimeStmt->execute([$userId]);
$lifetimeTotal = (float)$lifetimeStmt->fetchColumn();

// This month PSB
$monthStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(commission_amt),0) FROM commissions
     WHERE beneficiary_id = ?
       AND YEAR(created_at)  = YEAR(NOW())
       AND MONTH(created_at) = MONTH(NOW())'
);
$monthStmt->execute([$userId]);
$monthTotal = (float)$monthStmt->fetchColumn();

// Club income total
$clubTotal = 0.0;
try {
    $cs = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM club_income WHERE user_id = ?');
    $cs->execute([$userId]);
    $clubTotal = (float)$cs->fetchColumn();
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<style>
/* ══════════════════════════════════════════════════
   Mfills Commissions Page — Updated Brand
   Forest Green #0e2414 · Gold #c8922a
   Warm Ivory #FDFAF4 · Coral #E8534A · Jade #0F7B5C
══════════════════════════════════════════════════ */
:root {
  --indigo:    #0e2414;
  --indigo-d:  #091a0e;
  --indigo-l:  #1a3d22;
  --indigo-ll: #245c30;
  --gold:      #c8922a;
  --gold-l:    #dba94a;
  --gold-d:    #a87520;
  --ivory:     #FDFAF4;
  --ivory-d:   #F3EDE0;
  --ivory-dd:  #E2D5BC;
  --jade:      #0F7B5C;
  --jade-l:    #13a077;
  --jade-ll:   #1CC48D;
  --coral:     #E8534A;
  --ink:       #0e2414;
  --muted:     #5a7a62;
}

.page-header {
  background: linear-gradient(135deg, var(--indigo-d) 0%, var(--indigo) 55%, var(--indigo-l) 100%);
  padding: 2.5rem 0 4rem;
  position: relative; overflow: hidden;
  border-bottom: 3px solid var(--gold);
}
.page-header::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.12) 1.5px, transparent 1.5px);
  background-size: 24px 24px; pointer-events: none;
}
.ph-arc {
  position: absolute; border-radius: 50%;
  border: 2px solid rgba(200,146,42,.1); pointer-events: none;
}
.ph-arc-1 { width: 340px; height: 240px; bottom: -90px; left: -70px; }
.ph-arc-2 { width: 300px; height: 300px; top: -110px; right: -80px; border-color: rgba(15,123,92,.12); }
.page-header .container { position: relative; z-index: 1; }
.page-header h1 {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 900;
  color: #fff; margin-bottom: .25rem;
}
.page-header p { color: rgba(255,255,255,.55); font-size: .88rem; }

.page-wrap { background: var(--ivory); padding-bottom: 4rem; }

.top-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.1rem;
  margin-top: -1.75rem;
  position: relative; z-index: 2;
  margin-bottom: 2rem;
}
.top-tile {
  background: #fff; border-radius: 14px;
  padding: 1.25rem 1.15rem;
  border: 1.5px solid var(--ivory-dd);
  border-top: 3px solid var(--ivory-dd);
  box-shadow: 0 4px 18px rgba(14,36,20,.07);
  transition: transform .22s, box-shadow .22s;
  display: flex; flex-direction: column; gap: .2rem;
  animation: cardIn .45s ease both;
}
.top-tile:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(14,36,20,.12); }
@keyframes cardIn { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.top-tile.t-gold   { border-top-color: var(--gold); }
.top-tile.t-jade   { border-top-color: var(--jade); }
.top-tile.t-indigo { border-top-color: var(--indigo); }
.top-tile.t-coral  { border-top-color: var(--coral); }

.top-tile-icon {
  width: 34px; height: 34px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; margin-bottom: .35rem;
}
.ti-gold   { background: rgba(200,146,42,.1); }
.ti-jade   { background: rgba(15,123,92,.1); }
.ti-indigo { background: rgba(14,36,20,.08); }
.ti-coral  { background: rgba(232,83,74,.1); }

.top-tile .t-label {
  font-size: .68rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .07em; color: var(--muted);
}
.top-tile .t-value {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1.5rem; font-weight: 700; color: var(--ink); line-height: 1;
  margin: .1rem 0;
}
.top-tile.t-gold   .t-value { color: var(--gold-d); }
.top-tile.t-jade   .t-value { color: var(--jade); }
.top-tile.t-indigo .t-value { color: var(--indigo); }
.top-tile .t-sub { font-size: .73rem; color: var(--muted); }

/* ── PSB Rate info bar ── */
.psb-bar {
  background: linear-gradient(90deg, #eaf2eb, #f5edda, #eaf2eb);
  border: 1.5px solid rgba(14,36,20,.1);
  border-radius: 12px; padding: .85rem 1.25rem;
  display: flex; align-items: center; gap: .75rem;
  flex-wrap: wrap; margin-bottom: 1.75rem;
}
.psb-bar-label {
  font-size: .78rem; font-weight: 700; color: var(--indigo);
  display: flex; align-items: center; gap: .35rem;
}
.psb-pills { display: flex; gap: .3rem; flex-wrap: wrap; margin-left: auto; }
.psb-pill {
  background: rgba(14,36,20,.1); color: var(--indigo);
  border-radius: 20px; padding: .18rem .6rem;
  font-size: .7rem; font-weight: 800; font-family: 'Nunito', sans-serif;
}
.psb-pill.top { background: rgba(200,146,42,.15); color: var(--gold-d); }

/* ── Summary cards grid ── */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
  gap: 1rem; margin-bottom: 2rem;
}
.sum-tile {
  background: #fff; border-radius: 14px;
  padding: 1.2rem 1.1rem;
  border: 1.5px solid var(--ivory-dd);
  border-top: 3px solid var(--ivory-dd);
  box-shadow: 0 3px 14px rgba(14,36,20,.06);
  transition: transform .2s, box-shadow .2s;
  display: flex; flex-direction: column; gap: .2rem;
  animation: cardIn .45s ease both;
}
.sum-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(14,36,20,.1); }
.sum-tile.lvl-1 { border-top-color: var(--gold); }
.sum-tile.lvl-2 { border-top-color: var(--indigo); }
.sum-tile.lvl-3 { border-top-color: var(--jade); }
.sum-tile.lvl-4 { border-top-color: var(--jade-l); }
.sum-tile.lvl-5 { border-top-color: var(--coral); }
.sum-tile.lvl-6, .sum-tile.lvl-7 { border-top-color: var(--muted); }

.sum-tile-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem; margin-bottom: .35rem;
}
.sum-tile.lvl-1 .sum-tile-icon { background: rgba(200,146,42,.12); }
.sum-tile.lvl-2 .sum-tile-icon { background: rgba(14,36,20,.1); }
.sum-tile.lvl-3 .sum-tile-icon { background: rgba(15,123,92,.1); }
.sum-tile.lvl-4 .sum-tile-icon { background: rgba(19,160,119,.1); }
.sum-tile.lvl-5 .sum-tile-icon { background: rgba(232,83,74,.1); }
.sum-tile.lvl-6 .sum-tile-icon,
.sum-tile.lvl-7 .sum-tile-icon { background: rgba(90,122,98,.08); }

.sum-tile .sum-label {
  font-size: .68rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .07em; color: var(--muted);
}
.sum-tile .sum-rate {
  font-size: .7rem; font-weight: 700; margin-bottom: .1rem;
}
.sum-tile.lvl-1 .sum-rate { color: var(--gold-d); }
.sum-tile.lvl-2 .sum-rate { color: var(--indigo); }
.sum-tile.lvl-3 .sum-rate { color: var(--jade); }
.sum-tile.lvl-4 .sum-rate { color: var(--jade-l); }
.sum-tile.lvl-5 .sum-rate { color: var(--coral); }
.sum-tile.lvl-6 .sum-rate,
.sum-tile.lvl-7 .sum-rate { color: var(--muted); }

.sum-tile .sum-value {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1.45rem; font-weight: 700; color: var(--ink); line-height: 1;
  margin: .12rem 0;
}
.sum-tile.lvl-1 .sum-value { color: var(--gold-d); }
.sum-tile.lvl-2 .sum-value { color: var(--indigo); }
.sum-tile.lvl-3 .sum-value { color: var(--jade); }
.sum-tile .sum-sub { font-size: .73rem; color: var(--muted); }

.section-title {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1.15rem; font-weight: 700; color: var(--indigo);
  display: flex; align-items: center; gap: .75rem; margin: 0 0 1rem;
}
.section-title::after {
  content: ''; flex: 1; height: 1.5px;
  background: var(--ivory-dd); border-radius: 2px;
}

.card {
  background: #fff; border-radius: 16px;
  border: 1.5px solid var(--ivory-dd);
  box-shadow: 0 2px 12px rgba(14,36,20,.06);
  overflow: hidden;
}
.table-wrap { overflow-x: auto; }
.table-wrap table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.table-wrap thead th {
  padding: .7rem 1rem; text-align: left;
  font-size: .68rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .08em;
  color: var(--muted); background: var(--ivory-d);
  border-bottom: 1.5px solid var(--ivory-dd);
  white-space: nowrap;
}
.table-wrap tbody tr { border-bottom: 1px solid var(--ivory-d); transition: background .15s; }
.table-wrap tbody tr:last-child { border-bottom: none; }
.table-wrap tbody tr:hover { background: var(--ivory-d); }
.table-wrap tbody td { padding: .8rem 1rem; vertical-align: middle; color: var(--ink); }

.buyer-cell { display: flex; align-items: center; gap: .55rem; }
.buyer-avatar {
  width: 28px; height: 28px; border-radius: 8px;
  background: rgba(14,36,20,.08); border: 1.5px solid var(--ivory-dd);
  display: flex; align-items: center; justify-content: center;
  font-size: .72rem; font-weight: 800; color: var(--indigo);
  flex-shrink: 0; text-transform: uppercase;
}

.lvl-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 52px; padding: .2rem .5rem;
  border-radius: 20px; font-size: .7rem; font-weight: 800;
}
.lvl-badge.lvl-1 { background: rgba(200,146,42,.15); color: var(--gold-d); }
.lvl-badge.lvl-2 { background: rgba(14,36,20,.13);   color: var(--indigo); }
.lvl-badge.lvl-3 { background: rgba(15,123,92,.12);  color: var(--jade); }
.lvl-badge.lvl-4 { background: rgba(19,160,119,.1);  color: var(--jade-l); }
.lvl-badge.lvl-5 { background: rgba(232,83,74,.1);   color: var(--coral); }
.lvl-badge.lvl-6,
.lvl-badge.lvl-7 { background: rgba(90,122,98,.1);   color: var(--muted); }

.rate-pill {
  display: inline-block; border-radius: 20px;
  font-size: .72rem; font-weight: 800; padding: .15rem .6rem;
  background: rgba(14,36,20,.09); color: var(--indigo);
}
.rate-pill.top { background: rgba(200,146,42,.15); color: var(--gold-d); }

.comm-amt {
  font-weight: 800; color: var(--jade);
  font-size: .95rem;
}
.comm-amt::before { content: '+'; }

/* ✅ BV amount styling */
.bv-amt {
  font-size: .88rem; color: var(--indigo); font-weight: 700;
}
/* Price shown as small secondary below BV */
.price-sub {
  font-size: .72rem; color: var(--muted);
  display: block; margin-top: .1rem;
}

.status-pill {
  font-size: .68rem; font-weight: 800; border-radius: 20px; padding: .15rem .6rem; display: inline-block;
}
.status-credited { background: rgba(15,123,92,.12); color: var(--jade); }
.status-pending  { background: rgba(200,146,42,.12); color: var(--gold-d); }

.row-num { color: var(--muted); font-size: .78rem; }
.date-cell { color: var(--muted); font-size: .8rem; white-space: nowrap; line-height: 1.5; }

/* ── Pagination ── */
.pagination {
  padding: 1rem 1.25rem;
  display: flex; gap: .4rem; flex-wrap: wrap;
  border-top: 1.5px solid var(--ivory-dd);
  background: var(--ivory-d);
  align-items: center;
}
.page-info { margin-left: auto; font-size: .78rem; color: var(--muted); }
.page-btn {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 34px; height: 34px; padding: 0 .55rem;
  border-radius: 8px; font-size: .82rem; font-weight: 700;
  text-decoration: none; border: 1.5px solid var(--ivory-dd);
  background: #fff; color: var(--muted); transition: all .18s;
  font-family: 'Nunito', sans-serif;
}
.page-btn:hover { border-color: var(--indigo-l); color: var(--indigo); background: var(--ivory-d); }
.page-btn.active { background: var(--indigo); border-color: var(--indigo); color: #fff; box-shadow: 0 2px 8px rgba(14,36,20,.25); }

.empty-state {
  text-align: center; padding: 4rem 2rem; color: var(--muted);
  background: #fff; border-radius: 16px;
  border: 1.5px solid var(--ivory-dd);
}
.empty-state .ei { font-size: 3.5rem; opacity: .3; display: block; margin-bottom: .85rem; }
.empty-state h3 {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1.2rem; color: var(--indigo); margin-bottom: .4rem;
}
.empty-state p { font-size: .875rem; line-height: 1.7; margin-bottom: 1rem; }

.empty-cta {
  display: inline-flex; align-items: center; gap: .4rem;
  background: linear-gradient(135deg, var(--gold), var(--gold-l));
  color: #fff; font-weight: 800;
  font-family: 'Nunito', sans-serif; font-size: .875rem;
  padding: .55rem 1.4rem; border-radius: 50px; text-decoration: none;
  box-shadow: 0 4px 14px rgba(200,146,42,.35); transition: all .2s;
  animation: pulse-gold 2.4s ease-in-out infinite;
}
.empty-cta:hover { transform: translateY(-1px); color: #fff; box-shadow: 0 6px 20px rgba(200,146,42,.45); animation: none; }
@keyframes pulse-gold {
  0%, 100% { box-shadow: 0 4px 14px rgba(200,146,42,.35); }
  50%       { box-shadow: 0 4px 22px rgba(200,146,42,.6); }
}

/* ── Responsive ── */
@media (max-width: 900px) {
  .top-stats    { grid-template-columns: repeat(2, 1fr); }
  .summary-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 640px) {
  .top-stats    { grid-template-columns: repeat(2, 1fr); gap: .75rem; }
  .summary-grid { grid-template-columns: repeat(2, 1fr); gap: .75rem; }
  .page-header  { padding: 2rem 0 3rem; }
  .psb-bar      { flex-direction: column; align-items: flex-start; gap: .5rem; }
  .psb-pills    { margin-left: 0; }

  .table-wrap thead { display: none; }
  .table-wrap tbody tr {
    display: block; padding: .85rem 1rem;
    border-bottom: 1px solid var(--ivory-d);
  }
  .table-wrap tbody tr:last-child { border-bottom: none; }
  .table-wrap tbody td {
    display: flex; justify-content: space-between;
    align-items: center; padding: .22rem 0;
    border: none; font-size: .85rem;
  }
  .table-wrap tbody td::before {
    content: attr(data-label);
    font-size: .68rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--muted); flex-shrink: 0; margin-right: .5rem;
  }
  .table-wrap tbody td.date-cell {
    font-size: .75rem; padding-bottom: .4rem; margin-bottom: .25rem;
    border-bottom: 1px dashed var(--ivory-dd); justify-content: flex-start;
  }
  .table-wrap tbody td.date-cell::before { display: none; }
  .table-wrap tbody td.row-num-cell { display: none; }
}
@media (max-width: 420px) {
  .top-stats    { grid-template-columns: 1fr 1fr; }
  .summary-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<!-- ── Page Header ── -->
<div class="page-header">
  <div class="ph-arc ph-arc-1"></div>
  <div class="ph-arc ph-arc-2"></div>
  <div class="container">
    <h1>💰 Partner Sales Bonus (PSB)</h1>
    <p>Your earnings from 7-level network purchases · Commission calculated on BV (Business Volume)</p>
  </div>
</div>

<div class="container page-wrap" style="padding-top:0">

  <!-- ── Top stat tiles ── -->
  <div class="top-stats">
    <div class="top-tile t-gold" style="animation-delay:.04s">
      <div class="top-tile-icon ti-gold">💰</div>
      <div class="t-label">Lifetime PSB</div>
      <div class="t-value">₹<?= number_format($lifetimeTotal, 2) ?></div>
      <div class="t-sub">Total earned ever</div>
    </div>
    <div class="top-tile t-jade" style="animation-delay:.08s">
      <div class="top-tile-icon ti-jade">📅</div>
      <div class="t-label">This Month PSB</div>
      <div class="t-value">₹<?= number_format($monthTotal, 2) ?></div>
      <div class="t-sub"><?= date('F Y') ?></div>
    </div>
    <div class="top-tile t-indigo" style="animation-delay:.12s">
      <div class="top-tile-icon ti-indigo">📊</div>
      <div class="t-label">Total Transactions</div>
      <div class="t-value"><?= number_format($total) ?></div>
      <div class="t-sub">All levels combined</div>
    </div>
    <div class="top-tile t-coral" style="animation-delay:.16s">
      <div class="top-tile-icon" style="background:rgba(15,123,92,.1)">👑</div>
      <div class="t-label">Club Income</div>
      <div class="t-value">₹<?= number_format($clubTotal, 2) ?></div>
      <div class="t-sub">Leadership pool share</div>
    </div>
  </div>

  <!-- ── PSB Rate info bar ── -->
  <div class="psb-bar">
    <div class="psb-bar-label">💡 PSB calculated on BV (Business Volume) · Not on selling price · Total pool: 40%</div>
    <div class="psb-pills">
      <?php
      $rates = COMMISSION_RATES;
      foreach ($rates as $l => $r):
      ?>
        <span class="psb-pill <?= $l === 1 ? 'top' : '' ?>">L<?= $l ?>·<?= $r ?>%</span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── PSB by level summary ── -->
  <?php if (!empty($summary)): ?>
  <div class="section-title">📊 PSB by Level</div>
  <div class="summary-grid">
    <?php
    $icons = ['1'=>'🥇','2'=>'🥈','3'=>'🥉','4'=>'4️⃣','5'=>'5️⃣','6'=>'6️⃣','7'=>'7️⃣'];
    foreach ($summary as $i => $s):
      $lvl = (int)$s['level'];
    ?>
    <div class="sum-tile lvl-<?= $lvl ?>" style="animation-delay:<?= ($i + 5) * .06 ?>s">
      <div class="sum-tile-icon"><?= $icons[$lvl] ?? '💊' ?></div>
      <div class="sum-label">Level <?= $lvl ?></div>
      <div class="sum-rate"><?= $rates[$lvl] ?? '?' ?>% on BV</div>
      <div class="sum-value">₹<?= number_format((float)$s['total'], 2) ?></div>
      <div class="sum-sub"><?= $s['cnt'] ?> transaction<?= $s['cnt'] != 1 ? 's' : '' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Transaction history ── -->
  <?php if (!empty($rows)): ?>
  <div class="section-title">⚡ Transaction History</div>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Buyer</th>
            <th>Product</th>
            <th>BV</th><!-- ✅ Changed from "Order Amt" to "BV" -->
            <th>Level</th>
            <th>PSB Rate</th>
            <th>PSB Earned</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="row-num-cell" data-label="#">
            <span class="row-num"><?= $offset + $i + 1 ?></span>
          </td>
          <td class="date-cell">
            <?= date('d M Y', strtotime($r['created_at'])) ?><br>
            <span style="font-size:.7rem"><?= date('H:i', strtotime($r['created_at'])) ?></span>
          </td>
          <td data-label="Buyer">
            <div class="buyer-cell">
              <div class="buyer-avatar"><?= strtoupper(substr($r['buyer'], 0, 1)) ?></div>
              <strong><?= e($r['buyer']) ?></strong>
            </div>
          </td>
          <td data-label="Product" style="font-size:.83rem"><?= e($r['product']) ?></td>
          <td data-label="BV">
            <!-- ✅ Show BV as primary, selling price as small secondary -->
            <span class="bv-amt">₹<?= number_format($r['order_bv'], 2) ?></span>
            <span class="price-sub">Price: ₹<?= number_format($r['order_amount'], 2) ?></span>
          </td>
          <td data-label="Level">
            <span class="lvl-badge lvl-<?= (int)$r['level'] ?>">Lvl <?= $r['level'] ?></span>
          </td>
          <td data-label="PSB Rate">
            <span class="rate-pill <?= $r['level'] == 1 ? 'top' : '' ?>"><?= $r['rate'] ?>%</span>
          </td>
          <td data-label="PSB Earned">
            <span class="comm-amt">₹<?= number_format($r['commission_amt'], 2) ?></span>
          </td>
          <td data-label="Status">
            <span class="status-pill status-<?= e($r['status']) ?>"><?= ucfirst($r['status']) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="page-btn">‹</a>
      <?php endif; ?>
      <?php
      $start = max(1, $page - 2);
      $end   = min($pages, $page + 2);
      if ($start > 1) echo '<span class="page-btn" style="border:none;background:none;cursor:default">…</span>';
      for ($pg = $start; $pg <= $end; $pg++):
      ?>
        <a href="?page=<?= $pg ?>" class="page-btn <?= $pg === $page ? 'active' : '' ?>"><?= $pg ?></a>
      <?php endfor;
      if ($end < $pages) echo '<span class="page-btn" style="border:none;background:none;cursor:default">…</span>';
      ?>
      <?php if ($page < $pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="page-btn">›</a>
      <?php endif; ?>
      <span class="page-info">Page <?= $page ?> of <?= $pages ?> · <?= $total ?> records</span>
    </div>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div class="empty-state">
    <span class="ei">💰</span>
    <h3>No PSB earned yet</h3>
    <p>Share your referral link and grow your network.<br>
       Every purchase in your 7-level network automatically credits<br>
       Partner Sales Bonus (PSB) to your wallet!</p>
    <a href="dashboard.php" class="empty-cta">🔗 Go to Dashboard &amp; Share Link</a>
  </div>
  <?php endif; ?>

</div><!-- /.container -->

<?php include __DIR__ . '/includes/footer.php'; ?>