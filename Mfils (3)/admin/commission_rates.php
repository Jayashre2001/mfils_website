<?php
// admin/commission_rates.php
require_once __DIR__ . '/config.php';
$pageTitle = 'Commission Rates';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['rate'] as $level => $rate) {
        $level = (int)$level;
        $rate  = round((float)$rate, 2);
        $label = trim($_POST['label'][$level] ?? "Level $level");
        $pdo->prepare("UPDATE commission_rates SET rate=?,label=? WHERE level=?")->execute([$rate,$label,$level]);
    }
    setAdminFlash('success', 'Commission rates updated successfully.');
    header('Location: commission_rates.php'); exit;
}

$rates = $pdo->query("SELECT * FROM commission_rates ORDER BY level")->fetchAll();
$total_rate = array_sum(array_column($rates, 'rate'));

require_once '_layout.php';
?>

<div style="max-width:680px">

<div class="tcard" style="margin-bottom:1.5rem">
  <div class="tcard-head">
    <h3>7-Level Commission Rates</h3>
    <span class="badge <?= $total_rate<=100?'badge-green':'badge-red' ?>">
      Total: <?= pct($total_rate) ?> <?= $total_rate>100?'⚠ Exceeds 100%':'' ?>
    </span>
  </div>

  <form method="POST">
    <div style="padding:1.25rem">
      <div style="background:var(--cream);border:1px solid #E5DDD4;border-radius:8px;padding:.9rem 1rem;margin-bottom:1.25rem;font-size:.85rem;color:var(--muted)">
        ℹ️ When a member buys a product, commissions are distributed up the referral chain. Level 1 = direct upline, Level 2 = upline's upline, etc. up to Level 7.
      </div>

      <div style="display:grid;gap:.75rem">
        <?php foreach ($rates as $r): ?>
        <div style="display:grid;grid-template-columns:80px 1fr 120px;gap:.75rem;align-items:center;background:#fff;border:1.5px solid var(--cream-d);border-left:4px solid var(--gold);border-radius:7px;padding:.75rem 1rem">
          <div style="text-align:center">
            <span style="font-family:'Playfair Display',serif;font-size:1.25rem;color:var(--maroon);font-weight:700">L<?= $r['level'] ?></span>
          </div>
          <div>
            <label class="f-label" style="margin-bottom:.25rem">Label</label>
            <input type="text" name="label[<?= $r['level'] ?>]" value="<?= e($r['label']) ?>" class="f-control" style="font-size:.85rem;padding:.45rem .75rem">
          </div>
          <div>
            <label class="f-label" style="margin-bottom:.25rem">Rate (%)</label>
            <div style="display:flex;align-items:center;gap:.4rem">
              <input type="number" name="rate[<?= $r['level'] ?>]" value="<?= $r['rate'] ?>" step="0.01" min="0" max="100" class="f-control" style="font-size:.9rem;padding:.45rem .75rem">
              <span style="color:var(--muted);flex-shrink:0">%</span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:1.5rem;display:flex;gap:.75rem;align-items:center">
        <button type="submit" class="btn btn-primary">💾 Save Commission Rates</button>
        <span style="font-size:.82rem;color:var(--muted)">Changes apply to future orders immediately.</span>
      </div>
    </div>
  </form>
</div>

<!-- Current rates preview -->
<div class="tcard">
  <div class="tcard-head"><h3>Rate Preview</h3></div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Level</th><th>Label</th><th>Rate</th><th>On ₹1000 order</th><th>On ₹5000 order</th></tr></thead>
    <tbody>
    <?php foreach ($rates as $r): ?>
    <tr>
      <td><span class="badge badge-blue">L<?= $r['level'] ?></span></td>
      <td><?= e($r['label']) ?></td>
      <td><span class="badge badge-gold"><?= pct($r['rate']) ?></span></td>
      <td style="color:var(--success);font-weight:600"><?= inr(1000*$r['rate']/100) ?></td>
      <td style="color:var(--success);font-weight:600"><?= inr(5000*$r['rate']/100) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="background:var(--cream)">
      <td colspan="2"><strong>Total Distribution</strong></td>
      <td><strong><?= pct($total_rate) ?></strong></td>
      <td><strong><?= inr(1000*$total_rate/100) ?></strong></td>
      <td><strong><?= inr(5000*$total_rate/100) ?></strong></td>
    </tr>
    </tbody>
  </table>
  </div>
</div>

</div>

<?php require_once '_footer.php'; ?>
