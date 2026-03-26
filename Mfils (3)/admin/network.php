<?php
// admin/network.php
require_once __DIR__ . '/config.php';
$pageTitle = 'MLM Network';
$pdo = db();

$q       = trim($_GET['q'] ?? '');
$view_id = (int)($_GET['view'] ?? 0);

// Search
$searched = [];
if ($q) {
    $stmt = $pdo->prepare("SELECT id,username,email,referral_code,wallet FROM users WHERE username LIKE ? OR referral_code LIKE ? LIMIT 20");
    $stmt->execute(["%$q%", "%$q%"]);
    $searched = $stmt->fetchAll();
}

// Build tree HTML recursively — brand colors per level
function renderTree(PDO $pdo, int $uid, int $depth = 0, int $maxDepth = 7): string {
    if ($depth >= $maxDepth) return '';

    $stmt = $pdo->prepare("SELECT id,username,email,wallet,referral_code,is_active,mbpin FROM users WHERE referrer_id=? ORDER BY created_at ASC");
    $stmt->execute([$uid]);
    $children = $stmt->fetchAll();
    if (!$children) return '';

    // Brand-aligned level colors
    $lvlColors  = ['#C8922A','#1C3D24','#0F7B5C','#2a5c34','#a87520','#3a8a4a','#0e2414'];
    $lvlBgs     = ['rgba(200,146,42,.08)','rgba(28,61,36,.07)','rgba(15,123,92,.07)','rgba(42,92,52,.07)','rgba(168,117,32,.07)','rgba(58,138,74,.07)','rgba(14,36,20,.06)'];
    $color  = $lvlColors[$depth % count($lvlColors)];
    $bg     = $lvlBgs[$depth % count($lvlBgs)];
    $indent = $depth === 0 ? '0' : '28px';
    $border = $depth > 0 ? '2px solid rgba(200,146,42,.18)' : 'none';

    // Count direct children per node
    $childCounts = [];
    foreach ($children as $c) {
        $cs = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id=?");
        $cs->execute([$c['id']]);
        $childCounts[$c['id']] = (int)$cs->fetchColumn();
    }

    $html = '<ul class="tree-ul" style="padding-left:'.$indent.';border-left:'.$border.'">';
    foreach ($children as $c) {
        $sub      = renderTree($pdo, $c['id'], $depth + 1, $maxDepth);
        $hasKids  = $sub !== '';
        $kidCount = $childCounts[$c['id']];
        $lvlLabel = 'L' . ($depth + 1);

        $html .= '<li class="tree-li">';
        $html .= '<div class="tree-node" style="border-left-color:'.$color.';background:'.$bg.'"'
               . ($hasKids ? ' onclick="toggleNode(this)" role="button"' : '')
               . ' title="'.htmlspecialchars($c['email'] ?? '').'">';

        // Toggle arrow
        if ($hasKids) {
            $html .= '<span class="tree-arrow" style="color:'.$color.'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:11px;height:11px"><polyline points="9 18 15 12 9 6"/></svg></span>';
        } else {
            $html .= '<span class="tree-leaf" style="color:'.$color.'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:9px;height:9px"><circle cx="12" cy="12" r="3" fill="currentColor"/></svg></span>';
        }

        // Level badge
        $html .= '<span class="tree-lvl" style="background:'.$color.';color:#fff">'.$lvlLabel.'</span>';

        // Username
        $html .= '<span class="tree-name">'.htmlspecialchars($c['username']).'</span>';

        // Referral code
        $html .= '<code class="tree-code" style="color:'.$color.'">'.htmlspecialchars($c['referral_code']).'</code>';

        // Wallet
        $html .= '<span class="tree-wallet">₹'.number_format($c['wallet'], 2).'</span>';

        // Kids count
        if ($kidCount > 0) {
            $html .= '<span class="tree-kids" style="background:'.$color.';opacity:.85">'.$kidCount.' ↓</span>';
        }

        // Status
        $html .= '<span class="tree-status '.($c['is_active'] ? 'tree-active' : 'tree-inactive').'">'.($c['is_active'] ? 'Active' : 'Inactive').'</span>';

        // View link
        $html .= '<a href="?view='.$c['id'].'" class="tree-view-btn" onclick="event.stopPropagation()" title="View full tree">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:11px;height:11px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </a>';

        $html .= '</div>';

        if ($hasKids) {
            $html .= '<div class="sub-tree">'.$sub.'</div>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

// View specific user
$viewUser = null;
if ($view_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$view_id]);
    $viewUser = $stmt->fetch();
}

// Top-level users
$tops = $pdo->query("SELECT id,username,referral_code,wallet,is_active FROM users WHERE referrer_id IS NULL ORDER BY id")->fetchAll();

// Stats
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$no_referrer    = $pdo->query("SELECT COUNT(*) FROM users WHERE referrer_id IS NULL")->fetchColumn();
$with_referrals = $pdo->query("SELECT COUNT(DISTINCT referrer_id) FROM users WHERE referrer_id IS NOT NULL")->fetchColumn();
$total_wallet   = $pdo->query("SELECT COALESCE(SUM(wallet),0) FROM users")->fetchColumn();

require_once '_layout.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Outfit:wght@300;400;500;600;700;800&display=swap');

:root {
  --g-dd:   #0e2414;
  --g-d:    #1C3D24;
  --g:      #2a5c34;
  --g-l:    #3a8a4a;
  --gold:   #C8922A;
  --gold-d: #a87520;
  --gold-l: #e0b050;
  --cream:  #FDFAF4;
  --cream-d:#F3EDE0;
  --border: #DDD5C4;
  --muted:  #6b8a72;
  --ink:    #0e2414;
}

/* ── HERO ── */
.network-hero {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 55%, var(--g) 100%);
  border-radius: 16px; padding: 1.5rem 1.75rem;
  margin-bottom: 1.5rem; border-bottom: 3px solid var(--gold);
  box-shadow: 0 8px 32px rgba(14,36,20,.2);
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap; position: relative; overflow: hidden;
}
.network-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.07) 1.5px, transparent 1.5px);
  background-size: 22px 22px; pointer-events: none;
}
.network-hero-left { position: relative; z-index: 1; }
.network-hero h1 {
  font-family: 'Cinzel', serif; color: #fff;
  font-size: 1.35rem; font-weight: 700; margin-bottom: .2rem;
}
.network-hero p { color: rgba(255,255,255,.5); font-size: .8rem; }

/* ── STAT CARDS ── */
.net-stats {
  display: grid; grid-template-columns: repeat(4,1fr);
  gap: 1rem; margin-bottom: 1.5rem;
}
.net-stat {
  background: #fff; border-radius: 14px;
  padding: 1.1rem 1.2rem;
  border: 1.5px solid var(--border);
  border-top: 3px solid var(--border);
  box-shadow: 0 3px 14px rgba(14,36,20,.06);
  transition: transform .2s, box-shadow .2s;
  display: flex; flex-direction: column; gap: .2rem;
}
.net-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(14,36,20,.1); }
.net-stat.s-gold   { border-top-color: var(--gold); }
.net-stat.s-green  { border-top-color: var(--g-d); }
.net-stat.s-jade   { border-top-color: #0F7B5C; }
.net-stat.s-light  { border-top-color: var(--g-l); }
.ns-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: .3rem;
}
.ns-icon svg { width: 16px; height: 16px; }
.ns-label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
.ns-value { font-family: 'Cinzel', serif; font-size: 1.5rem; font-weight: 700; color: var(--ink); line-height: 1; }
.net-stat.s-gold  .ns-value { color: var(--gold-d); }
.net-stat.s-green .ns-value { color: var(--g-d); }
.ns-sub { font-size: .7rem; color: var(--muted); }

/* ── SEARCH BAR ── */
.net-toolbar {
  background: #fff; border: 1.5px solid var(--border);
  border-radius: 12px; padding: .85rem 1.1rem;
  display: flex; align-items: center; gap: .75rem;
  margin-bottom: 1.5rem; flex-wrap: wrap;
  box-shadow: 0 2px 10px rgba(14,36,20,.05);
}
.net-title {
  font-family: 'Cinzel', serif; font-size: 1rem;
  font-weight: 700; color: var(--g-d); margin-right: auto;
  display: flex; align-items: center; gap: .6rem;
}
.net-title svg { width: 18px; height: 18px; }
.search-wrap { display: flex; gap: 0; flex-shrink: 0; }
.search-wrap input {
  padding: .58rem .9rem .58rem 2.2rem; width: 220px;
  border: 1.5px solid var(--border); border-right: none;
  border-radius: 9px 0 0 9px;
  font-family: 'Outfit', sans-serif; font-size: .82rem;
  color: var(--ink); background: var(--cream); outline: none;
  transition: border-color .2s;
}
.search-wrap input:focus { border-color: var(--g-d); background: #fff; }
.search-wrap-inner { position: relative; }
.search-wrap-inner .s-ico {
  position: absolute; left: .7rem; top: 50%;
  transform: translateY(-50%); pointer-events: none;
}
.search-wrap-inner .s-ico svg { width: 14px; height: 14px; color: var(--muted); }
.btn-search {
  padding: .58rem 1rem; background: linear-gradient(135deg, var(--g-dd), var(--g));
  color: #fff; border: none; border-radius: 0 9px 9px 0;
  font-family: 'Outfit', sans-serif; font-size: .78rem; font-weight: 700;
  cursor: pointer; transition: all .2s; white-space: nowrap;
  display: flex; align-items: center; gap: .35rem;
}
.btn-search:hover { background: linear-gradient(135deg, var(--g), var(--g-l)); }
.btn-reset {
  padding: .55rem .9rem; background: transparent;
  border: 1.5px solid var(--border); border-radius: 9px;
  font-family: 'Outfit', sans-serif; font-size: .75rem; font-weight: 600;
  color: var(--muted); cursor: pointer; transition: all .2s;
  text-decoration: none; display: inline-flex; align-items: center; gap: .3rem;
}
.btn-reset:hover { border-color: var(--g-d); color: var(--g-d); background: var(--cream); }

/* ── SEARCH RESULTS TABLE ── */
.net-card {
  background: #fff; border-radius: 16px;
  border: 1.5px solid var(--border);
  box-shadow: 0 4px 20px rgba(14,36,20,.07);
  overflow: hidden; margin-bottom: 1.5rem;
}
.net-card-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: .9rem 1.25rem; border-bottom: 1.5px solid var(--border);
  background: linear-gradient(135deg, var(--cream), #edf5ee);
}
.net-card-head h3 {
  font-family: 'Cinzel', serif; font-size: .92rem;
  font-weight: 700; color: var(--g-d);
  display: flex; align-items: center; gap: .5rem;
}
.net-card table { width: 100%; border-collapse: collapse; font-family: 'Outfit', sans-serif; }
.net-card thead th {
  background: linear-gradient(135deg, var(--g-dd), var(--g-d)) !important;
  color: rgba(255,255,255,.8) !important;
  -webkit-text-fill-color: rgba(255,255,255,.8) !important;
  font-size: .65rem; text-transform: uppercase;
  letter-spacing: .1em; font-weight: 700;
  padding: .8rem 1rem; text-align: left; white-space: nowrap;
  border-bottom: 2px solid var(--gold) !important;
}
.net-card tbody tr { border-bottom: 1px solid var(--cream-d); transition: background .12s; }
.net-card tbody tr:last-child { border-bottom: none; }
.net-card tbody tr:hover { background: var(--cream); }
.net-card tbody td { padding: .8rem 1rem; vertical-align: middle; font-size: .84rem; }

.search-avatar {
  width: 34px; height: 34px; border-radius: 9px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 800; color: var(--gold-l);
  flex-shrink: 0; font-family: 'Cinzel', serif;
}
.search-code {
  background: rgba(200,146,42,.1); color: var(--gold-d);
  padding: .2rem .65rem; border-radius: 20px;
  font-size: .72rem; font-weight: 800;
  border: 1px solid rgba(200,146,42,.2);
  font-family: monospace;
}
.btn-view-tree {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .35rem .85rem; border-radius: 8px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  color: #fff; font-size: .72rem; font-weight: 700;
  text-decoration: none; font-family: 'Outfit', sans-serif;
  transition: all .2s; border: none;
}
.btn-view-tree:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14,36,20,.25); color: #fff; }

/* ── ROOT NODE ── */
.root-node {
  display: inline-flex; align-items: center; gap: .65rem;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  color: #fff; padding: .65rem 1.1rem; border-radius: 12px;
  margin-bottom: 1rem; box-shadow: 0 6px 20px rgba(14,36,20,.25);
  border: 1px solid rgba(200,146,42,.3);
}
.root-name { font-family: 'Cinzel', serif; font-size: .95rem; font-weight: 700; }
.root-code {
  font-size: .72rem; color: var(--gold-l);
  background: rgba(200,146,42,.2); padding: .15rem .6rem;
  border-radius: 20px; font-family: monospace; font-weight: 700;
}
.root-wallet { font-size: .78rem; opacity: .7; }
.root-badge {
  background: var(--gold); color: var(--g-dd);
  font-size: .65rem; font-weight: 800; padding: .2rem .65rem;
  border-radius: 20px; letter-spacing: .06em; text-transform: uppercase;
}

/* ── TREE STYLES ── */
.tree-ul {
  list-style: none; margin: 0;
  transition: all .2s;
}
.tree-li { margin: 5px 0; position: relative; }

.tree-node {
  display: inline-flex; align-items: center; gap: .5rem;
  background: #fff; border: 1.5px solid var(--border);
  border-left: 3px solid var(--gold);
  border-radius: 10px; padding: .45rem .85rem;
  cursor: default; transition: all .18s;
  font-family: 'Outfit', sans-serif;
  box-shadow: 0 1px 4px rgba(14,36,20,.04);
  flex-wrap: nowrap;
}
.tree-node[onclick] { cursor: pointer; }
.tree-node[onclick]:hover {
  box-shadow: 0 4px 16px rgba(14,36,20,.12);
  transform: translateX(3px);
}
.tree-arrow, .tree-leaf {
  flex-shrink: 0; display: flex; align-items: center;
  transition: transform .2s;
}
.tree-node.open .tree-arrow { transform: rotate(90deg); }

.tree-lvl {
  font-size: .6rem; font-weight: 800; padding: .12rem .45rem;
  border-radius: 8px; letter-spacing: .05em; flex-shrink: 0;
  font-family: 'Outfit', sans-serif;
}
.tree-name { font-weight: 700; font-size: .84rem; color: var(--g-d); white-space: nowrap; }
.tree-code {
  font-size: .68rem; font-weight: 700;
  background: rgba(200,146,42,.1);
  padding: .12rem .5rem; border-radius: 10px;
  font-family: monospace; white-space: nowrap;
}
.tree-wallet { font-size: .72rem; color: var(--muted); white-space: nowrap; }
.tree-kids {
  font-size: .6rem; font-weight: 800; color: #fff;
  padding: .1rem .4rem; border-radius: 8px; flex-shrink: 0;
}
.tree-status {
  font-size: .62rem; font-weight: 800; padding: .12rem .55rem;
  border-radius: 20px; flex-shrink: 0; text-transform: uppercase; letter-spacing: .04em;
}
.tree-active   { background: rgba(15,123,92,.1); color: #0a6644; }
.tree-inactive { background: rgba(192,57,43,.08); color: #C0392B; }
.tree-view-btn {
  display: flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0;
  background: rgba(28,61,36,.08); color: var(--g-d);
  text-decoration: none; transition: all .18s;
}
.tree-view-btn:hover { background: var(--g-d); color: #fff; transform: scale(1.1); }

.sub-tree { display: none; padding-left: 8px; margin-top: 2px; }

/* ── TOP LEVEL MEMBER CARD ── */
.top-member-card {
  background: #fff; border-radius: 12px;
  border: 1.5px solid var(--border); border-left: 4px solid var(--gold);
  padding: .75rem 1.1rem; margin-bottom: 1rem;
  display: flex; align-items: center; gap: .85rem;
  box-shadow: 0 2px 10px rgba(14,36,20,.06);
  flex-wrap: wrap;
}
.top-member-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; font-weight: 800; color: var(--gold-l);
  flex-shrink: 0; font-family: 'Cinzel', serif;
}
.top-member-name { font-weight: 700; font-size: .9rem; color: var(--g-d); font-family: 'Cinzel', serif; }
.top-member-code {
  background: rgba(200,146,42,.1); color: var(--gold-d);
  padding: .18rem .65rem; border-radius: 20px;
  font-size: .7rem; font-weight: 800; border: 1px solid rgba(200,146,42,.2);
  font-family: monospace;
}
.top-member-wallet { font-size: .78rem; color: var(--muted); }

/* ── LEVEL LEGEND ── */
.level-legend {
  display: flex; gap: .5rem; flex-wrap: wrap;
  padding: .75rem 1.25rem; border-bottom: 1.5px solid var(--border);
  background: var(--cream);
}
.legend-item {
  display: flex; align-items: center; gap: .35rem;
  font-size: .68rem; font-weight: 700; font-family: 'Outfit', sans-serif;
}
.legend-dot {
  width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0;
}

/* ── EXPAND ALL BUTTON ── */
.tree-controls {
  display: flex; gap: .5rem; align-items: center;
  padding: .7rem 1.25rem; border-bottom: 1.5px solid var(--border);
  background: #fff;
}
.ctrl-btn {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .38rem .85rem; border-radius: 8px; border: 1.5px solid var(--border);
  background: #fff; color: var(--muted); font-size: .72rem; font-weight: 700;
  cursor: pointer; transition: all .18s; font-family: 'Outfit', sans-serif;
}
.ctrl-btn:hover { border-color: var(--g-d); color: var(--g-d); background: var(--cream); }
.ctrl-btn svg { width: 12px; height: 12px; }

/* ── EMPTY ── */
.net-empty { text-align: center; padding: 3rem; color: var(--muted); font-family: 'Outfit', sans-serif; }
.net-empty-icon {
  width: 56px; height: 56px; border-radius: 14px;
  background: linear-gradient(135deg, #dceede, #c8e0cc);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto .75rem;
}

/* ── RESPONSIVE ── */
@media(max-width:768px) {
  .net-stats { grid-template-columns: repeat(2,1fr); }
  .net-toolbar { flex-direction: column; align-items: stretch; }
  .search-wrap input { width: 100%; }
  .tree-node { flex-wrap: wrap; }
}
@media(max-width:480px) {
  .net-stats { grid-template-columns: 1fr 1fr; }
}
</style>

<!-- ══ HERO ══ -->
<div class="network-hero">
  <div class="network-hero-left">
    <h1>MLM Network Tree</h1>
    <p>Visualize the 7-level Partner Sales Bonus network structure</p>
  </div>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="net-stats">
  <div class="net-stat s-green">
    <div class="ns-icon" style="background:rgba(28,61,36,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#1C3D24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="ns-label">Total Members</div>
    <div class="ns-value"><?= number_format($total_users) ?></div>
    <div class="ns-sub">All registered partners</div>
  </div>
  <div class="net-stat s-gold">
    <div class="ns-icon" style="background:rgba(200,146,42,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#a87520" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div class="ns-label">Root Members</div>
    <div class="ns-value"><?= number_format($no_referrer) ?></div>
    <div class="ns-sub">No referrer (top level)</div>
  </div>
  <div class="net-stat s-jade">
    <div class="ns-icon" style="background:rgba(15,123,92,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#0F7B5C" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    </div>
    <div class="ns-label">Active Recruiters</div>
    <div class="ns-value"><?= number_format($with_referrals) ?></div>
    <div class="ns-sub">Have active referrals</div>
  </div>
  <div class="net-stat s-light">
    <div class="ns-icon" style="background:rgba(58,138,74,.1)">
      <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
    </div>
    <div class="ns-label">Total Wallet Balance</div>
    <div class="ns-value" style="font-size:1.25rem">₹<?= number_format((float)$total_wallet, 0) ?></div>
    <div class="ns-sub">All partners combined</div>
  </div>
</div>

<!-- ══ TOOLBAR ══ -->
<form method="GET">
  <div class="net-toolbar">
    <div class="net-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Network Explorer
    </div>
    <div class="search-wrap">
      <div class="search-wrap-inner">
        <span class="s-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input type="text" name="q" placeholder="Search member or referral code…" value="<?= e($q) ?>">
      </div>
      <button type="submit" class="btn-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:13px;height:13px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Search
      </button>
    </div>
    <?php if ($q || $view_id): ?>
      <a href="network.php" class="btn-reset">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Reset
      </a>
    <?php endif; ?>
  </div>
</form>

<!-- ══ SEARCH RESULTS ══ -->
<?php if ($q && $searched): ?>
<div class="net-card">
  <div class="net-card-head">
    <h3>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:15px;height:15px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Search results for "<?= e($q) ?>" — <?= count($searched) ?> found
    </h3>
  </div>
  <div style="overflow-x:auto">
  <table>
    <thead><tr><th>Member</th><th>Referral Code</th><th>Wallet</th><th>Directs</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($searched as $s):
      $dStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id=?");
      $dStmt->execute([$s['id']]);
      $dc = $dStmt->fetchColumn();
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:.65rem">
          <div class="search-avatar"><?= mb_strtoupper(mb_substr($s['username'],0,2)) ?></div>
          <div>
            <div style="font-weight:700;color:var(--g-d);font-size:.875rem"><?= e($s['username']) ?></div>
            <div style="font-size:.7rem;color:var(--muted)"><?= e($s['email']) ?></div>
          </div>
        </div>
      </td>
      <td><span class="search-code"><?= e($s['referral_code']) ?></span></td>
      <td style="font-weight:700;color:var(--gold-d)">₹<?= number_format($s['wallet'],2) ?></td>
      <td>
        <span style="background:rgba(28,61,36,.1);color:var(--g-d);font-weight:800;font-size:.75rem;padding:.2rem .6rem;border-radius:20px"><?= $dc ?></span>
      </td>
      <td>
        <a href="?view=<?= $s['id'] ?>" class="btn-view-tree">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:12px;height:12px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          View Tree (<?= $dc ?> direct)
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php elseif ($q): ?>
<div class="net-card" style="margin-bottom:1.5rem">
  <div class="net-empty">
    <div class="net-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" style="width:24px;height:24px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
    <h3 style="font-family:'Cinzel',serif;color:var(--g-d);margin-bottom:.3rem">No results found</h3>
    <p>Try a different name or referral code</p>
  </div>
</div>
<?php endif; ?>

<!-- ══ SPECIFIC USER TREE ══ -->
<?php if ($viewUser): ?>
<div class="net-card">
  <div class="net-card-head">
    <h3>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:15px;height:15px"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Network of <?= e($viewUser['username']) ?>
    </h3>
    <a href="network.php" class="btn-reset">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:12px;height:12px"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
  </div>

  <!-- Level legend -->
  <div class="level-legend">
    <span style="font-size:.65rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-right:.25rem">Levels:</span>
    <?php
    $legendColors = ['#C8922A','#1C3D24','#0F7B5C','#2a5c34','#a87520','#3a8a4a','#0e2414'];
    for ($li = 1; $li <= 7; $li++):
    ?>
    <span class="legend-item">
      <span class="legend-dot" style="background:<?= $legendColors[$li-1] ?>"></span>
      L<?= $li ?>
    </span>
    <?php endfor; ?>
  </div>

  <!-- Expand/collapse controls -->
  <div class="tree-controls">
    <button type="button" class="ctrl-btn" onclick="expandAll()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="7 13 12 18 17 13"/><polyline points="7 6 12 11 17 6"/></svg>
      Expand All
    </button>
    <button type="button" class="ctrl-btn" onclick="collapseAll()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="17 11 12 6 7 11"/><polyline points="17 18 12 13 7 18"/></svg>
      Collapse All
    </button>
    <span style="font-size:.72rem;color:var(--muted);font-family:'Outfit',sans-serif;margin-left:.25rem">Click any node to expand · <?= e($viewUser['username']) ?>'s network</span>
  </div>

  <div style="padding:1.25rem;overflow-x:auto" id="treeContainer">
    <!-- Root node -->
    <div class="root-node">
      <div style="width:32px;height:32px;border-radius:8px;background:rgba(200,146,42,.2);border:1px solid rgba(200,146,42,.3);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.8rem;font-weight:800;color:var(--gold-l)"><?= mb_strtoupper(mb_substr($viewUser['username'],0,2)) ?></div>
      <span class="root-name"><?= e($viewUser['username']) ?></span>
      <span class="root-code"><?= e($viewUser['referral_code']) ?></span>
      <span class="root-wallet">₹<?= number_format($viewUser['wallet'],2) ?></span>
      <span class="root-badge">Root</span>
    </div>
    <?= renderTree($pdo, (int)$viewUser['id']) ?>
  </div>
</div>

<?php else: ?>
<!-- ══ ALL TOP-LEVEL TREES ══ -->
<div class="net-card">
  <div class="net-card-head">
    <h3>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:15px;height:15px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Top-Level Members (<?= count($tops) ?>)
    </h3>
    <span style="font-size:.75rem;color:var(--muted);font-family:'Outfit',sans-serif">Click member to expand · Use "View Tree" for full network</span>
  </div>

  <div style="padding:1.25rem">
    <?php if (empty($tops)): ?>
      <div class="net-empty">
        <div class="net-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" style="width:24px;height:24px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <h3 style="font-family:'Cinzel',serif;color:var(--g-d);margin-bottom:.3rem">No members yet</h3>
        <p>Members will appear here once they register</p>
      </div>
    <?php else: foreach ($tops as $t):
      $dStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id=?");
      $dStmt->execute([$t['id']]);
      $dc = $dStmt->fetchColumn();
    ?>
    <div>
      <div class="top-member-card">
        <div class="top-member-avatar"><?= mb_strtoupper(mb_substr($t['username'],0,2)) ?></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
            <span class="top-member-name"><?= e($t['username']) ?></span>
            <span class="top-member-code"><?= e($t['referral_code']) ?></span>
            <span class="top-member-wallet">₹<?= number_format($t['wallet'],2) ?></span>
          </div>
        </div>
        <a href="?view=<?= $t['id'] ?>" class="btn-view-tree">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:12px;height:12px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Full Tree (<?= $dc ?> direct)
        </a>
      </div>
      <?= renderTree($pdo, $t['id'], 0, 2) ?>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php endif; ?>

<script>
/* ── Toggle node ── */
function toggleNode(el) {
  var sub   = el.nextElementSibling;
  var arrow = el.querySelector('.tree-arrow');
  if (!sub) return;
  var isOpen = sub.style.display !== 'none';
  if (isOpen) {
    sub.style.display = 'none';
    el.classList.remove('open');
    el.style.boxShadow = '';
  } else {
    sub.style.display = 'block';
    el.classList.add('open');
    el.style.boxShadow = '0 4px 16px rgba(14,36,20,.1)';
  }
}

/* ── Expand all ── */
function expandAll() {
  document.querySelectorAll('.sub-tree').forEach(function(el) {
    el.style.display = 'block';
  });
  document.querySelectorAll('.tree-node[onclick]').forEach(function(el) {
    el.classList.add('open');
    el.style.boxShadow = '0 4px 16px rgba(14,36,20,.1)';
  });
}

/* ── Collapse all ── */
function collapseAll() {
  document.querySelectorAll('.sub-tree').forEach(function(el) {
    el.style.display = 'none';
  });
  document.querySelectorAll('.tree-node[onclick]').forEach(function(el) {
    el.classList.remove('open');
    el.style.boxShadow = '';
  });
}
</script>

<?php require_once '_footer.php'; ?>