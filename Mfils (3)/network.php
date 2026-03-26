<?php
// network.php
$pageTitle = 'My Network – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = currentUserId();
$user   = getUser($userId);          // FIX: getUser() now returns total_bv from orders
$tree   = getDownlineTree($userId);  // FIX: now includes total_bv per node

// ── Flatten tree ──────────────────────────────────────────────
function flattenTree(array $nodes, array &$flat = []): array {
    foreach ($nodes as $n) {
        $flat[] = $n;
        if (!empty($n['children'])) flattenTree($n['children'], $flat);
    }
    return $flat;
}

// ── Serialise for JS ──────────────────────────────────────────
function treeToJson(array $nodes): array {
    $out = [];
    foreach ($nodes as $n) {
        $out[] = [
            'id'       => $n['id'],
            'username' => $n['username'],
            'level'    => $n['level'],
            'wallet'   => $n['wallet'],
            'bv'       => $n['total_bv'] ?? 0,   // FIX: was always 0 before
            'joined'   => date('M Y', strtotime($n['created_at'])),
            'children' => !empty($n['children']) ? treeToJson($n['children']) : [],
        ];
    }
    return $out;
}

$flat    = flattenTree($tree);
$byLevel = [];
foreach ($flat as $n) $byLevel[$n['level']][] = $n;
ksort($byLevel);

// ── BV per level (own purchases of members at each level) ─────
// FIX: Now populated correctly because getDownlineTree() fetches total_bv
$rates = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];

$bvByLevel = [];
for ($l = 1; $l <= 7; $l++) {
    $bvByLevel[$l] = 0;
    foreach (($byLevel[$l] ?? []) as $m) {
        $bvByLevel[$l] += (float)($m['total_bv'] ?? 0);
    }
}

// ── PSB actually credited (from commissions table — source of truth) ──
// FIX: Previously re-calculated from BV × rate, which gave wrong figures
//      if any orders were refunded or commissions were adjusted.
//      Now reads directly from the commissions table.
$psbByLevel  = getPsbEarnedByLevel($userId);
$totalPsbEarned = getTotalPsbEarned($userId);

// ── Total Group BV — ALL levels, no cap ───────────────────────
// Per document: BV beyond L7 counts for Leadership Club qualification.
$totalGroupBv = getGroupBv($userId);

// ── PSB-eligible Group BV — L1–L7 only ───────────────────────
$psbGroupBv = getPsbGroupBv($userId, 7);

// ── BV beyond L7 (for display in Leadership Club notice) ──────
$beyondL7Bv = max(0, $totalGroupBv - $psbGroupBv);

// ── Root node's own BV ────────────────────────────────────────
$rootBv = (float)($user['total_bv'] ?? 0); // FIX: now populated by getUser()

// ── JSON for JS tree renderer ─────────────────────────────────
$treeJson = json_encode([
    'id'       => $userId,
    'username' => $user['username'],
    'level'    => 0,
    'wallet'   => $user['wallet'],
    'bv'       => $rootBv,
    'joined'   => date('M Y', strtotime($user['created_at'])),
    'children' => treeToJson($tree),
    'isRoot'   => true,
]);

include __DIR__ . '/includes/header.php';
?>

<style>
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
  --coral:     #E8534A;
  --ink:       #0e2414;
  --muted:     #5a7a62;
}

/* ── Page header ── */
.page-header {
  background: linear-gradient(135deg, var(--indigo-d) 0%, var(--indigo) 55%, var(--indigo-l) 100%);
  padding: 2.5rem 0 3.5rem;
  position: relative; overflow: hidden;
  border-bottom: 3px solid var(--gold);
}
.page-header::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.12) 1.5px, transparent 1.5px);
  background-size: 24px 24px; pointer-events: none;
}
.ph-arc { position: absolute; border-radius: 50%; border: 2px solid rgba(200,146,42,.1); pointer-events: none; }
.ph-arc-1 { width: 320px; height: 220px; bottom: -80px; left: -60px; }
.ph-arc-2 { width: 280px; height: 280px; top: -100px; right: -70px; border-color: rgba(15,123,92,.12); }
.page-header .container { position: relative; z-index: 1; }
.page-header h1 { font-family: 'Cinzel','Georgia',serif; font-size: clamp(1.5rem,3vw,2rem); font-weight: 900; color: #fff; margin-bottom: .25rem; }
.page-header p  { color: rgba(255,255,255,.55); font-size: .88rem; }

/* ── Page body ── */
.page-wrap { background: var(--ivory); padding-bottom: 4rem; }

/* ── Stat tiles ── */
.stat-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
  gap: 1rem; margin-bottom: 1.75rem;
}
.stat-tile {
  background: #fff; border-radius: 14px;
  border: 1.5px solid var(--ivory-dd);
  box-shadow: 0 2px 12px rgba(14,36,20,.06);
  padding: 1.1rem 1.3rem;
  border-top: 3px solid var(--indigo);
  display: flex; flex-direction: column; gap: .2rem;
}
.stat-tile.gold-top  { border-top-color: var(--gold); }
.stat-tile.jade-top  { border-top-color: var(--jade); }
.stat-tile.coral-top { border-top-color: var(--coral); }
.stat-tile .st-label { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
.stat-tile .st-value { font-family: 'Cinzel','Georgia',serif; font-size: 1.6rem; font-weight: 700; color: var(--indigo); line-height: 1.1; }
.stat-tile.gold-top  .st-value { color: var(--gold-d); }
.stat-tile.jade-top  .st-value { color: var(--jade); }
.stat-tile.coral-top .st-value { color: var(--coral); }
.stat-tile .st-sub { font-size: .72rem; color: var(--muted); margin-top: .1rem; }

/* ── Card ── */
.card {
  background: #fff; border-radius: 16px;
  border: 1.5px solid var(--ivory-dd);
  box-shadow: 0 2px 12px rgba(14,36,20,.07);
  overflow: hidden; margin-bottom: 1.5rem;
}
.card-header {
  font-family: 'Cinzel','Georgia',serif;
  font-size: .95rem; font-weight: 700; color: var(--indigo);
  padding: 1rem 1.25rem;
  border-bottom: 1.5px solid var(--ivory-dd);
  background: var(--ivory-d);
  display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
}
.card-header em { font-style: italic; color: var(--gold); }

/* ── Badge ── */
.badge { display: inline-flex; align-items: center; justify-content: center; padding: .2rem .65rem; border-radius: 20px; font-size: .72rem; font-weight: 800; }
.badge-indigo { background: rgba(14,36,20,.12); color: var(--indigo); }
.badge-gold   { background: rgba(200,146,42,.15); color: var(--gold-d); }
.badge-jade   { background: rgba(15,123,92,.12); color: var(--jade); }

/* ── Tree scroll canvas ── */
.tree-scroll {
  overflow-x: auto; overflow-y: auto;
  padding: 2.5rem 2rem 3rem;
  min-height: 320px; max-height: 640px;
  cursor: grab; user-select: none;
  background: radial-gradient(circle at 1px 1px, rgba(14,36,20,.05) 1px, transparent 0);
  background-size: 28px 28px;
  border-radius: 0 0 10px 10px;
  display: flex; justify-content: center; align-items: flex-start;
}
.tree-scroll:active { cursor: grabbing; }
.fc-tree { display: inline-flex; flex-direction: column; align-items: center; min-width: max-content; flex-shrink: 0; margin: 0 auto; }

/* ── Node wrap ── */
.fc-node-wrap { display: flex; flex-direction: column; align-items: center; position: relative; padding: 0 16px; }
.fc-node-wrap.has-children > .fc-node-box::after {
  content: ''; position: absolute; bottom: -24px; left: 50%; transform: translateX(-50%);
  width: 2px; height: 24px; background: var(--gold); z-index: 1; pointer-events: none;
}
.fc-node-wrap.has-children.collapsed > .fc-node-box::after { display: none; }

/* ── Node box ── */
.fc-node-box {
  position: relative; display: flex; flex-direction: column; align-items: center;
  gap: 4px; background: #fff; border: 2px solid var(--ivory-dd);
  border-radius: 14px; padding: 12px 14px 10px;
  min-width: 128px; max-width: 158px;
  box-shadow: 0 3px 14px rgba(14,36,20,.07);
  transition: border-color .2s, box-shadow .2s, transform .15s;
  cursor: default;
}
.fc-node-box.clickable { cursor: pointer; }
.fc-node-box.clickable:hover { border-color: var(--gold); box-shadow: 0 6px 22px rgba(14,36,20,.16); transform: translateY(-2px); z-index: 10; }
.fc-node-box.clickable:active { transform: translateY(0); }

/* Root */
.fc-node-box.root {
  border-color: var(--gold);
  background: linear-gradient(135deg, var(--indigo-d), var(--indigo));
  color: #fff; min-width: 148px; cursor: default !important;
}
.fc-node-box.root .fc-name    { color: var(--gold-l); }
.fc-node-box.root .fc-meta    { color: rgba(255,255,255,.55); }
.fc-node-box.root .fc-wallet  { background: rgba(200,146,42,.22); color: var(--gold-l); }
.fc-node-box.root .fc-bv-pill { background: rgba(15,123,92,.25); color: #5ce8b5; }
.fc-node-box.root .fc-avatar  { background: var(--gold); color: #fff; }

/* Level accents */
.fc-node-box[data-lvl="1"] { border-left: 3px solid var(--gold); }
.fc-node-box[data-lvl="2"] { border-left: 3px solid var(--indigo); }
.fc-node-box[data-lvl="3"] { border-left: 3px solid var(--jade); }
.fc-node-box[data-lvl="4"] { border-left: 3px solid var(--jade-l); }
.fc-node-box[data-lvl="5"] { border-left: 3px solid var(--coral); }
.fc-node-box[data-lvl="6"] { border-left: 3px solid var(--gold-l); }
.fc-node-box[data-lvl="7"] { border-left: 3px solid var(--ivory-dd); }

.fc-node-box.clickable.is-collapsed { border-style: dashed; border-color: var(--gold); }

/* Node inner elements */
.fc-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--indigo); color: rgba(255,255,255,.9);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cinzel','Georgia',serif; font-weight: 800; font-size: .95rem;
  flex-shrink: 0; pointer-events: none;
}
.fc-name    { font-weight: 700; font-size: .8rem; text-align: center; color: var(--ink); line-height: 1.2; pointer-events: none; }
.fc-meta    { font-size: .65rem; color: var(--muted); text-align: center; pointer-events: none; }
.fc-wallet  { background: rgba(200,146,42,.12); color: var(--gold-d); font-size: .65rem; font-weight: 700; border-radius: 10px; padding: .1rem .45rem; pointer-events: none; }
.fc-bv-pill { background: rgba(14,36,20,.09); color: var(--indigo); font-size: .63rem; font-weight: 700; border-radius: 10px; padding: .1rem .45rem; pointer-events: none; }

.fc-expand-hint {
  position: absolute; bottom: -9px; left: 50%; transform: translateX(-50%);
  width: 18px; height: 18px; border-radius: 50%;
  background: var(--indigo); color: #fff;
  font-size: .65rem; font-weight: 900;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #fff; box-shadow: 0 2px 6px rgba(14,36,20,.3);
  z-index: 5; pointer-events: none; transition: background .2s; line-height: 1;
}
.fc-node-box.clickable:hover .fc-expand-hint { background: var(--gold); }
.fc-node-box.is-collapsed    .fc-expand-hint { background: var(--gold); }

/* Connector lines */
.fc-connector { position: relative; height: 24px; display: flex; align-items: flex-end; justify-content: center; width: 100%; }
.fc-connector::before { content: ''; position: absolute; top: 0; left: calc(50% - 1px); width: 2px; height: 100%; background: var(--gold); }
.fc-h-line { position: absolute; bottom: 0; height: 2px; background: var(--gold); }

/* Children group */
.fc-children { display: flex; flex-direction: column; align-items: center; }
.fc-children.hidden { display: none; }
.fc-children-row { display: flex; align-items: flex-start; justify-content: center; }

/* Controls bar */
.tree-controls { display: flex; gap: .5rem; align-items: center; padding: .7rem 1.25rem; border-bottom: 1px solid var(--ivory-dd); background: #fff; flex-wrap: wrap; }
.tree-stats { margin-left: auto; font-size: .78rem; color: var(--muted); font-weight: 500; }

/* Legend */
.tree-legend { display: flex; flex-wrap: wrap; gap: .5rem 1rem; padding: .8rem 1.25rem; background: var(--ivory-d); border-top: 1.5px solid var(--ivory-dd); font-size: .78rem; }
.legend-item { display: flex; align-items: center; gap: .35rem; font-weight: 600; color: var(--muted); }
.legend-dot  { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* Tooltip */
.fc-tooltip {
  position: fixed; background: var(--indigo); color: #fff;
  border-radius: 10px; padding: .75rem 1rem; font-size: .8rem;
  pointer-events: none; opacity: 0; transition: opacity .15s;
  z-index: 9999; min-width: 180px;
  box-shadow: 0 8px 24px rgba(0,0,0,.25);
  border: 1px solid rgba(200,146,42,.3); line-height: 1.7;
}
.fc-tooltip.show { opacity: 1; }
.fc-tooltip strong { color: var(--gold-l); font-size: .9rem; display: block; margin-bottom: .2rem; }
.fc-tooltip span   { opacity: .75; display: block; font-size: .77rem; }
.fc-tooltip .tip-bv   { color: #5ce8b5; opacity: 1 !important; font-weight: 700; }
.fc-tooltip .tip-earn { color: var(--gold-l); opacity: 1 !important; font-weight: 700; }

/* Section title */
.section-title {
  font-family: 'Cinzel','Georgia',serif; font-size: 1.15rem; font-weight: 700; color: var(--indigo);
  display: flex; align-items: center; gap: .75rem; margin: 0 0 1rem;
}
.section-title::after { content: ''; flex: 1; height: 1.5px; background: var(--ivory-dd); border-radius: 2px; }

/* ── Level summary table ── */
.table-wrap { overflow-x: auto; }
.table-wrap table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.table-wrap thead th {
  padding: .7rem 1rem; text-align: left;
  font-size: .68rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .08em; color: var(--muted);
  background: var(--ivory-d); border-bottom: 1.5px solid var(--ivory-dd);
}
.table-wrap tbody tr { border-bottom: 1px solid var(--ivory-d); transition: background .15s; }
.table-wrap tbody tr:last-child { border-bottom: none; }
.table-wrap tbody tr:hover { background: var(--ivory-d); }
.table-wrap tbody td { padding: .8rem 1rem; vertical-align: middle; color: var(--ink); }

.lvl-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 64px; padding: .2rem .6rem; border-radius: 20px; font-size: .72rem; font-weight: 800; }
.lvl-badge.l1 { background: rgba(200,146,42,.15); color: var(--gold-d); }
.lvl-badge.l2 { background: rgba(14,36,20,.13);   color: var(--indigo); }
.lvl-badge.l3 { background: rgba(15,123,92,.12);  color: var(--jade); }
.lvl-badge.l4 { background: rgba(19,160,119,.1);  color: var(--jade-l); }
.lvl-badge.l5 { background: rgba(232,83,74,.1);   color: var(--coral); }
.lvl-badge.l6 { background: rgba(219,169,74,.12); color: var(--gold-d); }
.lvl-badge.l7 { background: rgba(90,122,98,.1);   color: var(--muted); }

.rate-pill { background: rgba(14,36,20,.09); color: var(--indigo); border-radius: 20px; padding: .15rem .65rem; font-size: .75rem; font-weight: 800; display: inline-block; }
.rate-pill.l1 { background: rgba(200,146,42,.15); color: var(--gold-d); }

.bv-amount         { font-family: 'Cinzel','Georgia',serif; font-size: .85rem; font-weight: 700; color: var(--jade); }
.bv-amount.zero    { color: var(--muted); font-weight: 400; font-family: inherit; font-size: .82rem; }
.earn-amount       { font-family: 'Cinzel','Georgia',serif; font-size: .85rem; font-weight: 700; color: var(--gold-d); }
.earn-amount.zero  { color: var(--muted); font-weight: 400; font-family: inherit; font-size: .82rem; }

/* ── Leadership Club notice ── */
.leadership-notice {
  background: linear-gradient(135deg, rgba(14,36,20,.04), rgba(200,146,42,.06));
  border: 1.5px solid rgba(200,146,42,.3);
  border-radius: 14px; padding: 1.1rem 1.4rem;
  display: flex; align-items: flex-start; gap: 1rem;
  margin-top: 1.5rem;
}
.ln-icon  { font-size: 1.5rem; flex-shrink: 0; margin-top: .1rem; }
.ln-body  {}
.ln-title { font-family: 'Cinzel','Georgia',serif; font-size: .88rem; font-weight: 800; color: var(--indigo); margin-bottom: .3rem; }
.ln-text  { font-size: .8rem; color: var(--muted); line-height: 1.65; }
.ln-text strong { color: var(--gold-d); font-weight: 700; }

/* ── Table footer ── */
.table-total-row td { border-top: 2px solid var(--ivory-dd) !important; background: var(--ivory-d); font-weight: 700; }

/* ── Beyond-L7 row ── */
.beyond-row td { background: rgba(200,146,42,.05); font-style: italic; color: var(--muted); }
.beyond-row .bv-amount { color: var(--gold-d); font-style: normal; }

/* ── Responsive ── */
@media (max-width: 600px) {
  .fc-node-box { min-width: 90px; max-width: 116px; padding: 9px 10px 8px; }
  .fc-avatar   { width: 28px; height: 28px; font-size: .8rem; }
  .fc-name     { font-size: .74rem; }
  .fc-node-wrap { padding: 0 8px; }
  .stat-row { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- ── Page Header ── -->
<div class="page-header">
  <div class="ph-arc ph-arc-1"></div>
  <div class="ph-arc ph-arc-2"></div>
  <div class="container">
    <h1>🌳 My Referral Network</h1>
    <p>Your downline across all 7 levels · Partner Sales Bonus (PSB) active</p>
  </div>
</div>

<div class="container page-wrap" style="padding-top:1.75rem">

  <!-- ── Stat Tiles ── -->
  <!-- FIX: 6 tiles now — added "Personal BV" and "Beyond L7 BV" -->
  <div class="stat-row">

    <div class="stat-tile">
      <div class="st-label">Total Members</div>
      <div class="st-value"><?= count($flat) ?></div>
      <div class="st-sub">across all 7 levels</div>
    </div>

    <div class="stat-tile">
      <div class="st-label">Direct Referrals</div>
      <div class="st-value"><?= count($byLevel[1] ?? []) ?></div>
      <div class="st-sub">Level 1 partners</div>
    </div>

    <!-- FIX: Was showing totalGroupBv here but labelled it wrong.
         Now shows PSB-eligible BV (L1–L7 only) which drives the PSB calculation. -->
    <div class="stat-tile gold-top">
      <div class="st-label">PSB-Eligible BV (L1–L7)</div>
      <div class="st-value">₹<?= number_format($psbGroupBv, 0) ?></div>
      <div class="st-sub">BV that generates PSB</div>
    </div>

    <!-- FIX: Read actual credited PSB from commissions table, not recalculated -->
    <div class="stat-tile jade-top">
      <div class="st-label">Total PSB Earned</div>
      <div class="st-value">₹<?= number_format($totalPsbEarned, 0) ?></div>
      <div class="st-sub">credited to your wallet</div>
    </div>

    <!-- FIX: Total group BV (all levels, no cap) — for Leadership Club -->
    <div class="stat-tile coral-top">
      <div class="st-label">Total Group BV</div>
      <div class="st-value">₹<?= number_format($totalGroupBv, 0) ?></div>
      <div class="st-sub">all levels · Leadership Club</div>
    </div>

    <!-- FIX: Beyond-L7 BV shown separately per the document -->
    <?php if ($beyondL7Bv > 0): ?>
    <div class="stat-tile" style="border-top-color:var(--gold-l)">
      <div class="st-label">Beyond L7 BV</div>
      <div class="st-value" style="color:var(--gold-d)">₹<?= number_format($beyondL7Bv, 0) ?></div>
      <div class="st-sub">no PSB · counts for Leadership Club</div>
    </div>
    <?php endif; ?>

  </div>

  <!-- ── Flowchart Tree Card ── -->
  <div class="card">
    <div class="card-header">
      🔀 Network Tree –
      <em><?= e($user['username']) ?></em>
      <span class="badge badge-indigo"><?= count($flat) ?> members</span>
      <span class="badge badge-gold" style="margin-left:.25rem">Group BV ₹<?= number_format($totalGroupBv, 0) ?></span>
    </div>

    <div class="tree-controls">
      <span class="tree-stats">
        <?= count($flat) ?> total members &nbsp;·&nbsp;
        <?= count($byLevel[1] ?? []) ?> direct referrals &nbsp;·&nbsp;
        Group BV ₹<?= number_format($totalGroupBv, 0) ?>
      </span>
    </div>

    <div class="tree-scroll" id="treeScroll">
      <div class="fc-tree" id="fcTree"></div>
    </div>

    <div class="tree-legend">
      <?php
      $legendColors = [1=>'#c8922a',2=>'#0e2414',3=>'#0F7B5C',4=>'#13a077',5=>'#E8534A',6=>'#dba94a',7=>'#E2D5BC'];
      for ($l = 1; $l <= 7; $l++): ?>
        <div class="legend-item">
          <div class="legend-dot" style="background:<?= $legendColors[$l] ?>"></div>
          Level <?= $l ?> (<?= $rates[$l] ?>%)
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- ── Level Summary Table ── -->
  <div class="section-title">📊 Level-wise PSB Summary</div>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Level</th>
            <th>Members</th>
            <th>PSB Rate</th>
            <th>Group BV (₹)</th>
            <!-- FIX: Renamed — shows actual credited PSB from commissions table -->
            <th>PSB Credited (₹)</th>
            <th>Members</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $lvlClasses  = ['','l1','l2','l3','l4','l5','l6','l7'];
        $grandBv     = 0;
        $grandEarned = 0;
        for ($l = 1; $l <= 7; $l++):
            $members    = $byLevel[$l] ?? [];
            $levelBv    = $bvByLevel[$l] ?? 0;
            // FIX: use actual credited PSB from commissions table
            $levelEarn  = $psbByLevel[$l]['earned'] ?? 0;
            $grandBv   += $levelBv;
            $grandEarned += $levelEarn;
        ?>
        <tr>
          <td><span class="lvl-badge <?= $lvlClasses[$l] ?>">Level <?= $l ?></span></td>
          <td><strong><?= count($members) ?></strong></td>
          <td><span class="rate-pill <?= $l === 1 ? 'l1' : '' ?>"><?= $rates[$l] ?>%</span></td>
          <td>
            <?php if ($levelBv > 0): ?>
              <span class="bv-amount">₹<?= number_format($levelBv, 0) ?></span>
            <?php else: ?>
              <span class="bv-amount zero">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($levelEarn > 0): ?>
              <span class="earn-amount">₹<?= number_format($levelEarn, 2) ?></span>
            <?php else: ?>
              <span class="earn-amount zero">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem;color:var(--muted);max-width:220px;white-space:normal">
            <?= $members ? implode(', ', array_map(fn($m) => e($m['username']), $members)) : '–' ?>
          </td>
        </tr>
        <?php endfor; ?>
        </tbody>

        <!-- FIX: Beyond-L7 row shown only if such BV exists — makes the document's
             statement ("BV beyond L7 accumulates in total group BV") visible to the user -->
        <?php if ($beyondL7Bv > 0): ?>
        <tbody>
          <tr class="beyond-row">
            <td colspan="3" style="font-size:.8rem">
              Beyond Level 7 <span style="font-size:.72rem">(no PSB · counts for Leadership Club)</span>
            </td>
            <td><span class="bv-amount">₹<?= number_format($beyondL7Bv, 0) ?></span></td>
            <td><span style="font-size:.8rem">—</span></td>
            <td></td>
          </tr>
        </tbody>
        <?php endif; ?>

        <tfoot>
          <tr class="table-total-row">
            <td colspan="3" style="font-family:'Cinzel',serif;font-size:.82rem;color:var(--indigo)">
              Total Group BV (all levels)
            </td>
            <td><span class="bv-amount">₹<?= number_format($totalGroupBv, 0) ?></span></td>
            <td><span class="earn-amount">₹<?= number_format($grandEarned, 2) ?></span></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- ── Leadership Club Notice ── -->
  <div class="leadership-notice">
    <div class="ln-icon">🏆</div>
    <div class="ln-body">
      <div class="ln-title">Leadership Club — Group BV Qualification</div>
      <div class="ln-text">
        PSB is paid up to <strong>Level 7 only</strong> at rates of
        <strong>15% · 8% · 6% · 4% · 3% · 2% · 2%</strong> (total 40% distribution).
        Any Business Volume generated <strong>beyond Level 7</strong> does not earn PSB
        but continues accumulating in your <strong>Total Group Business Volume</strong>,
        contributing toward <strong>Mfills Leadership Club</strong> qualification and
        higher recognition tiers.
        <?php if ($beyondL7Bv > 0): ?>
          You currently have <strong>₹<?= number_format($beyondL7Bv, 0) ?></strong>
          of BV beyond Level 7 counted in your group total.
        <?php endif; ?>
        Your current total group BV (all levels) is
        <strong>₹<?= number_format($totalGroupBv, 0) ?></strong>.
      </div>
    </div>
  </div>

</div><!-- /container -->

<!-- Tooltip -->
<div class="fc-tooltip" id="fcTooltip"></div>

<script>
var TREE_DATA = <?= $treeJson ?>;
var RATES     = <?= json_encode($rates) ?>;

// FIX: PSB data from commissions table (actual credited amounts per level)
var PSB_BY_LEVEL = <?= json_encode($psbByLevel) ?>;

function buildNode(node, depth) {
  var isRoot     = !!node.isRoot;
  var hasKids    = node.children && node.children.length > 0;
  var initial    = node.username.charAt(0).toUpperCase();
  var lvlLabel   = isRoot ? 'You (Root)' : 'Level ' + node.level;
  var autoExpand = isRoot || depth < 1;

  var wrap = document.createElement('div');
  wrap.className = 'fc-node-wrap' + (hasKids ? ' has-children' : '');
  if (hasKids && !autoExpand) wrap.classList.add('collapsed');

  var box = document.createElement('div');
  box.className = 'fc-node-box' + (isRoot ? ' root' : '') + (hasKids ? ' clickable' : '');
  if (!isRoot) box.dataset.lvl = node.level;
  if (hasKids && !autoExpand) box.classList.add('is-collapsed');

  // FIX: PSB rate comes from server-side RATES array (consistent with PHP)
  var rate    = isRoot ? 0 : (RATES[node.level] || 0);
  var bvAmt   = node.bv || 0;
  // FIX: Tooltip PSB estimate — informational only; actual credited PSB
  //      is in PSB_BY_LEVEL (per-level totals from commissions table).
  var earnEst = Math.round(bvAmt * rate / 100);

  box.innerHTML =
    '<div class="fc-avatar">' + initial + '</div>' +
    '<div class="fc-name">'   + escHtml(node.username) + '</div>' +
    '<div class="fc-meta">'   + lvlLabel + ' · ' + node.joined + '</div>' +
    '<div class="fc-wallet">Wallet ₹' + formatNum(node.wallet) + '</div>' +
    (bvAmt > 0 ? '<div class="fc-bv-pill">BV ₹' + formatNum(bvAmt) + '</div>' : '') +
    (hasKids ? '<div class="fc-expand-hint">' + (autoExpand ? '−' : '+') + '</div>' : '');

  box.addEventListener('mouseenter', function(e) { showTip(e, node, isRoot, rate, bvAmt, earnEst); });
  box.addEventListener('mousemove',  function(e) { moveTip(e); });
  box.addEventListener('mouseleave', hideTip);

  wrap.appendChild(box);

  if (hasKids) {
    var childrenDiv = document.createElement('div');
    childrenDiv.className = 'fc-children' + (autoExpand ? '' : ' hidden');

    var connector = document.createElement('div');
    connector.className = 'fc-connector';
    childrenDiv.appendChild(connector);

    var row = document.createElement('div');
    row.className = 'fc-children-row';
    node.children.forEach(function(child) { row.appendChild(buildNode(child, depth + 1)); });
    childrenDiv.appendChild(row);
    wrap.appendChild(childrenDiv);

    requestAnimationFrame(function() { drawHLine(connector, row); });

    box.addEventListener('click', function(e) {
      e.stopPropagation();
      var isOpen = !childrenDiv.classList.contains('hidden');
      childrenDiv.classList.toggle('hidden', isOpen);
      wrap.classList.toggle('collapsed', isOpen);
      box.classList.toggle('is-collapsed', isOpen);
      var hint = box.querySelector('.fc-expand-hint');
      if (hint) hint.textContent = isOpen ? '+' : '−';
      if (!isOpen) setTimeout(function() { drawHLine(connector, row); }, 30);
    });
  }

  return wrap;
}

function drawHLine(connector, row) {
  var old = connector.querySelector('.fc-h-line');
  if (old) old.remove();
  var kids = row.querySelectorAll(':scope > .fc-node-wrap > .fc-node-box');
  if (kids.length < 2) return;
  var rowRect   = row.getBoundingClientRect();
  var firstRect = kids[0].getBoundingClientRect();
  var lastRect  = kids[kids.length - 1].getBoundingClientRect();
  var left  = firstRect.left + firstRect.width / 2 - rowRect.left;
  var right = lastRect.left  + lastRect.width  / 2 - rowRect.left;
  var line = document.createElement('div');
  line.className  = 'fc-h-line';
  line.style.left = left + 'px';
  line.style.width = (right - left) + 'px';
  connector.appendChild(line);
}

var fcTree = document.getElementById('fcTree');
fcTree.appendChild(buildNode(TREE_DATA, 0));

// FIX: Use setTimeout instead of rAF for re-expand redraw (fixes hidden element bounding rect issue)
window.addEventListener('load', function() {
  setTimeout(function() {
    fcTree.querySelectorAll('.fc-connector').forEach(function(con) {
      var row = con.nextElementSibling;
      if (row && !con.closest('.fc-children.hidden')) drawHLine(con, row);
    });
  }, 50);
});

var resizeTimer;
window.addEventListener('resize', function() {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function() {
    fcTree.querySelectorAll('.fc-connector').forEach(function(con) {
      var row = con.nextElementSibling;
      if (row && !con.closest('.fc-children.hidden')) drawHLine(con, row);
    });
  }, 150);
});

/* Drag-to-pan */
(function() {
  var el = document.getElementById('treeScroll');
  var down = false, sx, sy, sl, st;
  el.addEventListener('mousedown', function(e) {
    if (e.target.closest('.fc-node-box')) return;
    down = true; sx = e.clientX; sy = e.clientY; sl = el.scrollLeft; st = el.scrollTop;
    el.style.cursor = 'grabbing';
  });
  document.addEventListener('mousemove', function(e) {
    if (!down) return;
    el.scrollLeft = sl - (e.clientX - sx); el.scrollTop = st - (e.clientY - sy);
  });
  document.addEventListener('mouseup', function() { down = false; el.style.cursor = ''; });
})();

/* Tooltip */
var tip = document.getElementById('fcTooltip');
function showTip(e, node, isRoot, rate, bvAmt, earnEst) {
  // FIX: Tooltip now shows actual credited PSB for that level (from commissions table)
  //      in addition to the per-node BV estimate
  var lvlCredits = (!isRoot && PSB_BY_LEVEL[node.level])
    ? PSB_BY_LEVEL[node.level].earned : 0;

  tip.innerHTML =
    '<strong>' + escHtml(node.username) + '</strong>' +
    '<span>' + (isRoot ? 'Root (You)' : 'Level ' + node.level + ' — PSB rate: ' + rate + '%') + '</span>' +
    '<span>Joined: ' + node.joined + '</span>' +
    '<span>Wallet: ₹' + formatNum(node.wallet) + '</span>' +
    (bvAmt > 0
      ? '<span class="tip-bv">BV generated: ₹' + formatNum(bvAmt) + '</span>' +
        (!isRoot && earnEst > 0
          ? '<span class="tip-earn">Est. PSB from this node: ₹' + formatNum(earnEst) + '</span>'
          : '')
      : '<span>No BV yet</span>') +
    (!isRoot && lvlCredits > 0
      ? '<span class="tip-earn">Total PSB credited from L' + node.level + ': ₹' + formatNum(lvlCredits) + '</span>'
      : '') +
    (node.children && node.children.length
      ? '<span>' + node.children.length + ' direct referral' + (node.children.length > 1 ? 's' : '') + '</span>'
      : '');

  moveTip(e);
  tip.classList.add('show');
}
function moveTip(e) { tip.style.left = (e.clientX + 14) + 'px'; tip.style.top = (e.clientY - 10) + 'px'; }
function hideTip()  { tip.classList.remove('show'); }

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatNum(n) {
  return Number(n).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>