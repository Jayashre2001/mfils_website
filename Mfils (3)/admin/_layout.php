<?php
// admin/_layout.php
if (!function_exists('requireAdmin')) {
    require_once __DIR__ . '/config.php';
}
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌿</text></svg>">
<title><?= e($pageTitle ?? 'Dashboard') ?> · <?= APP_NAME ?> Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    /* ── Mfills Brand — Forest Green + Gold ── */
    --maroon:    #1C3D24;
    --maroon-d:  #0e2414;
    --maroon-l:  #2a5c34;
    --gold:      #C8922A;
    --gold-d:    #a87520;
    --gold-l:    #e0b050;
    --cream:     #FDFAF4;
    --cream-d:   #F3EDE0;
    --text:      #0e2414;
    --muted:     #5a7a62;
    --border:    #DDD5C4;
    --success:   #0F7B5C;
    --danger:    #C0392B;
    --warning:   #C8922A;
    --info:      #1E5F8A;
    --sidebar-w: 240px;
    --header-h:  60px;
    --shadow:    0 2px 12px rgba(14,36,20,.10);
    --radius:    10px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    font-size: 14px;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w);
    background: linear-gradient(180deg, #0e2414 0%, #1C3D24 60%, #0e2414 100%);
    min-height: 100vh;
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    z-index: 200;
    box-shadow: 3px 0 24px rgba(14,36,20,.3);
    transition: transform .25s ease;
  }
  .sidebar-brand {
    padding: 1rem 1.25rem 1.1rem;
    border-bottom: 1px solid rgba(200,146,42,.25);
    background: rgba(0,0,0,.18);
    display: flex; flex-direction: column; align-items: flex-start; gap: .25rem;
  }
  .brand-logo-wrap { display: block; line-height: 0; }
  .brand-logo {
    height: auto;
    max-height: 54px;
    width: auto;
    max-width: 155px;
    object-fit: contain;
    object-position: left center;
    display: block;
    opacity: .95;
    transition: opacity .2s;
  }
  .brand-logo:hover { opacity: 1; }
  .brand-sub { color: rgba(200,146,42,.6); font-size: .58rem; text-transform: uppercase; letter-spacing: .14em; font-weight: 600; margin-top: .15rem; padding-left: .05rem; }
  .sidebar-nav { flex: 1; padding: .75rem 0; overflow-y: auto; }
  .nav-section {
    padding: .6rem 1rem .3rem;
    font-size: .58rem; text-transform: uppercase;
    letter-spacing: .14em; color: rgba(200,146,42,.5); font-weight: 700;
    display: flex; align-items: center; gap: .5rem;
  }
  .nav-section::after {
    content: ''; flex: 1; height: 1px;
    background: rgba(200,146,42,.15);
  }
  .nav-link {
    display: flex; align-items: center; gap: .75rem;
    padding: .58rem 1.25rem;
    color: rgba(255,255,255,.65);
    text-decoration: none; font-size: .845rem; font-weight: 500;
    transition: all .18s; border-left: 3px solid transparent;
    position: relative;
  }
  .nav-link:hover  {
    background: rgba(200,146,42,.1);
    color: rgba(255,255,255,.95);
    border-left-color: var(--gold);
    padding-left: 1.45rem;
  }
  .nav-link.active {
    background: rgba(200,146,42,.15);
    color: var(--gold-l);
    border-left-color: var(--gold);
    font-weight: 700;
  }
  .nav-link.active::before {
    content: '';
    position: absolute; right: 0; top: 50%; transform: translateY(-50%);
    width: 3px; height: 60%; background: var(--gold);
    border-radius: 2px 0 0 2px; opacity: .5;
  }
  .nav-icon { font-size: .95rem; width: 20px; text-align: center; opacity: .85; }
  .nav-badge {
    margin-left: auto; background: var(--danger); color: #fff;
    font-size: .65rem; font-weight: 700; padding: 1px 6px;
    border-radius: 20px; min-width: 18px; text-align: center;
  }
  .sidebar-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid rgba(200,146,42,.2);
    background: rgba(0,0,0,.15);
    display: flex; align-items: center; gap: .75rem;
  }
  .admin-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--gold-d), var(--gold-l));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; font-weight: 800; color: var(--maroon-d); flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(200,146,42,.35);
  }
  .admin-info { flex: 1; min-width: 0; }
  .admin-name { color: #fff; font-size: .8rem; font-weight: 600; }
  .admin-role { color: rgba(255,255,255,.4); font-size: .68rem; }
  .logout-btn { color: rgba(255,255,255,.4); font-size: 1.2rem; text-decoration: none; transition: color .15s; padding: .25rem; }
  .logout-btn:hover { color: var(--danger); }

  /* ── Overlay (mobile) ── */
  .sidebar-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 199; backdrop-filter: blur(2px);
  }
  .sidebar-overlay.show { display: block; }

  /* ── Main ── */
  .main-wrap {
    margin-left: var(--sidebar-w);
    flex: 1; display: flex; flex-direction: column;
    min-height: 100vh; min-width: 0;
  }

  /* ── Topbar ── */
  .topbar {
    height: var(--header-h); background: #fff;
    border-bottom: 2px solid var(--border);
    display: flex; align-items: center;
    padding: 0 1.5rem; gap: 1rem;
    position: sticky; top: 0; z-index: 50;
    box-shadow: 0 2px 12px rgba(14,36,20,.07);
  }
  .hamburger {
    display: none; background: none; border: none;
    cursor: pointer; padding: .3rem;
    color: var(--maroon); font-size: 1.4rem; line-height: 1;
  }
  .topbar-title {
    font-family: 'Cinzel', 'Playfair Display', serif;
    font-size: 1.1rem; color: var(--maroon); flex: 1; font-weight: 700;
  }
  .topbar-right { display: flex; align-items: center; gap: .75rem; }
  .live-time {
    background: var(--cream-d); padding: .3rem .85rem;
    border-radius: 20px; font-size: .7rem; color: var(--muted);
    font-weight: 600; white-space: nowrap;
    border: 1px solid var(--border);
  }

  /* ── Page content ── */
  .page-content { padding: 1.5rem; flex: 1; }

  /* ── Flash ── */
  .flash {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem 1.1rem; border-radius: var(--radius);
    margin-bottom: 1.25rem; font-weight: 500; font-size: .875rem;
  }
  .flash-success { background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; }
  .flash-error   { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; }
  .flash-warning { background:#FFFBEB; color:#92400E; border:1px solid #FDE68A; }
  .flash-info    { background:#EFF6FF; color:#1E40AF; border:1px solid #BFDBFE; }

  /* ── Stat cards ── */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem; margin-bottom: 1.75rem;
  }
  .stat-card {
    background: #fff; border-radius: var(--radius);
    padding: 1.1rem 1.25rem;
    border-left: 4px solid var(--gold);
    border-top: 1px solid var(--border);
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    box-shadow: var(--shadow); transition: transform .2s, box-shadow .2s;
    position: relative; overflow: hidden;
  }
  .stat-card::after {
    content: ''; position: absolute; top: 0; right: 0;
    width: 60px; height: 60px;
    background: radial-gradient(circle at top right, rgba(200,146,42,.08), transparent 70%);
    pointer-events: none;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(14,36,20,.12); }
  .sc-icon  { font-size: 1.5rem; margin-bottom: .4rem; }
  .sc-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); font-weight: 700; margin-bottom: .2rem; }
  .sc-value { font-family: 'Cinzel', 'Playfair Display', serif; font-size: 1.6rem; color: var(--maroon); line-height: 1.1; margin-bottom: .25rem; }
  .sc-sub   { font-size: .75rem; color: var(--muted); }
  .sc-sub a { color: var(--maroon); font-weight: 600; text-decoration: none; }

  /* ── Table cards ── */
  .tcard { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; border: 1.5px solid var(--border); }
  .tcard-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: .9rem 1.25rem; border-bottom: 1.5px solid var(--border);
    background: linear-gradient(135deg, var(--cream) 0%, #edf5ee 100%);
  }
  .tcard-head h3 { font-family: 'Cinzel', 'Playfair Display', serif; font-size: .95rem; color: var(--maroon); font-weight: 700; }
  .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  thead th {
    background: linear-gradient(135deg, #0e2414, #1C3D24);
    color: rgba(255,255,255,.8);
    -webkit-text-fill-color: rgba(255,255,255,.8);
    font-size: .65rem; text-transform: uppercase; letter-spacing: .09em;
    font-weight: 700; padding: .7rem 1rem; text-align: left;
    border-bottom: 2px solid var(--gold); white-space: nowrap;
  }
  tbody td { padding: .65rem 1rem; border-bottom: 1px solid var(--cream-d); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: var(--cream); }

  /* ── Badges ── */
  .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: .7rem; font-weight: 700; letter-spacing: .03em; }
  .badge-green  { background: rgba(15,123,92,.1);  color: #0a6644; }
  .badge-red    { background: rgba(192,57,43,.1);  color: #991B1B; }
  .badge-gold   { background: rgba(200,146,42,.12); color: var(--gold-d); }
  .badge-maroon { background: rgba(28,61,36,.1);   color: var(--maroon); }
  .badge-gray   { background: #F3F4F6; color: #374151; }
  .badge-blue   { background: #DBEAFE; color: #1E40AF; }

  /* ── Buttons ── */
  .btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.1rem; border-radius: 8px;
    font-family: 'DM Sans', sans-serif; font-size: .82rem; font-weight: 700;
    cursor: pointer; text-decoration: none; border: none; transition: all .2s; white-space: nowrap;
  }
  .btn-primary  { background: linear-gradient(135deg, var(--maroon-d), var(--maroon-l)); color: #fff; box-shadow: 0 2px 8px rgba(14,36,20,.22); }
  .btn-primary:hover  { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(14,36,20,.3); }
  .btn-gold     { background: linear-gradient(135deg, var(--gold-d), var(--gold-l)); color: #fff; box-shadow: 0 2px 8px rgba(200,146,42,.25); }
  .btn-gold:hover     { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(200,146,42,.35); }
  .btn-outline  { background: transparent; border: 1.5px solid var(--border); color: var(--muted); }
  .btn-outline:hover  { border-color: var(--maroon); color: var(--maroon); background: var(--cream); }
  .btn-danger   { background: var(--danger); color: #fff; }
  .btn-danger:hover   { background: #A93226; }
  .btn-success  { background: var(--success); color: #fff; }
  .btn-sm { padding: .35rem .8rem; font-size: .75rem; }
  .btn-lg { padding: .75rem 1.5rem; font-size: .95rem; }

  /* ── Forms ── */
  .form-group { margin-bottom: 1.1rem; }
  .form-group label { display: block; font-size: .78rem; font-weight: 600; color: var(--muted); margin-bottom: .35rem; text-transform: uppercase; letter-spacing: .05em; }
  .form-control {
    width: 100%; padding: .6rem .9rem;
    border: 1.5px solid var(--border); border-radius: 7px;
    font-family: 'DM Sans', sans-serif; font-size: .875rem;
    color: var(--text); background: #fff;
    transition: border-color .15s, box-shadow .15s; outline: none;
  }
  .form-control:focus { border-color: var(--maroon); box-shadow: 0 0 0 3px rgba(28,61,36,.1); }
  select.form-control { cursor: pointer; }
  textarea.form-control { resize: vertical; min-height: 90px; }

  /* ── Utility ── */
  .text-muted{color:var(--muted)} .text-success{color:var(--success)} .text-danger{color:var(--danger)} .text-center{text-align:center}
  .mt-1{margin-top:.5rem} .mt-2{margin-top:1rem} .mt-3{margin-top:1.5rem}
  .mb-1{margin-bottom:.5rem} .mb-2{margin-bottom:1rem} .mb-3{margin-bottom:1.5rem}

  /* ── Scrollbar ── */
  ::-webkit-scrollbar{width:5px;height:5px} ::-webkit-scrollbar-track{background:transparent} ::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px}

  /* ══ RESPONSIVE ══ */
  @media (max-width: 900px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .live-time { display: none; }
  }
  @media (max-width: 768px) {
    .sidebar      { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-wrap    { margin-left: 0; }
    .hamburger    { display: block; }
    .page-content { padding: 1rem; }
    .topbar       { padding: 0 1rem; }
    .stats-row    { grid-template-columns: 1fr 1fr; gap: .75rem; }
  }
  @media (max-width: 480px) {
    .stats-row    { grid-template-columns: 1fr; }
    .topbar-title { font-size: 1rem; }
  }
</style>
<script>
  function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
  }
</script>
</head>
<body>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <a href="index.php" class="brand-logo-wrap">
      <img src="<?= APP_URL ?>/includes/images/logo2.png"
           alt="Mfills"
           class="brand-logo"
           onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span style="display:none;font-family:'Cinzel',serif;color:var(--gold-l);font-size:1rem;font-weight:700;letter-spacing:.06em">MFILLS</span>
    </a>
    <div class="brand-sub">Admin Panel</div>
  </div>
  <nav class="sidebar-nav">
    <?php
    $cur = basename($_SERVER['PHP_SELF']);
    function navLink(string $href, string $icon, string $label, string $cur, ?int $badge = null): void {
        $active = (basename($href) === $cur) ? 'active' : '';
        echo '<a href="'.$href.'" class="nav-link '.$active.'">';
        echo '<span class="nav-icon">'.$icon.'</span>'.$label;
        if ($badge > 0) echo '<span class="nav-badge">'.$badge.'</span>';
        echo '</a>';
    }
    try {
        $pendingW = (int)db()->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();
    } catch (Exception $e) { $pendingW = 0; }
    ?>
    <div class="nav-section">Overview</div>
    <?php navLink('index.php',       '📊', 'Dashboard',     $cur) ?>
    <div class="nav-section">Management</div>
    <?php navLink('users.php',       '👥', 'Members',       $cur) ?>
    <?php navLink('orders.php',      '🛒', 'Orders',        $cur) ?>
    <?php navLink('products.php',    '📦', 'Products',      $cur) ?>
    <?php navLink('commissions.php', '💰', 'Commissions',   $cur) ?>
    <?php navLink('withdrawals.php', '🏦', 'Withdrawals',   $cur, $pendingW) ?>
    <div class="nav-section">Reports</div>
    <?php navLink('network.php',     '🌐', 'MLM Network',   $cur) ?>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-avatar">A</div>
    <div class="admin-info">
      <div class="admin-name"><?= ADMIN_USERNAME ?></div>
      <div class="admin-role">Administrator</div>
    </div>
    <a href="logout.php" class="logout-btn" title="Logout">⏻</a>
  </div>
</aside>

<!-- Main -->
<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger" onclick="openSidebar()">☰</button>
    <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
    <div class="topbar-right">
      <span class="live-time" id="liveTime"></span>
      <a href="<?= APP_URL ?>" target="_blank" class="btn btn-outline btn-sm">🌐 Site</a>
    </div>
  </header>
  <div class="page-content">
    <?php $flash = getAdminFlash();
    if ($flash):
      $cls = match($flash['type']) { 'success'=>'flash-success','error'=>'flash-error','warning'=>'flash-warning',default=>'flash-info' };
    ?><div class="flash <?= $cls ?>"><?= e($flash['msg']) ?></div><?php endif; ?>