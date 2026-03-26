<?php
// admin/withdrawals.php
require_once __DIR__ . '/config.php';
$pageTitle = 'Withdrawals';
$pdo = db();

// ── Handle Approve / Reject ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wid    = (int)($_POST['wid'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $note   = trim($_POST['note'] ?? '');

    // Fetch withdrawal
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$wid]);
    $wd = $stmt->fetch();

    if ($wd && $wd['status'] === 'pending') {

        if ($action === 'approve') {
            $pdo->beginTransaction();
            try {
                // Lock user row & verify balance
                $lock = $pdo->prepare("SELECT wallet FROM users WHERE id = ? FOR UPDATE");
                $lock->execute([$wd['user_id']]);
                $bal = (float)$lock->fetchColumn();

                if ($bal < (float)$wd['amount']) {
                    $pdo->rollBack();
                    setAdminFlash('error', "User ka wallet balance insufficient hai. Approve nahi ho sakta.");
                } else {
                    // ✅ Approve pe deduct karo
                    $pdo->prepare("UPDATE users SET wallet = wallet - ? WHERE id = ?")
                        ->execute([$wd['amount'], $wd['user_id']]);

                    $pdo->prepare("UPDATE withdrawals SET status='approved', admin_note=?, updated_at=NOW() WHERE id=?")
                        ->execute([$note, $wid]);

                    $pdo->commit();
                    setAdminFlash('success', "Withdrawal #$wid approved. ₹" . number_format($wd['amount'], 2) . " deducted from wallet.");
                }
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Withdrawal approve error: ' . $e->getMessage());
                setAdminFlash('error', 'Database error: ' . $e->getMessage());
            }

        } elseif ($action === 'reject') {
            // ✅ Reject pe wallet bilkul touch nahi karo
            $pdo->prepare("UPDATE withdrawals SET status='rejected', admin_note=?, updated_at=NOW() WHERE id=?")
                ->execute([$note ?: 'Rejected by admin', $wid]);

            setAdminFlash('success', "Withdrawal #$wid rejected. User ka balance safe hai.");
        }

    } else {
        setAdminFlash('error', 'Invalid request ya already processed.');
    }

    header('Location: withdrawals.php?status=' . urlencode($_GET['status'] ?? 'pending'));
    exit;
}

// ── Filters ───────────────────────────────────────────
$filter = $_GET['status'] ?? 'pending';
$where  = $filter !== 'all' ? "WHERE w.status = ?" : '';
$params = $filter !== 'all' ? [$filter] : [];

$stmt = $pdo->prepare(
    "SELECT w.*, u.username, u.email, u.wallet AS user_wallet
     FROM withdrawals w
     LEFT JOIN users u ON u.id = w.user_id
     $where
     ORDER BY w.created_at DESC"
);
$stmt->execute($params);
$wds = $stmt->fetchAll();

// ── Stats ─────────────────────────────────────────────
$pending_cnt  = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();
$pending_sum  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='pending'")->fetchColumn();
$approved_sum = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='approved'")->fetchColumn();
$rejected_cnt = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='rejected'")->fetchColumn();

require_once '_layout.php';
?>

<style>
.wd-hero {
  background: linear-gradient(135deg, #0e2414 0%, #1C3D24 55%, #2a5c34 100%);
  border-radius: 16px; padding: 1.5rem 1.75rem;
  margin-bottom: 1.5rem; border-bottom: 3px solid #C8922A;
  box-shadow: 0 8px 32px rgba(14,36,20,.2);
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap; position: relative; overflow: hidden;
}
.wd-hero::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.07) 1.5px, transparent 1.5px);
  background-size: 22px 22px; pointer-events: none;
}
.wd-hero h1 { font-family:'Cinzel',serif; color:#fff; font-size:1.35rem; font-weight:700; margin-bottom:.2rem; position:relative; z-index:1; }
.wd-hero p  { color:rgba(255,255,255,.5); font-size:.8rem; position:relative; z-index:1; }

.wd-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
.wd-stat  { background:#fff; border-radius:14px; padding:1rem 1.2rem; border:1.5px solid #DDD5C4; border-top:3px solid #DDD5C4; box-shadow:0 3px 14px rgba(14,36,20,.06); }
.wd-stat.s-warn  { border-top-color:#E67E22; }
.wd-stat.s-jade  { border-top-color:#0F7B5C; }
.wd-stat.s-red   { border-top-color:#C0392B; }
.wd-stat.s-gold  { border-top-color:#C8922A; }
.wds-label { font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b8a72; font-family:'Outfit',sans-serif; }
.wds-value { font-family:'Cinzel',serif; font-size:1.4rem; font-weight:700; color:#0e2414; line-height:1.1; margin:.2rem 0; }
.wd-stat.s-warn .wds-value { color:#E67E22; }
.wd-stat.s-jade .wds-value { color:#0F7B5C; }
.wd-stat.s-red  .wds-value { color:#C0392B; }
.wd-stat.s-gold .wds-value { color:#a87520; }
.wds-sub { font-size:.7rem; color:#6b8a72; font-family:'Outfit',sans-serif; }

.filter-bar { background:#fff; border:1.5px solid #DDD5C4; border-radius:12px; padding:.75rem 1rem; display:flex; align-items:center; gap:.5rem; margin-bottom:1.25rem; flex-wrap:wrap; box-shadow:0 2px 10px rgba(14,36,20,.05); }
.filter-btn { display:inline-flex; align-items:center; gap:.35rem; padding:.38rem .9rem; border-radius:20px; font-size:.75rem; font-weight:700; cursor:pointer; text-decoration:none; transition:all .18s; font-family:'Outfit',sans-serif; border:1.5px solid #DDD5C4; background:#fff; color:#6b8a72; }
.filter-btn:hover { border-color:#1C3D24; color:#1C3D24; }
.filter-btn.active { background:linear-gradient(135deg,#0e2414,#2a5c34); color:#fff; border-color:transparent; box-shadow:0 3px 10px rgba(14,36,20,.2); }
.filter-btn.fa-pending  { background:linear-gradient(135deg,#a87520,#C8922A); }
.filter-btn.fa-approved { background:linear-gradient(135deg,#0a6644,#0F7B5C); }
.filter-btn.fa-rejected { background:linear-gradient(135deg,#A93226,#C0392B); }

.wdt { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(14,36,20,.08); overflow:hidden; border:1.5px solid #DDD5C4; }
.wdt table { width:100%; border-collapse:collapse; font-family:'Outfit',sans-serif; }
.wdt thead th { background:linear-gradient(135deg,#0e2414 0%,#1C3D24 60%,#2a5c34 100%); color:rgba(255,255,255,.8); font-size:.65rem; text-transform:uppercase; letter-spacing:.1em; font-weight:700; padding:.85rem 1rem; text-align:left; border:none; border-bottom:2px solid #C8922A; white-space:nowrap; }
.wdt tbody tr { border-bottom:1px solid #F3EDE0; transition:background .12s; }
.wdt tbody tr:last-child { border-bottom:none; }
.wdt tbody tr:hover { background:#FDFAF4; }
.wdt tbody td { padding:.85rem 1rem; vertical-align:middle; font-size:.84rem; color:#0e2414; }

.status-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.22rem .7rem; border-radius:20px; font-size:.68rem; font-weight:800; text-transform:uppercase; font-family:'Outfit',sans-serif; }
.sb-pending  { background:rgba(200,146,42,.1);  color:#a87520; }
.sb-approved { background:rgba(15,123,92,.1);   color:#0a6644; }
.sb-rejected { background:rgba(192,57,43,.08);  color:#C0392B; }

.act-forms { display:flex; flex-direction:column; gap:.4rem; min-width:230px; }
.act-row   { display:flex; gap:.35rem; align-items:center; }
.act-note  { background:#FDFAF4; border:1px solid #DDD5C4; padding:4px 8px; border-radius:6px; font-size:.76rem; width:110px; outline:none; color:#0e2414; font-family:'Outfit',sans-serif; }
.btn-approve { background:linear-gradient(135deg,#0a6644,#0F7B5C); color:#fff; border:none; padding:.38rem .8rem; border-radius:7px; font-size:.74rem; font-weight:700; cursor:pointer; font-family:'Outfit',sans-serif; transition:all .2s; }
.btn-approve:hover { transform:scale(1.05); }
.btn-reject  { background:linear-gradient(135deg,#A93226,#C0392B); color:#fff; border:none; padding:.38rem .8rem; border-radius:7px; font-size:.74rem; font-weight:700; cursor:pointer; font-family:'Outfit',sans-serif; transition:all .2s; }
.btn-reject:hover { transform:scale(1.05); }

.user-bal { font-size:.7rem; color:#6b8a72; margin-top:2px; font-family:'Outfit',sans-serif; }
.admin-note-text { font-size:.7rem; color:#6b8a72; margin-top:3px; font-style:italic; }

@media(max-width:900px) { .wd-stats { grid-template-columns:repeat(2,1fr); } }
</style>

<!-- Hero -->
<div class="wd-hero">
  <div>
    <h1>💸 Withdrawal Requests</h1>
    <p>Approve or reject member withdrawal requests</p>
  </div>
  <?php if ($pending_cnt > 0): ?>
  <div style="background:rgba(230,126,34,.2);border:1px solid rgba(230,126,34,.4);border-radius:12px;padding:.6rem 1rem;position:relative;z-index:1">
    <div style="font-family:'Cinzel',serif;color:#E67E22;font-size:1.2rem;font-weight:700"><?= $pending_cnt ?></div>
    <div style="font-size:.68rem;color:rgba(255,255,255,.6)">Pending Action</div>
  </div>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="wd-stats">
  <div class="wd-stat s-warn">
    <div class="wds-label">Pending Requests</div>
    <div class="wds-value"><?= $pending_cnt ?></div>
    <div class="wds-sub">₹<?= number_format($pending_sum, 2) ?> pending</div>
  </div>
  <div class="wd-stat s-gold">
    <div class="wds-label">Pending Amount</div>
    <div class="wds-value" style="font-size:1.1rem">₹<?= number_format($pending_sum, 0) ?></div>
    <div class="wds-sub">To be processed</div>
  </div>
  <div class="wd-stat s-jade">
    <div class="wds-label">Total Approved</div>
    <div class="wds-value" style="font-size:1.1rem">₹<?= number_format($approved_sum, 0) ?></div>
    <div class="wds-sub">Successfully paid</div>
  </div>
  <div class="wd-stat s-red">
    <div class="wds-label">Rejected</div>
    <div class="wds-value"><?= $rejected_cnt ?></div>
    <div class="wds-sub">Balance safe</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
  <span style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b8a72;font-family:'Outfit',sans-serif">Filter:</span>
  <?php
  $filterMeta = [
    'pending'  => ['label'=>'Pending',  'cls'=>'fa-pending'],
    'approved' => ['label'=>'Approved', 'cls'=>'fa-approved'],
    'rejected' => ['label'=>'Rejected', 'cls'=>'fa-rejected'],
    'all'      => ['label'=>'All',      'cls'=>''],
  ];
  foreach ($filterMeta as $fk => $fm):
  ?>
  <a href="?status=<?= $fk ?>" class="filter-btn <?= $filter===$fk ? 'active '.$fm['cls'] : '' ?>">
    <?= $fm['label'] ?>
    <?php if ($fk === 'pending' && $pending_cnt > 0): ?>
      <span style="background:rgba(255,255,255,.25);padding:.05rem .45rem;border-radius:10px;font-size:.65rem;font-weight:800"><?= $pending_cnt ?></span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <span style="margin-left:auto;font-size:.75rem;color:#6b8a72;font-family:'Outfit',sans-serif">
    <?= count($wds) ?> <?= $filter !== 'all' ? ucfirst($filter) : 'total' ?> requests
  </span>
</div>

<!-- Table -->
<div class="wdt">
  <div style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Member</th>
        <th>Amount</th>
        <th>Method</th>
        <th>Account Details</th>
        <th>Status</th>
        <th>Requested</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($wds)): ?>
      <tr><td colspan="8" style="text-align:center;padding:3rem;color:#6b8a72;font-family:'Outfit',sans-serif">
        No <?= $filter !== 'all' ? $filter : '' ?> withdrawal requests
      </td></tr>
    <?php else: foreach ($wds as $w):
      $det = [];
      if (!empty($w['account_details'])) $det = json_decode($w['account_details'], true) ?? [];
      $detStr = '';
      if ($w['method'] === 'bank') {
          $detStr = ($det['bank_name'] ?? '') . ' · ' . ($det['acc_number'] ?? '') . ' · IFSC: ' . ($det['ifsc'] ?? '');
          $accName = $det['acc_name'] ?? '';
      } else {
          $detStr  = $det['upi_id'] ?? '';
          $accName = '';
      }
      $status = strtolower($w['status'] ?? 'pending');
      $sbCls  = ['pending'=>'sb-pending','approved'=>'sb-approved','rejected'=>'sb-rejected'][$status] ?? 'sb-pending';
    ?>
    <tr>
      <td style="font-family:'Cinzel',serif;font-weight:700;color:#1C3D24">#<?= $w['id'] ?></td>

      <td>
        <div style="font-weight:700;font-size:.85rem"><?= e($w['username'] ?? '—') ?></div>
        <div style="font-size:.72rem;color:#6b8a72"><?= e($w['email'] ?? '') ?></div>
        <div class="user-bal">Wallet: ₹<?= number_format((float)($w['user_wallet'] ?? 0), 2) ?></div>
      </td>

      <td>
        <span style="font-family:'Cinzel',serif;font-weight:700;font-size:1rem;color:#a87520">
          ₹<?= number_format((float)$w['amount'], 2) ?>
        </span>
      </td>

      <td>
        <span style="font-size:.8rem;font-weight:700">
          <?= $w['method'] === 'bank' ? '🏦 Bank' : '📱 UPI' ?>
        </span>
      </td>

      <td style="font-size:.76rem;color:#6b8a72;max-width:180px">
        <?php if ($accName): ?>
          <div style="font-weight:600;color:#0e2414;font-size:.8rem"><?= e($accName) ?></div>
        <?php endif; ?>
        <?= e($detStr) ?>
      </td>

      <td>
        <span class="status-badge <?= $sbCls ?>">
          <?= ucfirst($status) ?>
        </span>
        <?php if (!empty($w['admin_note'])): ?>
          <div class="admin-note-text"><?= e($w['admin_note']) ?></div>
        <?php endif; ?>
      </td>

      <td style="font-size:.78rem;color:#6b8a72;white-space:nowrap">
        <?= date('d M Y', strtotime($w['created_at'])) ?>
        <br><?= date('h:i A', strtotime($w['created_at'])) ?>
      </td>

      <td>
        <?php if ($status === 'pending'): ?>
          <div class="act-forms">
            <!-- Approve -->
            <form method="POST" onsubmit="return confirm('Approve this withdrawal? ₹<?= number_format($w['amount'],2) ?> will be deducted from user wallet.')">
              <input type="hidden" name="wid"    value="<?= $w['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <div class="act-row">
                <input type="text" name="note" class="act-note" placeholder="Note (optional)">
                <button type="submit" class="btn-approve">✓ Approve</button>
              </div>
            </form>
            <!-- Reject -->
            <form method="POST" onsubmit="return confirm('Reject this withdrawal? User balance will NOT be affected.')">
              <input type="hidden" name="wid"    value="<?= $w['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <div class="act-row">
                <input type="text" name="note" class="act-note" placeholder="Reason" required>
                <button type="submit" class="btn-reject">✗ Reject</button>
              </div>
            </form>
          </div>
        <?php else: ?>
          <span style="font-size:.8rem;color:#6b8a72">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require_once '_footer.php'; ?>