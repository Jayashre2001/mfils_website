<?php
// admin/orders.php
require_once __DIR__ . '/config.php';
$pageTitle = 'Orders';
$pdo = db();

// Read filter first (needed in POST redirect too)
$filter = trim($_GET['status'] ?? '');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid     = (int)($_POST['oid'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['pending', 'completed', 'cancelled'];

    if ($oid > 0 && in_array($status, $allowed)) {

        // Map admin status → customer-facing delivery_status
        $deliveryStatusMap = [
            'pending'   => 'processing',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
        ];
        $deliveryStatus = $deliveryStatusMap[$status] ?? 'processing';

        $pdo->prepare("UPDATE orders SET status=?, delivery_status=? WHERE id=?")
            ->execute([$status, $deliveryStatus, $oid]);

        setAdminFlash('success', "Order #$oid status updated to " . ucfirst($status) . ".");
    } else {
        setAdminFlash('error', 'Invalid request.');
    }

    header('Location: orders.php' . ($filter ? '?status=' . urlencode($filter) : ''));
    exit;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$lim  = 20;
$off  = ($page - 1) * $lim;

// Filter query
$where  = '';
$params = [];
if ($filter && in_array($filter, ['pending', 'completed', 'cancelled'])) {
    $where  = 'WHERE o.status = ?';
    $params = [$filter];
}

// Count
$cStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where");
$cStmt->execute($params);
$total = (int)$cStmt->fetchColumn();
$pages = max(1, ceil($total / $lim));

// Orders with joins
$stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email,
            p.name AS product_name, p.price AS unit_price, p.image_url,
            COALESCE(o.bv, 0) AS bv_amt
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     LEFT JOIN products p ON p.id = o.product_id
     $where
     ORDER BY o.created_at DESC
     LIMIT $lim OFFSET $off"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Summary per status
$summaryRaw = $pdo->query(
    "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
     FROM orders GROUP BY status"
)->fetchAll();
$summaryMap = [];
foreach ($summaryRaw as $s) $summaryMap[$s['status']] = $s;

// Top-level stats
$totalRevenue  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='completed'")->fetchColumn();
$totalOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$todayOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$pendingOrders = (int)($summaryMap['pending']['cnt'] ?? 0);

require_once '_layout.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Outfit:wght@300;400;500;600;700;800&display=swap');

:root {
  --g-dd:   #0e2414;
  --g-d:    #1C3D24;
  --g:      #2a5c34;
  --g-l:    #3a8a4a;
  --g-ll:   #5db870;
  --gold:   #C8922A;
  --gold-d: #a87520;
  --gold-l: #e0b050;
  --cream:  #FDFAF4;
  --cream-d:#F3EDE0;
  --border: #DDD5C4;
  --muted:  #6b8a72;
  --ink:    #0e2414;
  --danger: #C0392B;
  --success:#0F7B5C;
}

/* ── HERO ── */
.orders-hero {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 55%, var(--g) 100%);
  border-radius: 16px; padding: 1.5rem 1.75rem;
  margin-bottom: 1.5rem; border-bottom: 3px solid var(--gold);
  box-shadow: 0 8px 32px rgba(14,36,20,.2);
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap; position: relative; overflow: hidden;
}
.orders-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.07) 1.5px, transparent 1.5px);
  background-size: 22px 22px; pointer-events: none;
}
.orders-hero h1 {
  font-family: 'Cinzel', serif; color: #fff;
  font-size: 1.35rem; font-weight: 700; margin-bottom: .2rem;
  position: relative; z-index: 1;
}
.orders-hero p { color: rgba(255,255,255,.5); font-size: .8rem; position: relative; z-index: 1; }

/* ── STAT CARDS ── */
.ord-stats {
  display: grid; grid-template-columns: repeat(4,1fr);
  gap: 1rem; margin-bottom: 1.5rem;
}
.ord-stat {
  background: #fff; border-radius: 14px;
  padding: 1.1rem 1.2rem; border: 1.5px solid var(--border);
  border-top: 3px solid var(--border);
  box-shadow: 0 3px 14px rgba(14,36,20,.06);
  transition: transform .2s, box-shadow .2s;
  display: flex; flex-direction: column; gap: .2rem;
}
.ord-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(14,36,20,.1); }
.ord-stat.s-gold  { border-top-color: var(--gold); }
.ord-stat.s-green { border-top-color: var(--g-d); }
.ord-stat.s-jade  { border-top-color: #0F7B5C; }
.ord-stat.s-warn  { border-top-color: #E67E22; }
.os-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: .3rem; }
.os-icon svg { width: 16px; height: 16px; }
.os-label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); font-family: 'Outfit', sans-serif; }
.os-value { font-family: 'Cinzel', serif; font-size: 1.5rem; font-weight: 700; color: var(--ink); line-height: 1; }
.ord-stat.s-gold  .os-value { color: var(--gold-d); }
.ord-stat.s-green .os-value { color: var(--g-d); }
.ord-stat.s-jade  .os-value { color: #0F7B5C; }
.ord-stat.s-warn  .os-value { color: #E67E22; }
.os-sub { font-size: .7rem; color: var(--muted); font-family: 'Outfit', sans-serif; }

/* ── FILTER BAR ── */
.filter-bar {
  background: #fff; border: 1.5px solid var(--border);
  border-radius: 12px; padding: .75rem 1rem;
  display: flex; align-items: center; gap: .5rem;
  margin-bottom: 1.25rem; flex-wrap: wrap;
  box-shadow: 0 2px 10px rgba(14,36,20,.05);
}
.filter-label { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-right: .25rem; font-family: 'Outfit', sans-serif; }
.filter-btn {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .38rem .9rem; border-radius: 20px;
  font-size: .75rem; font-weight: 700; cursor: pointer;
  text-decoration: none; transition: all .18s;
  font-family: 'Outfit', sans-serif; border: 1.5px solid var(--border);
  background: #fff; color: var(--muted);
}
.filter-btn:hover { border-color: var(--g-d); color: var(--g-d); background: var(--cream); }
.filter-btn.active {
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  color: #fff; border-color: transparent;
  box-shadow: 0 3px 10px rgba(14,36,20,.2);
}
.filter-btn.active-completed { background: linear-gradient(135deg, #0a6644, #0F7B5C); }
.filter-btn.active-pending   { background: linear-gradient(135deg, #a87520, #C8922A); }
.filter-btn.active-cancelled { background: linear-gradient(135deg, #A93226, #C0392B); }
.filter-count {
  font-size: .65rem; background: rgba(255,255,255,.25);
  padding: .05rem .45rem; border-radius: 10px; font-weight: 800;
}

/* ── TABLE CARD ── */
.orders-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 4px 24px rgba(14,36,20,.08);
  overflow: hidden; border: 1.5px solid var(--border);
}
.orders-card table { width: 100%; border-collapse: collapse; font-family: 'Outfit', sans-serif; }
.orders-card thead th {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 60%, var(--g) 100%);
  color: rgba(255,255,255,.8) !important;
  -webkit-text-fill-color: rgba(255,255,255,.8) !important;
  font-size: .65rem; text-transform: uppercase;
  letter-spacing: .1em; font-weight: 700;
  padding: .85rem 1rem; text-align: left;
  white-space: nowrap; border: none !important;
  border-bottom: 2px solid var(--gold) !important;
}
.orders-card tbody tr { border-bottom: 1px solid var(--cream-d); transition: background .12s; }
.orders-card tbody tr:last-child { border-bottom: none; }
.orders-card tbody tr:hover { background: var(--cream); }
.orders-card tbody td { padding: .85rem 1rem; vertical-align: middle; font-size: .84rem; color: var(--ink); }

/* ── ORDER ID ── */
.order-id {
  font-family: 'Cinzel', serif; font-weight: 700;
  color: var(--g-d); font-size: .88rem;
  display: flex; align-items: center; gap: .3rem;
}
.order-id-hash { color: var(--gold-d); }

/* ── MEMBER CELL ── */
.member-cell { display: flex; align-items: center; gap: .65rem; }
.member-av {
  width: 34px; height: 34px; border-radius: 9px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 800; color: var(--gold-l);
  flex-shrink: 0; font-family: 'Cinzel', serif;
}
.member-name  { font-weight: 700; font-size: .84rem; color: var(--g-d); }
.member-email { font-size: .7rem; color: var(--muted); margin-top: 1px; }

/* ── PRODUCT CELL ── */
.product-cell { display: flex; align-items: center; gap: .6rem; }
.product-thumb {
  width: 36px; height: 36px; border-radius: 8px;
  object-fit: cover; border: 1.5px solid var(--border);
  flex-shrink: 0;
}
.product-thumb-ph {
  width: 36px; height: 36px; border-radius: 8px;
  background: linear-gradient(135deg, #dceede, #c8e0cc);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; border: 1.5px solid var(--border);
}
.product-name { font-size: .82rem; font-weight: 600; color: var(--ink); max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.product-price { font-size: .68rem; color: var(--muted); }

/* ── QTY BADGE ── */
.qty-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 26px; height: 24px; padding: 0 .5rem;
  background: rgba(28,61,36,.1); color: var(--g-d);
  border-radius: 20px; font-size: .72rem; font-weight: 800;
  border: 1px solid rgba(28,61,36,.15);
}

/* ── AMOUNT ── */
.amount-val { font-family: 'Cinzel', serif; font-weight: 700; color: var(--gold-d); font-size: .9rem; }

/* ── ADMIN STATUS BADGES ── */
.status-badge {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .25rem .75rem; border-radius: 20px;
  font-size: .68rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .04em;
  font-family: 'Outfit', sans-serif;
}
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.s-completed { background: rgba(15,123,92,.1); color: #0a6644; }
.s-completed .status-dot { background: var(--g-ll); box-shadow: 0 0 0 2px rgba(93,184,112,.25); }
.s-pending   { background: rgba(200,146,42,.1); color: var(--gold-d); }
.s-pending   .status-dot { background: var(--gold); }
.s-cancelled { background: rgba(192,57,43,.08); color: var(--danger); }
.s-cancelled .status-dot { background: var(--danger); }

/* ── DELIVERY STATUS BADGES (customer-facing) ── */
.delivery-badge {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .22rem .65rem; border-radius: 20px;
  font-size: .65rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .04em;
  font-family: 'Outfit', sans-serif;
}
.d-processing { background: rgba(200,146,42,.12); color: #a87520; }
.d-shipped    { background: rgba(15,123,92,.12);  color: #0a6644; }
.d-delivered  { background: rgba(28,61,36,.12);   color: var(--g-d); }
.d-cancelled  { background: rgba(192,57,43,.08);  color: var(--danger); }

/* ── BV CELL ── */
.bv-val { font-size: .78rem; font-weight: 700; color: var(--g-d); }
.bv-zero { color: var(--muted); font-weight: 400; }

/* ── DATE CELL ── */
.date-main { font-size: .78rem; color: var(--ink); font-weight: 500; }
.date-time  { font-size: .68rem; color: var(--muted); margin-top: 1px; }

/* ── UPDATE FORM ── */
.update-form { display: flex; gap: .4rem; align-items: center; }
.status-select {
  background: var(--cream); border: 1.5px solid var(--border);
  color: var(--ink); padding: .42rem .65rem;
  border-radius: 8px; font-family: 'Outfit', sans-serif;
  font-size: .78rem; font-weight: 600; outline: none;
  cursor: pointer; transition: border-color .2s;
  appearance: none; -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' stroke='%236b8a72' stroke-width='2.5' xmlns='http://www.w3.org/2000/svg'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right .5rem center;
  background-size: 12px;
  padding-right: 1.6rem;
}
.status-select:focus { border-color: var(--g-d); }
.btn-update {
  width: 32px; height: 32px; border-radius: 8px; border: none;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  color: #fff; cursor: pointer; display: flex;
  align-items: center; justify-content: center;
  transition: all .2s; flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(14,36,20,.2);
}
.btn-update:hover { transform: scale(1.1); box-shadow: 0 4px 14px rgba(14,36,20,.3); }
.btn-update svg { width: 14px; height: 14px; }

/* ── EMPTY STATE ── */
.empty-state { text-align: center; padding: 3.5rem 2rem; }
.empty-icon-wrap {
  width: 60px; height: 60px; border-radius: 14px;
  background: linear-gradient(135deg, #dceede, #c8e0cc);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto .85rem;
}
.empty-state h3 { font-family: 'Cinzel', serif; color: var(--g-d); font-size: .95rem; margin-bottom: .3rem; }
.empty-state p  { color: var(--muted); font-size: .82rem; }

/* ── PAGINATION ── */
.pagination {
  display: flex; align-items: center; justify-content: space-between;
  padding: .9rem 1.25rem; border-top: 1.5px solid var(--border);
  flex-wrap: wrap; gap: .5rem; background: var(--cream-d);
}
.pg-info { font-size: .75rem; color: var(--muted); font-family: 'Outfit', sans-serif; }
.pg-links { display: flex; gap: .3rem; flex-wrap: wrap; }
.pg-btn {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 700; text-decoration: none;
  color: var(--muted); border: 1.5px solid var(--border);
  background: #fff; transition: all .18s; font-family: 'Outfit', sans-serif;
}
.pg-btn:hover  { border-color: var(--g-d); color: var(--g-d); }
.pg-btn.active { background: var(--g-d); color: #fff; border-color: var(--g-d); box-shadow: 0 2px 8px rgba(28,61,36,.25); }

/* ── RESPONSIVE ── */
@media(max-width:900px) { .ord-stats { grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px) {
  .ord-stats { grid-template-columns: 1fr 1fr; }
  .filter-bar { flex-direction: column; align-items: flex-start; }
  .orders-card thead th:nth-child(n+5) { display: none; }
  .orders-card tbody td:nth-child(n+5) { display: none; }
}
</style>

<!-- ══ HERO ══ -->
<div class="orders-hero">
  <div>
    <h1>Orders Management</h1>
    <p>Track, update and manage all MShop purchases</p>
  </div>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="ord-stats">
  <div class="ord-stat s-gold">
    <div class="os-icon" style="background:rgba(200,146,42,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#a87520" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div class="os-label">Total Revenue</div>
    <div class="os-value" style="font-size:1.25rem">₹<?= number_format((float)$totalRevenue, 0) ?></div>
    <div class="os-sub">From completed orders</div>
  </div>
  <div class="ord-stat s-green">
    <div class="os-icon" style="background:rgba(28,61,36,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#1C3D24" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    </div>
    <div class="os-label">Total Orders</div>
    <div class="os-value"><?= number_format($totalOrders) ?></div>
    <div class="os-sub">All time</div>
  </div>
  <div class="ord-stat s-jade">
    <div class="os-icon" style="background:rgba(15,123,92,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#0F7B5C" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="os-label">Completed</div>
    <div class="os-value"><?= number_format($summaryMap['completed']['cnt'] ?? 0) ?></div>
    <div class="os-sub"><?= inr($summaryMap['completed']['total'] ?? 0) ?> revenue</div>
  </div>
  <div class="ord-stat s-warn">
    <div class="os-icon" style="background:rgba(230,126,34,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#E67E22" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div class="os-label">Pending</div>
    <div class="os-value"><?= number_format($pendingOrders) ?></div>
    <div class="os-sub">Awaiting processing</div>
  </div>
</div>

<!-- ══ FILTER BAR ══ -->
<div class="filter-bar">
  <span class="filter-label">Filter:</span>
  <a href="orders.php" class="filter-btn <?= !$filter ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
    All Orders
    <span class="filter-count" style="<?= !$filter ? 'background:rgba(255,255,255,.2)' : 'background:rgba(28,61,36,.1);color:var(--g-d)' ?>"><?= number_format($totalOrders) ?></span>
  </a>
  <?php
  $filterMeta = [
    'completed' => ['icon' => '<polyline points="20 6 9 17 4 12"/>', 'label' => 'Completed', 'cls' => 'active-completed'],
    'pending'   => ['icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>', 'label' => 'Pending', 'cls' => 'active-pending'],
    'cancelled' => ['icon' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>', 'label' => 'Cancelled', 'cls' => 'active-cancelled'],
  ];
  foreach ($filterMeta as $fk => $fm):
    $cnt = $summaryMap[$fk]['cnt'] ?? 0;
  ?>
  <a href="?status=<?= $fk ?>" class="filter-btn <?= $filter===$fk ? 'active '.$fm['cls'] : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><?= $fm['icon'] ?></svg>
    <?= $fm['label'] ?>
    <span class="filter-count" style="<?= $filter===$fk ? 'background:rgba(255,255,255,.2)' : 'background:rgba(28,61,36,.08);color:var(--g-d)' ?>"><?= number_format($cnt) ?></span>
  </a>
  <?php endforeach; ?>
  <span style="margin-left:auto;font-size:.75rem;color:var(--muted);font-family:'Outfit',sans-serif">
    Showing <?= number_format($total) ?> <?= $filter ?: 'total' ?> orders
  </span>
</div>

<!-- ══ TABLE ══ -->
<div class="orders-card">
  <div style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>Order</th>
        <th>Member</th>
        <th>Product</th>
        <th style="text-align:center">Qty</th>
        <th>Amount</th>
        <th>BV</th>
        <th>Status</th>
        <th>Delivery</th>
        <th>Date</th>
        <th>Update</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($orders)): ?>
      <tr><td colspan="10">
        <div class="empty-state">
          <div class="empty-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" style="width:26px;height:26px"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          </div>
          <h3>No orders found</h3>
          <p><?= $filter ? 'No '.ucfirst($filter).' orders at the moment' : 'Orders will appear here once members start purchasing' ?></p>
        </div>
      </td></tr>
    <?php else: foreach ($orders as $o):
      $badgeCls = [
        'completed' => 's-completed',
        'pending'   => 's-pending',
        'cancelled' => 's-cancelled',
      ];
      $cls = $badgeCls[$o['status']] ?? 's-pending';

      // Delivery status badge
      $deliveryStatus = $o['delivery_status'] ?? 'processing';
      $deliveryBadgeMap = [
        'processing' => ['cls' => 'd-processing', 'icon' => '⏳', 'label' => 'Processing'],
        'shipped'    => ['cls' => 'd-shipped',    'icon' => '🚚', 'label' => 'Shipped'],
        'delivered'  => ['cls' => 'd-delivered',  'icon' => '✅', 'label' => 'Delivered'],
        'cancelled'  => ['cls' => 'd-cancelled',  'icon' => '❌', 'label' => 'Cancelled'],
      ];
      $db = $deliveryBadgeMap[$deliveryStatus] ?? $deliveryBadgeMap['processing'];
    ?>
    <tr>
      <!-- Order ID -->
      <td>
        <div class="order-id">
          <span class="order-id-hash">#</span><?= $o['id'] ?>
        </div>
      </td>

      <!-- Member -->
      <td>
        <div class="member-cell">
          <div class="member-av"><?= mb_strtoupper(mb_substr($o['username'] ?? '?', 0, 2)) ?></div>
          <div>
            <div class="member-name"><?= e($o['username'] ?? '—') ?></div>
            <div class="member-email"><?= e($o['email'] ?? '') ?></div>
          </div>
        </div>
      </td>

      <!-- Product -->
      <td>
        <div class="product-cell">
          <?php if (!empty($o['image_url'])): ?>
            <img src="<?= e($o['image_url']) ?>" class="product-thumb" onerror="this.outerHTML='<div class=\'product-thumb-ph\'><svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#3a8a4a\' stroke-width=\'1.5\' style=\'width:16px;height:16px\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'3\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><path d=\'M21 15l-5-5L5 21\'/></svg></div>'">
          <?php else: ?>
            <div class="product-thumb-ph">
              <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" style="width:16px;height:16px"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </div>
          <?php endif; ?>
          <div>
            <div class="product-name" title="<?= e($o['product_name'] ?? '—') ?>"><?= e($o['product_name'] ?? '—') ?></div>
            <div class="product-price">Unit: <?= inr($o['unit_price'] ?? 0) ?></div>
          </div>
        </div>
      </td>

      <!-- Qty -->
      <td style="text-align:center">
        <span class="qty-badge">×<?= $o['quantity'] ?? 1 ?></span>
      </td>

      <!-- Amount -->
      <td><span class="amount-val"><?= inr($o['amount']) ?></span></td>

      <!-- BV -->
      <td>
        <?php $bv = (float)($o['bv'] ?? 0); ?>
        <?php if ($bv > 0): ?>
          <span class="bv-val"><?= number_format($bv, 0) ?></span>
        <?php else: ?>
          <span class="bv-zero">—</span>
        <?php endif; ?>
      </td>

      <!-- Admin Status -->
      <td>
        <span class="status-badge <?= $cls ?>">
          <span class="status-dot"></span>
          <?= ucfirst($o['status']) ?>
        </span>
      </td>

      <!-- Customer Delivery Status -->
      <td>
        <span class="delivery-badge <?= $db['cls'] ?>">
          <?= $db['icon'] ?> <?= $db['label'] ?>
        </span>
      </td>

      <!-- Date -->
      <td>
        <div class="date-main"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
        <div class="date-time"><?= date('h:i A', strtotime($o['created_at'])) ?></div>
      </td>

      <!-- Update -->
      <td>
        <form method="POST" action="orders.php" class="update-form">
          <input type="hidden" name="oid" value="<?= $o['id'] ?>">
          <select name="status" class="status-select">
            <?php foreach (['pending','completed','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-update" title="Save">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="pagination">
    <span class="pg-info">Showing <?= $off+1 ?>–<?= min($off+$lim,$total) ?> of <?= number_format($total) ?> orders</span>
    <div class="pg-links">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&status=<?= urlencode($filter) ?>" class="pg-btn">‹</a>
      <?php endif; ?>
      <?php
        $start = max(1, $page-2);
        $end   = min($pages, $page+2);
        if ($start > 1) echo '<span class="pg-btn" style="border:none;color:var(--muted)">…</span>';
        for ($p = $start; $p <= $end; $p++):
      ?>
        <a href="?page=<?= $p ?>&status=<?= urlencode($filter) ?>" class="pg-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor;
        if ($end < $pages) echo '<span class="pg-btn" style="border:none;color:var(--muted)">…</span>';
      ?>
      <?php if ($page < $pages): ?>
        <a href="?page=<?= $page+1 ?>&status=<?= urlencode($filter) ?>" class="pg-btn">›</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once '_footer.php'; ?>