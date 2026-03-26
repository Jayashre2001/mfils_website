<?php
// admin/users.php
require_once __DIR__ . '/config.php';
requireAdmin();
$pageTitle = 'Members';
$pdo = db();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);

    if ($action === 'toggle' && $uid) {
        $cur = $pdo->prepare("SELECT is_active FROM users WHERE id=?");
        $cur->execute([$uid]);
        $val = (int)$cur->fetchColumn();
        $pdo->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([1-$val, $uid]);
        setAdminFlash('success', 'Member status updated.');
    }
    if ($action === 'delete' && $uid) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        setAdminFlash('success', 'Member deleted.');
    }
    if ($action === 'add_wallet' && $uid) {
        $amt = (float)($_POST['amount'] ?? 0);
        if ($amt > 0) {
            $pdo->prepare("UPDATE users SET wallet=wallet+? WHERE id=?")->execute([$amt, $uid]);
            setAdminFlash('success', 'Wallet credited ' . inr($amt) . ' successfully.');
        }
    }
    header('Location: users.php'); exit;
}

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$lim  = 20;
$off  = ($page - 1) * $lim;

$where  = $q ? "WHERE u.username LIKE ? OR u.email LIKE ? OR u.referral_code LIKE ?" : '';
$params = $q ? ["%$q%", "%$q%", "%$q%"] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $lim));

$stmt = $pdo->prepare(
    "SELECT u.*,
        (SELECT COUNT(*) FROM users r WHERE r.referrer_id=u.id) as directs,
        (SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id=u.id) as earned,
        ref.username as referrer_name
     FROM users u
     LEFT JOIN users ref ON ref.id=u.referrer_id
     $where ORDER BY u.created_at DESC LIMIT $lim OFFSET $off"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Quick stats
$active_count   = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$inactive_count = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=0")->fetchColumn();
$today_count    = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();

require_once '_layout.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Outfit:wght@300;400;500;600;700;800&display=swap');

/* ══════════════════════════════════════════
   MFILLS ADMIN — MEMBERS PAGE
   Forest Green · Gold · Cream
══════════════════════════════════════════ */
:root {
  --g-dd:    #0e2414;
  --g-d:     #1C3D24;
  --g:       #2a5c34;
  --g-l:     #3a8a4a;
  --g-ll:    #5db870;
  --gold:    #C8922A;
  --gold-d:  #a87520;
  --gold-l:  #e0b050;
  --cream:   #FDFAF4;
  --cream-d: #F3EDE0;
  --border:  #DDD5C4;
  --muted:   #6b8a72;
  --ink:     #0e2414;
  --danger:  #C0392B;
  --success: #0F7B5C;
}

/* ── HERO HEADER ── */
.members-hero {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 55%, var(--g) 100%);
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  margin-bottom: 1.5rem;
  display: flex; align-items: center;
  justify-content: space-between;
  gap: 1rem; flex-wrap: wrap;
  position: relative; overflow: hidden;
  border-bottom: 3px solid var(--gold);
  box-shadow: 0 8px 32px rgba(14,36,20,.2);
}
.members-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.08) 1.5px, transparent 1.5px);
  background-size: 22px 22px; pointer-events: none;
}
.members-hero::after {
  content: '👥';
  position: absolute; right: 1.75rem; top: 50%;
  transform: translateY(-50%);
  font-size: 5rem; opacity: .06; pointer-events: none;
}
.hero-left { position: relative; z-index: 1; }
.members-hero h1 {
  font-family: 'Cinzel', serif;
  color: #fff; font-size: 1.35rem;
  margin-bottom: .25rem; font-weight: 700;
}
.members-hero p { color: rgba(255,255,255,.5); font-size: .8rem; }

/* ── STAT PILLS ── */
.stat-pills { display: flex; gap: .55rem; flex-wrap: wrap; position: relative; z-index: 1; }
.stat-pill {
  display: flex; align-items: center; gap: .4rem;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(200,146,42,.3);
  border-radius: 30px; padding: .38rem 1rem;
  font-size: .75rem; color: #fff; font-weight: 600;
  font-family: 'Outfit', sans-serif;
  backdrop-filter: blur(4px); white-space: nowrap;
  transition: background .2s;
}
.stat-pill:hover { background: rgba(200,146,42,.2); }
.stat-pill .pill-val { color: var(--gold-l); font-weight: 800; font-size: .8rem; }
.stat-pill .pill-lbl { opacity: .65; font-weight: 400; }

/* ── SEARCH BAR ── */
.filter-bar {
  background: #fff; border: 1.5px solid var(--border);
  border-radius: 12px; padding: .85rem 1.1rem;
  display: flex; align-items: center; gap: .75rem;
  margin-bottom: 1.25rem; flex-wrap: wrap;
  box-shadow: 0 2px 10px rgba(14,36,20,.05);
}
.search-wrap { flex: 1; min-width: 220px; position: relative; }
.search-wrap .s-icon {
  position: absolute; left: .9rem; top: 50%;
  transform: translateY(-50%); pointer-events: none;
}
.search-wrap .s-icon svg { width: 15px; height: 15px; color: var(--muted); }
.search-wrap input {
  width: 100%; padding: .62rem .9rem .62rem 2.4rem;
  border: 1.5px solid var(--border); border-radius: 9px;
  font-family: 'Outfit', sans-serif; font-size: .875rem;
  color: var(--ink); background: var(--cream); outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.search-wrap input:focus {
  border-color: var(--g-d); background: #fff;
  box-shadow: 0 0 0 3px rgba(28,61,36,.1);
}
.filter-count { font-size: .78rem; color: var(--muted); white-space: nowrap; font-family: 'Outfit', sans-serif; }

/* ── TABLE CARD ── */
.members-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 4px 24px rgba(14,36,20,.08);
  overflow: hidden; border: 1.5px solid var(--border);
}
.members-card table { width: 100%; border-collapse: collapse; font-family: 'Outfit', sans-serif; }

.members-card thead th {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 60%, var(--g) 100%);
  color: rgba(255,255,255,.8) !important;
  -webkit-text-fill-color: rgba(255,255,255,.8) !important;
  font-size: .65rem; text-transform: uppercase;
  letter-spacing: .1em; font-weight: 700;
  padding: .85rem 1rem; text-align: left;
  white-space: nowrap; border: none !important;
  border-bottom: 2px solid var(--gold) !important;
}
.members-card tbody tr {
  border-bottom: 1px solid var(--cream-d);
  transition: background .12s;
}
.members-card tbody tr:last-child { border-bottom: none; }
.members-card tbody tr:hover { background: var(--cream); }
.members-card tbody td { padding: .85rem 1rem; vertical-align: middle; font-size: .84rem; color: var(--ink); }

/* ── MEMBER AVATAR + INFO ── */
.member-cell { display: flex; align-items: center; gap: .75rem; }
.member-avatar {
  width: 38px; height: 38px; border-radius: 10px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; font-weight: 800; color: var(--gold-l);
  flex-shrink: 0; text-transform: uppercase;
  box-shadow: 0 2px 8px rgba(14,36,20,.2);
  font-family: 'Cinzel', serif;
}
.member-name  { font-weight: 700; font-size: .875rem; color: var(--g-d); }
.member-email { font-size: .7rem; color: var(--muted); margin-top: 2px; }
.member-mbpin { font-size: .65rem; color: var(--gold-d); font-weight: 600; margin-top: 1px; }

/* ── REFERRAL CODE CHIP ── */
.ref-chip {
  background: rgba(200,146,42,.1);
  color: var(--gold-d);
  padding: .22rem .75rem; border-radius: 20px;
  font-size: .72rem; font-weight: 800;
  letter-spacing: .06em;
  border: 1px solid rgba(200,146,42,.25);
  white-space: nowrap; font-family: 'Outfit', monospace;
}

/* ── STATUS BUTTON ── */
.status-btn {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .28rem .8rem; border-radius: 20px;
  font-size: .68rem; font-weight: 700; cursor: pointer;
  border: 1.5px solid transparent; transition: all .2s;
  font-family: 'Outfit', sans-serif; letter-spacing: .04em; text-transform: uppercase;
}
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.status-btn.active-btn {
  background: rgba(15,123,92,.1); color: var(--success);
  border-color: rgba(15,123,92,.25);
}
.status-btn.active-btn .status-dot { background: var(--g-ll); box-shadow: 0 0 0 2px rgba(93,184,112,.3); }
.status-btn.active-btn:hover { background: rgba(192,57,43,.08); color: var(--danger); border-color: rgba(192,57,43,.2); }
.status-btn.inactive-btn {
  background: rgba(192,57,43,.08); color: var(--danger);
  border-color: rgba(192,57,43,.2);
}
.status-btn.inactive-btn .status-dot { background: var(--danger); }
.status-btn.inactive-btn:hover { background: rgba(15,123,92,.1); color: var(--success); border-color: rgba(15,123,92,.25); }

/* ── ACTION BUTTONS ── */
.action-group { display: flex; gap: .4rem; align-items: center; justify-content: center; }
.act-btn {
  width: 32px; height: 32px; border-radius: 9px;
  border: 1.5px solid transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .22s cubic-bezier(.34,1.3,.64,1);
  background: transparent; flex-shrink: 0;
}
.act-btn svg { width: 14px; height: 14px; }
.act-btn-wallet {
  background: rgba(200,146,42,.1); border-color: rgba(200,146,42,.25); color: var(--gold-d);
}
.act-btn-wallet:hover {
  background: var(--gold); border-color: var(--gold); color: #fff;
  transform: scale(1.12) rotate(-5deg); box-shadow: 0 4px 12px rgba(200,146,42,.4);
}
.act-btn-delete {
  background: rgba(192,57,43,.08); border-color: rgba(192,57,43,.2); color: var(--danger);
}
.act-btn-delete:hover {
  background: var(--danger); border-color: var(--danger); color: #fff;
  transform: scale(1.12) rotate(5deg); box-shadow: 0 4px 12px rgba(192,57,43,.4);
}

/* ── STICKY ACTIONS COLUMN ── */
.members-card thead th:last-child,
.members-card tbody td:last-child {
  position: sticky; right: 0; z-index: 2;
  box-shadow: -3px 0 10px rgba(14,36,20,.06);
  min-width: 90px; background: #fff;
}
.members-card thead th:last-child {
  background: var(--g-dd) !important;
}
.members-card tbody tr:hover td:last-child { background: var(--cream); }

/* ── AMOUNTS ── */
.amt-earned { color: var(--success); font-weight: 700; font-size: .85rem; }
.amt-wallet { color: var(--gold-d); font-weight: 700; font-size: .85rem; }
.amt-zero   { color: var(--muted); font-weight: 400; }

/* ── DIRECTS BADGE ── */
.directs-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 28px; height: 24px; padding: 0 .5rem;
  background: rgba(28,61,36,.1); color: var(--g-d);
  border-radius: 20px; font-size: .72rem; font-weight: 800;
  border: 1px solid rgba(28,61,36,.18);
}

/* ── DATE CELL ── */
.date-cell { font-size: .75rem; color: var(--muted); white-space: nowrap; }

/* ── EMPTY STATE ── */
.empty-state { text-align: center; padding: 3.5rem 2rem; }
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #dceede, #c8e0cc);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto .85rem;
}
.empty-state h3 { color: var(--g-d); font-family: 'Cinzel', serif; font-size: .95rem; margin-bottom: .3rem; }
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
  font-size: .78rem; font-weight: 700;
  text-decoration: none; color: var(--muted);
  border: 1.5px solid var(--border); background: #fff;
  transition: all .18s; font-family: 'Outfit', sans-serif;
}
.pg-btn:hover  { border-color: var(--g-d); color: var(--g-d); }
.pg-btn.active { background: var(--g-d); color: #fff; border-color: var(--g-d); box-shadow: 0 2px 8px rgba(28,61,36,.25); }

/* ── MODAL ── */
.modal-bg {
  display: none; position: fixed; inset: 0;
  background: rgba(14,36,20,.55);
  z-index: 999; align-items: center; justify-content: center;
  padding: 1rem; backdrop-filter: blur(5px);
}
.modal-bg.open { display: flex; animation: fadeInBg .2s ease; }
@keyframes fadeInBg { from{opacity:0} to{opacity:1} }

.modal-box {
  background: #fff; border-radius: 20px; width: 100%; max-width: 420px;
  box-shadow: 0 28px 70px rgba(14,36,20,.22);
  overflow: hidden; animation: modalIn .28s cubic-bezier(.34,1.4,.64,1);
  border: 1.5px solid var(--border);
}
@keyframes modalIn {
  from { opacity:0; transform: scale(.94) translateY(16px); }
  to   { opacity:1; transform: scale(1)   translateY(0); }
}
.modal-head {
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  padding: 1.15rem 1.4rem;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 2px solid var(--gold);
}
.modal-head-left { display: flex; align-items: center; gap: .7rem; }
.modal-head-icon {
  width: 34px; height: 34px; border-radius: 9px;
  background: rgba(200,146,42,.2); border: 1px solid rgba(200,146,42,.3);
  display: flex; align-items: center; justify-content: center;
}
.modal-head-icon svg { width: 16px; height: 16px; }
.modal-head h3 {
  font-family: 'Cinzel', serif; color: var(--gold-l);
  font-size: .95rem; font-weight: 700;
}
.modal-close {
  background: rgba(255,255,255,.12); border: 1.5px solid rgba(255,255,255,.2);
  color: rgba(255,255,255,.8); width: 30px; height: 30px;
  border-radius: 50%; font-size: .9rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s; line-height: 1;
}
.modal-close:hover { background: rgba(255,255,255,.25); transform: rotate(90deg); color: #fff; }

.modal-body { padding: 1.4rem; }
.modal-user-tag {
  display: flex; align-items: center; gap: .6rem;
  background: var(--cream); border: 1.5px solid var(--border);
  border-radius: 10px; padding: .65rem .9rem;
  margin-bottom: 1.1rem;
}
.modal-user-avatar {
  width: 32px; height: 32px; border-radius: 8px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  display: flex; align-items: center; justify-content: center;
  font-size: .8rem; font-weight: 800; color: var(--gold-l);
  flex-shrink: 0; font-family: 'Cinzel', serif;
}
.modal-user-name { font-weight: 700; font-size: .875rem; color: var(--g-d); }
.modal-user-bal  { font-size: .72rem; color: var(--muted); margin-top: 1px; }

.f-label {
  display: block; font-size: .72rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .06em;
  color: var(--g-d); margin-bottom: .4rem;
}
.f-control {
  width: 100%; padding: .68rem .95rem;
  border: 1.5px solid var(--border); border-radius: 10px;
  font-family: 'Outfit', sans-serif; font-size: .95rem;
  color: var(--ink); background: var(--cream); outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.f-control:focus {
  border-color: var(--gold); background: #fff;
  box-shadow: 0 0 0 3px rgba(200,146,42,.15);
}
.modal-footer {
  display: flex; gap: .65rem; justify-content: flex-end;
  margin-top: 1.25rem; padding-top: 1rem;
  border-top: 1.5px solid var(--cream-d);
}
.btn { display: inline-flex; align-items: center; gap: .4rem; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: .82rem; transition: all .2s; text-decoration: none; font-family: 'Outfit', sans-serif; padding: .6rem 1.3rem; }
.btn-gold { background: linear-gradient(135deg, var(--gold-d), var(--gold-l)); color: #fff; box-shadow: 0 3px 12px rgba(200,146,42,.3); }
.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(200,146,42,.4); }
.btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--muted); }
.btn-outline:hover { border-color: var(--g-d); color: var(--g-d); background: var(--cream); }
.btn-primary { background: linear-gradient(135deg, var(--g-dd), var(--g)); color: #fff; box-shadow: 0 3px 12px rgba(14,36,20,.2); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14,36,20,.28); }
.btn-sm { padding: .38rem .85rem; font-size: .75rem; border-radius: 8px; }

/* ── RESPONSIVE ── */
@media(max-width:600px) {
  .members-hero { flex-direction: column; align-items: flex-start; }
  .filter-bar { flex-direction: column; align-items: stretch; }
  .members-card thead th:nth-child(n+4) { display: none; }
  .members-card tbody td:nth-child(n+4) { display: none; }
}
</style>

<!-- ══ HERO HEADER ══ -->
<div class="members-hero">
  <div class="hero-left">
    <h1>Members Management</h1>
    <p>View, manage and control all registered Mfills Business Partners</p>
  </div>
  <div class="stat-pills">
    <div class="stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;opacity:.7"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      <span class="pill-lbl">Total</span> <span class="pill-val"><?= number_format($total) ?></span>
    </div>
    <div class="stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="#5db870" stroke-width="2.5" style="width:12px;height:12px"><polyline points="20 6 9 17 4 12"/></svg>
      <span class="pill-lbl">Active</span> <span class="pill-val"><?= number_format($active_count) ?></span>
    </div>
    <div class="stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="#e57373" stroke-width="2.5" style="width:12px;height:12px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      <span class="pill-lbl">Inactive</span> <span class="pill-val"><?= number_format($inactive_count) ?></span>
    </div>
    <div class="stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="var(--gold-l)" stroke-width="2" style="width:12px;height:12px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span class="pill-lbl">Today</span> <span class="pill-val">+<?= $today_count ?></span>
    </div>
  </div>
</div>

<!-- ══ SEARCH BAR ══ -->
<form method="GET">
  <div class="filter-bar">
    <div class="search-wrap">
      <span class="s-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </span>
      <input type="text" name="q" placeholder="Search by name, email or referral code…" value="<?= e($q) ?>">
    </div>
    <span class="filter-count">
      <?= $q ? number_format($total).' result'.($total!=1?'s':'').' for "'.e($q).'"' : number_format($total).' members total' ?>
    </span>
    <button type="submit" class="btn btn-primary btn-sm">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:13px;height:13px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Search
    </button>
    <?php if ($q): ?>
      <a href="users.php" class="btn btn-outline btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Clear
      </a>
    <?php endif; ?>
  </div>
</form>

<!-- ══ MEMBERS TABLE ══ -->
<div class="members-card">
  <div style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th style="width:42px">#</th>
        <th>Member</th>
        <th>Referral Code</th>
        <th>Referred By</th>
        <th style="text-align:center">Directs</th>
        <th>PSB Earned</th>
        <th>Wallet</th>
        <th style="text-align:center">Status</th>
        <th>Joined</th>
        <th style="text-align:center">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
      <tr><td colspan="10">
        <div class="empty-state">
          <div class="empty-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" style="width:28px;height:28px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <h3><?= $q ? 'No results found' : 'No members yet' ?></h3>
          <p><?= $q ? 'Try a different search term' : 'Members will appear here once they register' ?></p>
        </div>
      </td></tr>
    <?php else: foreach ($users as $u): ?>
    <tr>
      <td><span style="font-size:.72rem;color:var(--muted);font-weight:500"><?= $u['id'] ?></span></td>
      <td>
        <div class="member-cell">
          <div class="member-avatar"><?= mb_substr($u['username'], 0, 2) ?></div>
          <div>
            <div class="member-name"><?= e($u['username']) ?></div>
            <div class="member-email"><?= e($u['email']) ?></div>
            <?php if (!empty($u['mbpin'])): ?>
              <div class="member-mbpin"><?= e($u['mbpin']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </td>
      <td><span class="ref-chip"><?= e($u['referral_code']) ?></span></td>
      <td style="font-size:.82rem;color:var(--muted)"><?= e($u['referrer_name'] ?? '—') ?></td>
      <td style="text-align:center">
        <span class="directs-badge"><?= $u['directs'] ?></span>
      </td>
      <td>
        <?php if ((float)$u['earned'] > 0): ?>
          <span class="amt-earned"><?= inr($u['earned']) ?></span>
        <?php else: ?>
          <span class="amt-zero">—</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ((float)$u['wallet'] > 0): ?>
          <span class="amt-wallet"><?= inr($u['wallet']) ?></span>
        <?php else: ?>
          <span class="amt-zero">₹0.00</span>
        <?php endif; ?>
      </td>
      <td style="text-align:center">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="uid" value="<?= $u['id'] ?>">
          <button type="submit" class="status-btn <?= $u['is_active'] ? 'active-btn' : 'inactive-btn' ?>">
            <span class="status-dot"></span>
            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
          </button>
        </form>
      </td>
      <td><span class="date-cell"><?= date('d M Y', strtotime($u['created_at'])) ?></span></td>
      <td>
        <div class="action-group">
          <button type="button"
            onclick="openWalletModal(<?= $u['id'] ?>,'<?= e($u['username']) ?>','<?= inr($u['wallet']) ?>')"
            class="act-btn act-btn-wallet" title="Credit Wallet">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="7" width="20" height="15" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
          </button>
          <form method="POST" onsubmit="return confirm('Delete <?= e($u['username']) ?>? This cannot be undone.')" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <button type="submit" class="act-btn act-btn-delete" title="Delete Member">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="pagination">
    <span class="pg-info">Showing <?= $off+1 ?>–<?= min($off+$lim,$total) ?> of <?= number_format($total) ?> members</span>
    <div class="pg-links">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&q=<?= urlencode($q) ?>" class="pg-btn">‹</a>
      <?php endif; ?>
      <?php
        $start = max(1, $page-2);
        $end   = min($pages, $page+2);
        if ($start > 1) echo '<span class="pg-btn" style="border:none;color:var(--muted)">…</span>';
        for ($p = $start; $p <= $end; $p++):
      ?>
        <a href="?page=<?= $p ?>&q=<?= urlencode($q) ?>" class="pg-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor;
        if ($end < $pages) echo '<span class="pg-btn" style="border:none;color:var(--muted)">…</span>';
      ?>
      <?php if ($page < $pages): ?>
        <a href="?page=<?= $page+1 ?>&q=<?= urlencode($q) ?>" class="pg-btn">›</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══ WALLET CREDIT MODAL ══ -->
<div class="modal-bg" id="walletModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-head-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#C8922A" stroke-width="2" stroke-linecap="round">
            <rect x="2" y="7" width="20" height="15" rx="2"/>
            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            <line x1="12" y1="12" x2="12" y2="16"/>
            <line x1="10" y1="14" x2="14" y2="14"/>
          </svg>
        </div>
        <h3>Credit Wallet</h3>
      </div>
      <button class="modal-close" onclick="closeModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="modal-user-tag">
        <div class="modal-user-avatar" id="walletAvatarEl">—</div>
        <div>
          <div class="modal-user-name" id="walletUsername">—</div>
          <div class="modal-user-bal">Current balance: <strong id="walletCurrent" style="color:var(--gold-d)"></strong></div>
        </div>
      </div>
      <form method="POST" id="walletForm">
        <input type="hidden" name="action" value="add_wallet">
        <input type="hidden" name="uid" id="walletUid">
        <div style="margin-bottom:.9rem">
          <label class="f-label">Amount to Credit (₹)</label>
          <input type="number" name="amount" id="walletAmount" class="f-control" min="1" step="0.01" placeholder="e.g. 500.00" required>
        </div>
        <div class="modal-footer">
          <button type="button" onclick="closeModal()" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancel
          </button>
          <button type="submit" class="btn btn-gold">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:13px;height:13px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Balance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openWalletModal(uid, username, current) {
  document.getElementById('walletUid').value           = uid;
  document.getElementById('walletUsername').textContent = username;
  document.getElementById('walletCurrent').textContent  = current;
  document.getElementById('walletAvatarEl').textContent = username.substring(0,2).toUpperCase();
  document.getElementById('walletAmount').value         = '';
  document.getElementById('walletModal').classList.add('open');
  setTimeout(function(){ document.getElementById('walletAmount').focus(); }, 200);
}
function closeModal() {
  var m = document.getElementById('walletModal');
  m.style.animation = 'fadeInBg .15s ease reverse';
  setTimeout(function(){ m.classList.remove('open'); m.style.animation = ''; }, 130);
}
document.getElementById('walletModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});
</script>

<?php require_once '_footer.php'; ?>