<?php
// admin/index.php
require_once __DIR__ . '/config.php';
requireAdmin();

$pageTitle = 'Dashboard';
$pdo = db();

$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$total_orders  = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='completed'")->fetchColumn();
$total_comm    = $pdo->query("SELECT COALESCE(SUM(commission_amt),0) FROM commissions")->fetchColumn();
$total_products= $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$new_today     = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$orders_today  = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$rev_today     = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='completed' AND DATE(created_at)=CURDATE()")->fetchColumn();
$pending_with  = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();

// ── PSB Saturday Reminder ──
$is_saturday     = (date('w') === '6');
$psb_pending_amt = (float)$pdo->query("SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE status != 'credited'")->fetchColumn();
$psb_pending_cnt = (int)$pdo->query("SELECT COUNT(*) FROM commissions WHERE status != 'credited'")->fetchColumn();

$lvl_stats     = $pdo->query("SELECT level,COUNT(*) as cnt,SUM(commission_amt) as total FROM commissions GROUP BY level ORDER BY level")->fetchAll();
$recent_users  = $pdo->query("SELECT id,username,email,wallet,referral_code,is_active,created_at FROM users ORDER BY created_at DESC LIMIT 8")->fetchAll();
$recent_orders = $pdo->query("SELECT o.*,u.username,p.name as product_name FROM orders o LEFT JOIN users u ON u.id=o.user_id LEFT JOIN products p ON p.id=o.product_id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();
$top_earners   = $pdo->query("SELECT u.username,u.email,COUNT(c.id) as txns,SUM(c.commission_amt) as earned FROM commissions c JOIN users u ON u.id=c.beneficiary_id GROUP BY c.beneficiary_id ORDER BY earned DESC LIMIT 6")->fetchAll();

// ── Graph data: Last 7 days revenue + orders
$rev_7d = $pdo->query("
  SELECT DATE(created_at) as d, COALESCE(SUM(amount),0) as rev, COUNT(*) as cnt
  FROM orders WHERE status='completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(created_at) ORDER BY d ASC
")->fetchAll(PDO::FETCH_ASSOC);

$rev_map = [];
foreach ($rev_7d as $r) $rev_map[$r['d']] = $r;
$chart_labels = $chart_rev = $chart_orders = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $chart_labels[] = date('d M', strtotime($date));
  $chart_rev[]    = isset($rev_map[$date]) ? (float)$rev_map[$date]['rev'] : 0;
  $chart_orders[] = isset($rev_map[$date]) ? (int)$rev_map[$date]['cnt']   : 0;
}

// ── New users last 7 days
$usr_7d = $pdo->query("
  SELECT DATE(created_at) as d, COUNT(*) as cnt
  FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_ASSOC);
$usr_map = [];
foreach ($usr_7d as $r) $usr_map[$r['d']] = (int)$r['cnt'];
$chart_users = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $chart_users[] = $usr_map[$date] ?? 0;
}

// ── Order status donut
$status_rows = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$status_map  = [];
foreach ($status_rows as $r) $status_map[$r['status']] = (int)$r['cnt'];

// ── Club BV thresholds from business plan
$club_thresholds = [
  'RSC' => ['name'=>'Rising Star',      'bv'=>50000,    'color'=>'#5db870', 'icon'=>'⭐'],
  'PC'  => ['name'=>'Prestige Club',    'bv'=>200000,   'color'=>'#C8922A', 'icon'=>'🏆'],
  'GAC' => ['name'=>'Global Ambassador','bv'=>1000000,  'color'=>'#3a8a4a', 'icon'=>'🌍'],
  'CC'  => ['name'=>'Chairman Club',    'bv'=>5000000,  'color'=>'#0e2414', 'icon'=>'👑'],
];

// ── PSB levels from business plan
$psb_levels = [1=>15, 2=>8, 3=>6, 4=>4, 5=>3, 6=>2, 7=>2];

require_once '_layout.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<style>
/* ══════════════════════════════════════════════
   MFILLS ADMIN DASHBOARD
   Forest Green · Gold · Cream — Brand Aligned
   Per Business Plan: PSB 7 Levels, 4 Club Ranks
══════════════════════════════════════════════ */
:root {
  --g-dd:    #0e2414;
  --g-d:     #1C3D24;
  --g:       #2a5c34;
  --g-l:     #3a8a4a;
  --g-ll:    #5db870;
  --gold:    #C8922A;
  --gold-d:  #a87520;
  --gold-l:  #e0b050;
  --gold-p:  #FFF8E7;
  --cream:   #FDFAF4;
  --cream-d: #F3EDE0;
  --border:  #DDD5C4;
  --muted:   #6b8a72;
  --ink:     #0e2414;
  --white:   #ffffff;
  --danger:  #b91c1c;
  --success: #1C3D24;
  --warn:    #92640a;
}

*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Outfit', sans-serif; background: var(--cream); color: var(--ink); }

.dash-wrap { padding: 1.75rem 2rem 3rem; max-width: 1400px; }

/* ══ WELCOME BANNER ══ */
.welcome-bar {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 45%, var(--g) 75%, var(--g-l) 100%);
  border-radius: 18px; padding: 1.6rem 2rem;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.75rem;
  box-shadow: 0 10px 36px rgba(14,36,20,.28);
  overflow: hidden; position: relative;
  animation: fadeUp .4s ease both;
}
.welcome-bar::after {
  content: 'MFILLS'; position: absolute;
  right: -20px; top: 50%; transform: translateY(-50%);
  font-family: 'Cinzel', serif; font-size: 7rem; font-weight: 900;
  color: rgba(255,255,255,.04); letter-spacing: .2em;
  pointer-events: none; white-space: nowrap;
}
.wb-left h1 {
  font-family: 'Cinzel', serif;
  font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1.2;
}
.wb-left p { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: .35rem; }
.wb-brand {
  display: inline-flex; align-items: center; gap: .5rem;
  background: rgba(200,146,42,.2); border: 1px solid rgba(200,146,42,.35);
  border-radius: 20px; padding: .25rem .85rem;
  font-size: .65rem; font-weight: 700; color: var(--gold-l);
  letter-spacing: .1em; text-transform: uppercase; margin-bottom: .6rem;
}
.wb-right { text-align: right; flex-shrink: 0; position: relative; z-index: 1; }
.wb-date { font-size: .68rem; color: rgba(255,255,255,.45); letter-spacing: .08em; text-transform: uppercase; }
.wb-rev {
  font-family: 'Cinzel', serif; font-size: 2rem;
  font-weight: 700; color: var(--gold-l); line-height: 1.1; margin-top: .25rem;
}
.wb-revlabel { font-size: .68rem; color: rgba(255,255,255,.4); }

/* ══ PENDING ALERT ══ */
.alert-pending {
  background: linear-gradient(to right,#fff5f5,var(--white));
  border: 1.5px solid #fca5a5; border-radius: 12px;
  padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;
  margin-bottom: 1.5rem; animation: fadeUp .4s .1s ease both;
}
.alert-pending .ap-icon { font-size: 1.75rem; }
.alert-pending strong { color: var(--danger); font-size: .88rem; }
.alert-pending p { font-size: .78rem; color: var(--muted); margin-top: 2px; }
.alert-pending a { color: var(--danger); font-weight: 700; text-decoration: none; }

/* ══ SECTION LABEL ══ */
.section-label {
  display: flex; align-items: center; gap: .75rem;
  margin-bottom: 1.1rem; margin-top: 2.1rem;
}
.section-label::before {
  content: ''; display: block; width: 3px; height: 22px;
  background: linear-gradient(to bottom, var(--gold), var(--g-l));
  border-radius: 3px;
}
.section-label h2 {
  font-family: 'Cinzel', serif; font-size: 1rem;
  font-weight: 700; color: var(--g-d); letter-spacing: .04em;
}
.section-label .sl-sub {
  font-size: .68rem; color: var(--muted);
  font-family: 'Outfit', sans-serif; font-weight: 400;
}
.section-label .sl-line { flex: 1; height: 1px; background: linear-gradient(to right, var(--border), transparent); }

/* ══ STATS GRID ══ */
.stats-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.1rem; }
@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } }

.stat-card {
  position: relative; background: var(--white); border-radius: 16px;
  padding: 1.35rem 1.4rem 1.15rem; overflow: hidden;
  border: 1.5px solid var(--border);
  box-shadow: 0 2px 12px rgba(14,36,20,.06);
  transition: transform .22s ease, box-shadow .22s ease;
  animation: fadeUp .5s ease both;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(14,36,20,.12); }
.stat-card::before {
  content: ''; position: absolute; top: 0; right: 0;
  width: 80px; height: 80px;
  background: linear-gradient(135deg, transparent 50%, var(--accent, #e8f2ea) 50%);
  border-radius: 0 16px 0 0; opacity: .6;
}
.sc-top {
  display: flex; align-items: flex-start;
  justify-content: space-between; margin-bottom: .9rem;
}
.sc-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: var(--icon-bg, var(--cream-d));
  display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
}
.sc-trend {
  font-size: .68rem; font-weight: 700; color: var(--g-d);
  background: rgba(28,61,36,.1); padding: 3px 9px; border-radius: 20px;
  letter-spacing: .04em;
}
.sc-trend.warn { color: var(--danger); background: #fdeaea; }
.sc-value {
  font-family: 'Cinzel', serif; font-size: 2rem;
  font-weight: 700; color: var(--ink); line-height: 1;
  margin-bottom: .3rem; letter-spacing: -.02em;
}
.sc-label {
  font-size: .68rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .1em; color: var(--muted); margin-bottom: .1rem;
}
.sc-sub { font-size: .74rem; color: var(--muted); margin-top: .35rem; }
.sc-sub a { color: var(--g-d); font-weight: 700; text-decoration: none; }

.stat-card:nth-child(1){animation-delay:.05s} .stat-card:nth-child(2){animation-delay:.1s}
.stat-card:nth-child(3){animation-delay:.15s} .stat-card:nth-child(4){animation-delay:.2s}
.stat-card:nth-child(5){animation-delay:.25s} .stat-card:nth-child(6){animation-delay:.3s}

/* ══ CHARTS ══ */
.charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; }
@media(max-width:860px){ .charts-row { grid-template-columns: 1fr; } }

.chart-card {
  background: var(--white); border: 1.5px solid var(--border); border-radius: 16px;
  overflow: hidden; box-shadow: 0 2px 12px rgba(14,36,20,.05);
  animation: fadeUp .5s .3s ease both;
}
.chart-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem .75rem; border-bottom: 1px solid var(--cream-d);
  background: linear-gradient(to right, rgba(14,36,20,.03), transparent);
}
.chart-head h3 {
  font-family: 'Cinzel', serif; font-size: .95rem;
  font-weight: 700; color: var(--g-d);
}
.chart-body { padding: 1rem 1.25rem 1.25rem; }

.chart-tabs {
  display: flex; gap: 3px; background: var(--cream-d);
  border-radius: 9px; padding: 3px;
}
.ctab {
  font-size: .68rem; font-weight: 700; padding: 4px 10px; border-radius: 7px;
  border: none; cursor: pointer; background: transparent; color: var(--muted);
  transition: all .18s; letter-spacing: .05em; text-transform: uppercase;
  font-family: 'Outfit', sans-serif;
}
.ctab.active { background: var(--white); color: var(--g-d); box-shadow: 0 1px 4px rgba(14,36,20,.12); }

.donut-wrap { position: relative; display: flex; align-items: center; justify-content: center; }
.donut-center { position: absolute; text-align: center; pointer-events: none; }
.donut-center .dc-val {
  font-family: 'Cinzel', serif; font-size: 1.6rem;
  font-weight: 700; color: var(--ink); line-height: 1;
}
.donut-center .dc-lbl { font-size: .6rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }

.donut-legend { display: flex; flex-direction: column; gap: .5rem; margin-top: 1rem; }
.dl-item { display: flex; align-items: center; gap: .6rem; font-size: .78rem; }
.dl-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.dl-label { color: var(--muted); flex: 1; }
.dl-val { font-weight: 700; color: var(--ink); }

.spark-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: .75rem; }
.spark-mini {
  background: var(--cream); border-radius: 10px;
  padding: .65rem .9rem; border: 1.5px solid var(--border);
}
.sm-label { font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--muted); margin-bottom: .2rem; }
.sm-val { font-family: 'Cinzel', serif; font-size: 1.15rem; font-weight: 700; color: var(--ink); }

/* ══ PSB LEVEL STRIP (from business plan) ══ */
.psb-strip {
  display: grid; grid-template-columns: repeat(7,1fr);
  border: 1.5px solid var(--border); border-radius: 14px;
  overflow: hidden; background: var(--cream-d); gap: 1px;
  animation: fadeUp .5s .35s ease both;
}
@media(max-width:700px){ .psb-strip { grid-template-columns: repeat(4,1fr); } }

.psb-cell {
  background: var(--white); padding: .9rem .75rem; text-align: center;
  transition: background .18s; position: relative;
}
.psb-cell:hover { background: var(--gold-p); }
.psb-cell::after {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px;
}
.psb-cell:nth-child(1)::after { background: var(--g-ll); }
.psb-cell:nth-child(2)::after { background: var(--g-l); }
.psb-cell:nth-child(3)::after { background: var(--g); }
.psb-cell:nth-child(4)::after { background: var(--g-d); }
.psb-cell:nth-child(5)::after { background: var(--gold); }
.psb-cell:nth-child(6)::after { background: var(--gold-d); }
.psb-cell:nth-child(7)::after { background: var(--g-dd); }

.psb-lvl { font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--muted); margin-bottom: .35rem; }
.psb-pct {
  font-family: 'Cinzel', serif; font-size: 1.4rem;
  font-weight: 700; color: var(--g-d); line-height: 1;
}
.psb-pct span { font-size: .65rem; font-weight: 600; color: var(--muted); }
.psb-txn { font-size: .65rem; color: var(--muted); margin-top: .25rem; }
.psb-earned { font-size: .72rem; font-weight: 700; color: var(--gold-d); margin-top: .2rem; }

/* ══ LEADERSHIP CLUBS (from business plan) ══ */
.clubs-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; }
@media(max-width:900px){ .clubs-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px){ .clubs-grid { grid-template-columns: 1fr 1fr; } }

.club-card {
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 14px; padding: 1.1rem 1rem; text-align: center;
  transition: all .22s; animation: fadeUp .5s .4s ease both;
  position: relative; overflow: hidden;
}
.club-card::before {
  content: ''; position: absolute; inset: 0;
  background: var(--club-bg, transparent); opacity: .04;
  pointer-events: none;
}
.club-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(14,36,20,.1); }
.club-icon { font-size: 1.8rem; margin-bottom: .4rem; }
.club-abbr {
  font-family: 'Cinzel', serif; font-size: .75rem; font-weight: 700;
  letter-spacing: .12em; color: var(--club-color, var(--g-d));
  background: var(--club-bg-light, rgba(28,61,36,.07));
  padding: 2px 10px; border-radius: 20px; display: inline-block; margin-bottom: .35rem;
}
.club-name { font-size: .72rem; color: var(--muted); font-weight: 500; margin-bottom: .5rem; }
.club-bv {
  font-family: 'Cinzel', serif; font-size: 1rem;
  font-weight: 700; color: var(--ink); line-height: 1;
}
.club-bv-label { font-size: .6rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-top: .15rem; }

/* ══ TWO COLUMN ══ */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
@media(max-width:860px){ .two-col { grid-template-columns: 1fr; } }

/* ══ TABLE CARDS ══ */
.tcard {
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 16px; overflow: hidden;
  box-shadow: 0 2px 12px rgba(14,36,20,.05);
  animation: fadeUp .5s .4s ease both;
}
.tcard-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: .95rem 1.25rem .8rem; border-bottom: 1px solid var(--cream-d);
  background: linear-gradient(to right, rgba(14,36,20,.03), transparent);
}
.tcard-head h3 { font-family: 'Cinzel', serif; font-size: .95rem; font-weight: 700; color: var(--g-d); letter-spacing: .03em; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; font-family: 'Outfit', sans-serif; }

/* TABLE HEADER — Forest Green brand */
thead tr {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 50%, var(--g) 100%) !important;
  border-bottom: 2px solid var(--gold) !important;
}
thead th {
  color: rgba(255,255,255,.85) !important;
  -webkit-text-fill-color: rgba(255,255,255,.85) !important;
  padding: .65rem 1rem; text-align: left;
  font-size: .65rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; white-space: nowrap;
  background: transparent !important; opacity: 1 !important;
}
tbody tr { border-bottom: 1px solid var(--cream-d); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--cream); }
tbody td { padding: .75rem 1rem; vertical-align: middle; }

.badge {
  display: inline-block; font-size: .63rem; font-weight: 700;
  letter-spacing: .07em; padding: 2px 9px; border-radius: 20px; text-transform: uppercase;
}
.badge-green  { background: rgba(28,61,36,.1);   color: var(--g-d); }
.badge-red    { background: #fdeaea; color: var(--danger); }
.badge-gold   { background: rgba(200,146,42,.12); color: var(--gold-d); }
.badge-gray   { background: #f0f0f0; color: #666; }

.btn { display: inline-flex; align-items: center; gap: .3rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .76rem; transition: all .18s; text-decoration: none; font-family: 'Outfit', sans-serif; }
.btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--muted); padding: .32rem .85rem; }
.btn-outline:hover { border-color: var(--g-d); color: var(--g-d); background: var(--cream); }
.btn-sm { font-size: .73rem; }

code.chip {
  background: var(--cream-d); color: var(--g-d);
  padding: 2px 8px; border-radius: 5px; font-size: .72rem;
  border: 1px solid var(--border);
}

/* ══ TOP EARNERS ══ */
.medal { width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; }
.medal-1 { background: linear-gradient(135deg,#ffd700,#e6a800); color: #5a3d00; }
.medal-2 { background: linear-gradient(135deg,#e0e0e0,#bdbdbd); color: #444; }
.medal-3 { background: linear-gradient(135deg,#cd8c52,#a0612a); color: #fff; }
.medal-n { background: var(--cream-d); color: var(--muted); }
.earner-row-1 td { background: linear-gradient(to right, var(--gold-p), var(--white)) !important; }

/* ══ POOL INFO BOX ══ */
.pool-box {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 60%, var(--g) 100%);
  border-radius: 14px; padding: 1.1rem 1.4rem;
  display: flex; align-items: center; gap: 1.2rem;
  margin-bottom: 1rem; position: relative; overflow: hidden;
}
.pool-box::after {
  content: '15%'; position: absolute; right: 1.5rem;
  font-family: 'Cinzel', serif; font-size: 4rem; font-weight: 900;
  color: rgba(255,255,255,.06); letter-spacing: .05em; pointer-events: none;
}
.pool-icon {
  width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
  background: rgba(200,146,42,.2); border: 1px solid rgba(200,146,42,.3);
  display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
}
.pool-text-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.5); margin-bottom: .25rem; }
.pool-text-val { font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 700; color: var(--gold-l); }
.pool-text-desc { font-size: .72rem; color: rgba(255,255,255,.45); margin-top: .2rem; }

@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
</style>

<div class="dash-wrap">

<!-- ══ WELCOME BANNER ══ -->
<div class="welcome-bar">
  <div class="wb-left">
    <div class="wb-brand">★ Mfills Admin Panel</div>
    <h1>Admin Dashboard</h1>
    <p>Network overview · BV tracking · Partner Sales Bonus · Club Income</p>
  </div>
  <div class="wb-right">
    <div class="wb-date"><?= date('l, d M Y') ?></div>
    <div class="wb-rev"><?= inr($total_revenue) ?></div>
    <div class="wb-revlabel">total network revenue</div>
  </div>
</div>

<?php if ($pending_with > 0): ?>
<div class="alert-pending">
  <div class="ap-icon">🚨</div>
  <div>
    <strong><?= $pending_with ?> Withdrawal<?= $pending_with>1?'s':'' ?> Pending Approval</strong>
    <p>Partners are waiting for payout. <a href="withdrawals.php">Review now →</a></p>
  </div>
</div>
<?php endif; ?>

<?php if ($is_saturday && $psb_pending_cnt > 0): ?>
<!-- ══ SATURDAY PSB TRANSFER REMINDER BANNER ══ -->
<div class="psb-saturday-banner" id="psbReminderBanner">
  <div class="psb-banner-left">
    <div class="psb-banner-icon">📅</div>
    <div>
      <div class="psb-banner-title">Saturday PSB Transfer Reminder</div>
      <div class="psb-banner-sub">
        <strong><?= number_format($psb_pending_cnt) ?> pending transactions</strong> worth
        <strong>₹<?= number_format($psb_pending_amt, 2) ?></strong> are awaiting transfer to partner wallets.
      </div>
    </div>
  </div>
  <div class="psb-banner-actions">
    <a href="commissions.php" class="psb-btn-primary">💸 Process PSB Now</a>
    <a href="wallets.php"     class="psb-btn-outline">👛 View Wallets</a>
    <button onclick="dismissPsbBanner()" class="psb-btn-dismiss" title="Dismiss">✕</button>
  </div>
</div>
<style>
.psb-saturday-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  background: linear-gradient(135deg, #fffbf0, #fef3d0);
  border: 2px solid #e0b050;
  border-left: 6px solid #C8922A;
  border-radius: 14px;
  padding: 1.1rem 1.4rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 18px rgba(200,146,42,.15);
  animation: psbSlideIn .4s ease;
}
@keyframes psbSlideIn {
  from { opacity:0; transform:translateY(-10px); }
  to   { opacity:1; transform:translateY(0); }
}
.psb-banner-left  { display:flex; align-items:center; gap:1rem; }
.psb-banner-icon  { font-size:2rem; flex-shrink:0; }
.psb-banner-title {
  font-family:'Cinzel',serif;
  font-size:.95rem; font-weight:700;
  color:#7a5010; margin-bottom:.25rem;
}
.psb-banner-sub   { font-size:.82rem; color:#a07830; line-height:1.45; }
.psb-banner-sub strong { color:#7a5010; }
.psb-banner-actions { display:flex; align-items:center; gap:.6rem; flex-shrink:0; flex-wrap:wrap; }
.psb-btn-primary {
  background: linear-gradient(135deg,#C8922A,#a87520);
  color:#fff; text-decoration:none;
  padding:.55rem 1.1rem; border-radius:8px;
  font-size:.82rem; font-weight:700;
  white-space:nowrap;
  box-shadow:0 3px 10px rgba(200,146,42,.3);
  transition:.2s;
}
.psb-btn-primary:hover { background:linear-gradient(135deg,#e0b050,#C8922A); transform:translateY(-1px); }
.psb-btn-outline {
  background:transparent;
  border:2px solid #C8922A;
  color:#a07830; text-decoration:none;
  padding:.5rem 1rem; border-radius:8px;
  font-size:.82rem; font-weight:600;
  white-space:nowrap; transition:.2s;
}
.psb-btn-outline:hover { background:#fef3d0; }
.psb-btn-dismiss {
  background:none; border:none; cursor:pointer;
  color:#c0a060; font-size:1.1rem; padding:.3rem .5rem;
  border-radius:6px; transition:.2s; line-height:1;
}
.psb-btn-dismiss:hover { background:#fde8b0; color:#7a5010; }
@media(max-width:700px){
  .psb-saturday-banner { flex-direction:column; align-items:flex-start; }
  .psb-banner-actions  { width:100%; }
}
</style>
<script>
function dismissPsbBanner() {
  var b = document.getElementById('psbReminderBanner');
  if (b) { b.style.transition='opacity .3s'; b.style.opacity='0'; setTimeout(function(){ b.remove(); }, 300); }
  // Remember dismiss for this session
  try { sessionStorage.setItem('psb_banner_dismissed_<?= date('Y-m-d') ?>', '1'); } catch(e){}
}
// Auto-dismiss if already dismissed this session
(function(){
  try {
    if (sessionStorage.getItem('psb_banner_dismissed_<?= date('Y-m-d') ?>') === '1') {
      var b = document.getElementById('psbReminderBanner');
      if (b) b.remove();
    }
  } catch(e){}
})();
</script>
<?php endif; ?>

<!-- ══ OVERVIEW STATS ══ -->
<div class="section-label">
  <h2>Network Overview</h2>
  <span class="sl-sub">Live · All time</span>
  <div class="sl-line"></div>
</div>
<div class="stats-grid">

  <div class="stat-card" style="--accent:#e8f2ea">
    <div class="sc-top">
      <div class="sc-icon" style="--icon-bg:#e8f2ea">👥</div>
      <span class="sc-trend">+<?= $new_today ?> today</span>
    </div>
    <div class="sc-label">Total MBPs</div>
    <div class="sc-value"><?= number_format($total_users) ?></div>
    <div class="sc-sub"><?= number_format($active_users) ?> active partners</div>
  </div>

  <div class="stat-card" style="--accent:#FFF8E7">
    <div class="sc-top">
      <div class="sc-icon" style="--icon-bg:#FFF8E7">🛍️</div>
      <span class="sc-trend">+<?= $orders_today ?> today</span>
    </div>
    <div class="sc-label">MShop Orders</div>
    <div class="sc-value"><?= number_format($total_orders) ?></div>
    <div class="sc-sub">BV-generating purchases</div>
  </div>

  <div class="stat-card" style="--accent:#e8f2ea">
    <div class="sc-top">
      <div class="sc-icon" style="--icon-bg:#e8f2ea">💵</div>
      <span class="sc-trend">+<?= inr($rev_today) ?></span>
    </div>
    <div class="sc-label">Total Revenue</div>
    <div class="sc-value" style="font-size:1.6rem"><?= inr($total_revenue) ?></div>
    <div class="sc-sub">Completed orders only</div>
  </div>

  <div class="stat-card" style="--accent:#FFF8E7">
    <div class="sc-top">
      <div class="sc-icon" style="--icon-bg:#FFF8E7">💸</div>
    </div>
    <div class="sc-label">PSB Distributed</div>
    <div class="sc-value" style="font-size:1.6rem"><?= inr($total_comm) ?></div>
    <div class="sc-sub">Across all 7 PSB levels</div>
  </div>

  <div class="stat-card" style="--accent:<?= $pending_with>0?'#fdeaea':'#e8f2ea' ?>">
    <div class="sc-top">
      <div class="sc-icon" style="--icon-bg:<?= $pending_with>0?'#fdeaea':'#e8f2ea' ?>">🏦</div>
      <?php if ($pending_with > 0): ?>
        <span class="sc-trend warn"><?= $pending_with ?> pending</span>
      <?php endif; ?>
    </div>
    <div class="sc-label">Withdrawals</div>
    <div class="sc-value" style="color:<?= $pending_with>0?'var(--danger)':'var(--g-d)' ?>"><?= $pending_with ?></div>
    <div class="sc-sub"><?= $pending_with>0 ? '<a href="withdrawals.php">Review now →</a>' : 'All clear ✓' ?></div>
  </div>

  <div class="stat-card" style="--accent:#e8f2ea">
    <div class="sc-top">
      <div class="sc-icon" style="--icon-bg:#e8f2ea">📦</div>
    </div>
    <div class="sc-label">Active Products</div>
    <div class="sc-value"><?= $total_products ?></div>
    <div class="sc-sub"><a href="products.php">Manage MShop →</a></div>
  </div>

</div>

<!-- ══ ANALYTICS ══ -->
<div class="section-label">
  <h2>Analytics</h2>
  <span class="sl-sub">Last 7 days</span>
  <div class="sl-line"></div>
</div>
<div class="charts-row">

  <div class="chart-card">
    <div class="chart-head">
      <h3>Business Performance</h3>
      <div class="chart-tabs">
        <button class="ctab active" onclick="switchChart('revenue',this)">Revenue</button>
        <button class="ctab" onclick="switchChart('orders',this)">Orders</button>
        <button class="ctab" onclick="switchChart('members',this)">New MBPs</button>
      </div>
    </div>
    <div class="chart-body">
      <div class="spark-row">
        <div class="spark-mini">
          <div class="sm-label">7-Day Revenue</div>
          <div class="sm-val"><?= inr(array_sum($chart_rev)) ?></div>
        </div>
        <div class="spark-mini">
          <div class="sm-label">7-Day Orders</div>
          <div class="sm-val"><?= array_sum($chart_orders) ?></div>
        </div>
      </div>
      <canvas id="mainChart" height="200"></canvas>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-head">
      <h3>Order Status</h3>
    </div>
    <div class="chart-body">
      <div class="donut-wrap">
        <canvas id="donutChart" height="180" width="180"></canvas>
        <div class="donut-center">
          <div class="dc-val"><?= $total_orders ?></div>
          <div class="dc-lbl">Total</div>
        </div>
      </div>
      <div class="donut-legend">
        <div class="dl-item">
          <div class="dl-dot" style="background:var(--g-l)"></div>
          <span class="dl-label">Completed</span>
          <span class="dl-val"><?= $status_map['completed'] ?? 0 ?></span>
        </div>
        <div class="dl-item">
          <div class="dl-dot" style="background:var(--gold)"></div>
          <span class="dl-label">Pending</span>
          <span class="dl-val"><?= $status_map['pending'] ?? 0 ?></span>
        </div>
        <div class="dl-item">
          <div class="dl-dot" style="background:var(--danger)"></div>
          <span class="dl-label">Cancelled</span>
          <span class="dl-val"><?= $status_map['cancelled'] ?? 0 ?></span>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ══ PSB — PARTNER SALES BONUS (7 Levels per business plan) ══ -->
<div class="section-label">
  <h2>Partner Sales Bonus (PSB)</h2>
  <span class="sl-sub">7-level distribution · up to 40% of BV</span>
  <div class="sl-line"></div>
</div>
<div class="psb-strip">
  <?php
  $psb_pcts = [1=>15, 2=>8, 3=>6, 4=>4, 5=>3, 6=>2, 7=>2];
  foreach ($psb_pcts as $lvl => $pct):
    $stat = null;
    foreach ($lvl_stats as $ls) {
      if ((int)$ls['level'] === $lvl) { $stat = $ls; break; }
    }
  ?>
  <div class="psb-cell">
    <div class="psb-lvl">Level <?= $lvl ?></div>
    <div class="psb-pct"><?= $pct ?><span>%</span></div>
    <?php if ($stat): ?>
      <div class="psb-txn"><?= number_format($stat['cnt']) ?> txns</div>
      <div class="psb-earned"><?= inr($stat['total']) ?></div>
    <?php else: ?>
      <div class="psb-txn" style="color:var(--border)">—</div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ CLUB INCOME — 4 Leadership Ranks ══ -->
<div class="section-label">
  <h2>Mfills Club Income</h2>
  <span class="sl-sub">Leadership reward pool · 15% of total sales</span>
  <div class="sl-line"></div>
</div>

<div class="pool-box">
  <div class="pool-icon">💰</div>
  <div>
    <div class="pool-text-label">Leadership Reward Pool</div>
    <div class="pool-text-val">15% of Total Company Sales → <?= inr($total_revenue * 0.15) ?></div>
    <div class="pool-text-desc">Distributed among RSC · PC · GAC · CC qualified partners</div>
  </div>
</div>

<div class="clubs-grid">
  <div class="club-card" style="--club-color:#5db870; --club-bg:var(--g-ll); --club-bg-light:rgba(93,184,112,.1)">
    <div class="club-icon">⭐</div>
    <div class="club-abbr">RSC</div>
    <div class="club-name">Rising Star Club</div>
    <div class="club-bv">50,000</div>
    <div class="club-bv-label">monthly team BV</div>
  </div>
  <div class="club-card" style="--club-color:var(--gold-d); --club-bg:var(--gold); --club-bg-light:rgba(200,146,42,.1)">
    <div class="club-icon">🏆</div>
    <div class="club-abbr">PC</div>
    <div class="club-name">Prestige Club</div>
    <div class="club-bv">2,00,000</div>
    <div class="club-bv-label">monthly team BV</div>
  </div>
  <div class="club-card" style="--club-color:var(--g-d); --club-bg:var(--g); --club-bg-light:rgba(42,92,52,.1)">
    <div class="club-icon">🌍</div>
    <div class="club-abbr">GAC</div>
    <div class="club-name">Global Ambassador</div>
    <div class="club-bv">10,00,000</div>
    <div class="club-bv-label">monthly team BV</div>
  </div>
  <div class="club-card" style="--club-color:var(--g-dd); --club-bg:var(--g-dd); --club-bg-light:rgba(14,36,20,.08)">
    <div class="club-icon">👑</div>
    <div class="club-abbr">CC</div>
    <div class="club-name">Chairman Club</div>
    <div class="club-bv">50,00,000</div>
    <div class="club-bv-label">monthly team BV</div>
  </div>
</div>

<!-- ══ RECENT ACTIVITY ══ -->
<div class="section-label">
  <h2>Recent Activity</h2>
  <div class="sl-line"></div>
</div>
<div class="two-col">

  <div class="tcard">
    <div class="tcard-head">
      <h3>Recent MBPs</h3>
      <a href="users.php" class="btn btn-outline btn-sm">View All →</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Partner</th><th>MBPIN / Ref</th><th>Wallet</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recent_users as $u): ?>
        <tr>
          <td>
            <div style="font-weight:600;color:var(--ink)"><?= e($u['username']) ?></div>
            <div style="font-size:.7rem;color:var(--muted)"><?= e($u['email']) ?></div>
          </td>
          <td><code class="chip"><?= e($u['referral_code']) ?></code></td>
          <td style="font-weight:700;color:var(--gold-d)"><?= inr($u['wallet']) ?></td>
          <td><span class="badge badge-<?= $u['is_active']?'green':'red' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="tcard">
    <div class="tcard-head">
      <h3>Recent MShop Orders</h3>
      <a href="orders.php" class="btn btn-outline btn-sm">View All →</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>MBP</th><th>Product</th><th>Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recent_orders as $o):
          $sc = ['completed'=>'badge-green','pending'=>'badge-gold','cancelled'=>'badge-red'];
        ?>
        <tr>
          <td style="color:var(--muted);font-size:.75rem"><?= $o['id'] ?></td>
          <td style="font-weight:500"><?= e($o['username']??'–') ?></td>
          <td style="font-size:.76rem;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted)"><?= e($o['product_name']??'–') ?></td>
          <td style="font-weight:700;color:var(--gold-d)"><?= inr($o['amount']) ?></td>
          <td><span class="badge <?= $sc[$o['status']]??'badge-gray' ?>"><?= ucfirst($o['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ══ TOP EARNERS ══ -->
<?php if ($top_earners): ?>
<div class="section-label">
  <h2>🏆 Top PSB Earners</h2>
  <span class="sl-sub">All-time commission leaders</span>
  <div class="sl-line"></div>
</div>
<div class="tcard">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Rank</th><th>Partner</th><th>Transactions</th><th>PSB Earned</th></tr>
      </thead>
      <tbody>
      <?php foreach ($top_earners as $i => $t): ?>
      <tr class="<?= $i===0?'earner-row-1':'' ?>">
        <td>
          <?php if ($i===0):     ?><span class="medal medal-1">🥇</span>
          <?php elseif ($i===1): ?><span class="medal medal-2">🥈</span>
          <?php elseif ($i===2): ?><span class="medal medal-3">🥉</span>
          <?php else:            ?><span class="medal medal-n"><?= $i+1 ?></span>
          <?php endif; ?>
        </td>
        <td>
          <div style="font-weight:600;color:var(--ink)"><?= e($t['username']) ?></div>
          <div style="font-size:.7rem;color:var(--muted)"><?= e($t['email']) ?></div>
        </td>
        <td style="color:var(--muted);font-size:.82rem"><?= number_format($t['txns']) ?></td>
        <td>
          <span style="font-family:'Cinzel',serif;font-size:1.1rem;font-weight:700;color:var(--g-d)">
            <?= inr($t['earned']) ?>
          </span>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

</div><!-- /.dash-wrap -->

<script>
const labels  = <?= json_encode($chart_labels) ?>;
const revData = <?= json_encode($chart_rev) ?>;
const ordData = <?= json_encode($chart_orders) ?>;
const usrData = <?= json_encode($chart_users) ?>;
const donutData = {
  completed: <?= $status_map['completed'] ?? 0 ?>,
  pending:   <?= $status_map['pending']   ?? 0 ?>,
  cancelled: <?= $status_map['cancelled'] ?? 0 ?>
};

Chart.defaults.font.family = "'Outfit', sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#6b8a72';

const mainCtx = document.getElementById('mainChart').getContext('2d');

function makeGrad(ctx, c1, c2) {
  const g = ctx.createLinearGradient(0, 0, 0, 220);
  g.addColorStop(0, c1); g.addColorStop(1, c2); return g;
}

const datasets = {
  revenue: {
    label: 'Revenue (₹)', data: revData,
    borderColor: '#2a5c34',
    backgroundColor: makeGrad(mainCtx,'rgba(42,92,52,.18)','rgba(42,92,52,.01)'),
    fill: true, tension: .42, pointRadius: 4,
    pointBackgroundColor: '#2a5c34', pointBorderColor: '#fff', pointBorderWidth: 2,
  },
  orders: {
    label: 'Orders', data: ordData,
    borderColor: '#C8922A',
    backgroundColor: makeGrad(mainCtx,'rgba(200,146,42,.2)','rgba(200,146,42,.01)'),
    fill: true, tension: .42, pointRadius: 4,
    pointBackgroundColor: '#C8922A', pointBorderColor: '#fff', pointBorderWidth: 2,
  },
  members: {
    label: 'New MBPs', data: usrData,
    borderColor: '#5db870',
    backgroundColor: makeGrad(mainCtx,'rgba(93,184,112,.18)','rgba(93,184,112,.01)'),
    fill: true, tension: .42, pointRadius: 4,
    pointBackgroundColor: '#5db870', pointBorderColor: '#fff', pointBorderWidth: 2,
  }
};

const mainChart = new Chart(mainCtx, {
  type: 'line',
  data: { labels, datasets: [datasets.revenue] },
  options: {
    responsive: true, maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#fff', titleColor: '#1C3D24',
        bodyColor: '#0e2414', borderColor: '#DDD5C4', borderWidth: 1, padding: 10,
        callbacks: {
          label: ctx => {
            const v = ctx.parsed.y;
            return ctx.dataset.label.includes('₹') ? '₹' + v.toLocaleString('en-IN') : v;
          }
        }
      }
    },
    scales: {
      x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 } } },
      y: { grid: { color: '#F3EDE0', drawBorder: false }, border: { display: false, dash: [4,4] }, ticks: { font: { size: 10 } } }
    }
  }
});

let currentTab = 'revenue';
function switchChart(type, btn) {
  if (currentTab === type) return;
  currentTab = type;
  document.querySelectorAll('.ctab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  mainChart.data.datasets = [datasets[type]];
  mainChart.update('active');
}

// Donut — Mfills brand colors
const donutCtx = document.getElementById('donutChart').getContext('2d');
new Chart(donutCtx, {
  type: 'doughnut',
  data: {
    labels: ['Completed', 'Pending', 'Cancelled'],
    datasets: [{
      data: [donutData.completed, donutData.pending, donutData.cancelled],
      backgroundColor: ['#3a8a4a', '#C8922A', '#b91c1c'],
      borderColor: '#fff', borderWidth: 3, hoverOffset: 6
    }]
  },
  options: {
    responsive: false, cutout: '68%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#fff', titleColor: '#0e2414',
        bodyColor: '#6b8a72', borderColor: '#DDD5C4', borderWidth: 1, padding: 8
      }
    }
  }
});
</script>

<?php require_once '_footer.php'; ?>