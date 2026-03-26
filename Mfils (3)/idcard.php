<?php
// idcard.php — Mfills Business Partner ID Card
$pageTitle = 'My ID Card – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId   = currentUserId();
$user     = getUser($userId);
$stats    = getUserStats($userId);

$mbpin    = $stats['mbpin']        ?? ($user['mbpin']    ?? 'MBP-000000');
$rank     = $stats['clubRank']     ?? 'NONE';
$refCode  = $user['referral_code'] ?? '';
$joinDate = isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : date('M Y');
$phone    = $user['phone']         ?? '';
$name     = $user['full_name']     ?? $user['username']  ?? 'Partner';
$email    = $user['email']         ?? '';

// Initials (max 2 chars)
$nameParts = explode(' ', trim($name));
$initials  = strtoupper(
    mb_substr($nameParts[0], 0, 1) .
    (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : mb_substr($nameParts[0], 1, 1))
);

// Verify & Register URLs
$verifyUrl   = APP_URL . '/verify/'       . urlencode($mbpin);
$registerUrl = APP_URL . '/register.php?ref=' . urlencode($refCode);

// Rank map
$rankMap = [
    'CC'   => ['label' => 'Chairman Club',          'icon' => '👑', 'bg' => 'linear-gradient(135deg,#FFD700,#FFA500)', 'tc' => '#1a3b22'],
    'GAC'  => ['label' => 'Global Ambassador Club',  'icon' => '🌍', 'bg' => 'linear-gradient(135deg,#1a3b22,#2a6336)', 'tc' => '#e0aa40'],
    'PC'   => ['label' => 'Prestige Club',           'icon' => '🏆', 'bg' => 'linear-gradient(135deg,#8B4513,#CD7F32)', 'tc' => '#fff'],
    'RSC'  => ['label' => 'Rising Star Club',        'icon' => '⭐', 'bg' => 'linear-gradient(135deg,#1565C0,#1E88E5)', 'tc' => '#fff'],
    'NONE' => ['label' => 'Business Partner',         'icon' => '🌿', 'bg' => 'linear-gradient(135deg,#1a3b22,#2a6336)', 'tc' => '#e0aa40'],
];
$ri = $rankMap[$rank] ?? $rankMap['NONE'];

// WhatsApp share message
$waMsg = 'I am a verified Mfills Business Partner! MBPIN: ' . $mbpin . ' | Join using referral code: ' . $refCode . ' → ' . $registerUrl;

include __DIR__ . '/includes/header.php';
?>
<!— External Libraries —>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
/* ─── ROOT TOKENS ─── */
:root {
  --gdd:#0b1d0f; --gd:#1a3b22; --gm:#2a6336; --gl:#3a8a4a; --gll:#4dac5e;
  --gold:#c8922a; --gold-l:#e0aa40; --gold-d:#9a6e1a; --gold-ll:#f5d48a;
  --iv:#f8f5ef; --ivd:#ede8de; --ivdd:#d8cfc0;
  --ink:#0e1f13; --mu:#6a8870;
  --card-w:85.6mm; --card-h:54mm;
}
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { background:#e8e4dc; font-family:'Outfit',sans-serif; -webkit-font-smoothing:antialiased; min-height:100vh; }

/* ─── PAGE SHELL ─── */
.icp-page { background:var(--iv); min-height:80vh; }

/* ─── PAGE HEADER ─── */
.icp-header {
  background:linear-gradient(135deg,var(--gdd) 0%,var(--gd) 55%,var(--gm) 100%);
  padding:2.25rem 0 2rem; border-bottom:3px solid var(--gold);
  position:relative; overflow:hidden; text-align:center;
}
.icp-header::before {
  content:''; position:absolute; inset:0; pointer-events:none;
  background-image:radial-gradient(circle,rgba(200,146,42,.07) 1.5px,transparent 1.5px);
  background-size:24px 24px;
}
.icp-header-inner { position:relative; z-index:1; }
.icp-header h1 {
  font-family:'Cinzel',serif; font-size:clamp(1.5rem,3vw,2rem);
  font-weight:900; color:#fff; margin-bottom:.3rem;
}
.icp-header h1 em { color:var(--gold-l); font-style:italic; }
.icp-header p { color:rgba(255,255,255,.45); font-size:.85rem; }

/* ─── WRAP ─── */
.icp-wrap { max-width:860px; margin:0 auto; padding:2.5rem 1.5rem 4rem; }

/* ─── SECTION LABEL ─── */
.icp-label {
  font-family:'Cinzel',serif; font-size:.65rem; font-weight:800;
  text-transform:uppercase; letter-spacing:.14em; color:var(--mu);
  display:flex; align-items:center; gap:.5rem; margin-bottom:1rem;
}
.icp-label::after { content:''; flex:1; height:1px; background:var(--ivdd); }

/* ─── CARDS ROW ─── */
.icp-cards-row {
  display:flex; gap:2rem; justify-content:center;
  flex-wrap:wrap; margin-bottom:.75rem;
}
.icp-card-col { display:flex; flex-direction:column; align-items:center; gap:.5rem; }
.icp-card-side-label {
  font-size:.6rem; font-weight:700; letter-spacing:.14em;
  text-transform:uppercase; color:var(--mu);
}

/* ─── THE CARD ─── */
.id-card {
  width:var(--card-w); height:var(--card-h);
  border-radius:3.5mm; overflow:hidden;
  position:relative; flex-shrink:0;
  font-family:'Outfit','Segoe UI',sans-serif;
  -webkit-font-smoothing:antialiased;
  transition:transform .35s cubic-bezier(.34,1.3,.64,1), box-shadow .35s;
  box-shadow:0 14px 44px rgba(0,0,0,.32), 0 0 0 1px rgba(200,146,42,.25);
  cursor:default;
}
.id-card:hover {
  box-shadow:0 22px 56px rgba(0,0,0,.4), 0 0 0 1px rgba(200,146,42,.4);
}

/* ══ FRONT ══ */
.idc-front {
  width:100%; height:100%;
  background:
    radial-gradient(ellipse 60% 80% at 80% -20%,rgba(58,138,74,.22) 0%,transparent 60%),
    radial-gradient(ellipse 50% 60% at -10% 110%,rgba(200,146,42,.1) 0%,transparent 60%),
    linear-gradient(160deg,#0b1d0f 0%,#1a3b22 45%,#0d2015 100%);
  position:relative; overflow:hidden;
}
.idc-front::before {
  content:''; position:absolute; inset:0; pointer-events:none; z-index:1;
  background-image:repeating-linear-gradient(45deg,transparent,transparent 2px,rgba(255,255,255,.012) 2px,rgba(255,255,255,.012) 4px);
}
.idc-topbar {
  position:absolute; top:0; left:0; right:0; height:4px; z-index:10;
  background:linear-gradient(90deg,transparent,var(--gold-d) 8%,var(--gold-ll) 35%,var(--gold) 50%,var(--gold-ll) 65%,var(--gold-d) 92%,transparent);
}
.idc-botbar {
  position:absolute; bottom:0; left:0; right:0; height:2.5px; z-index:10;
  background:linear-gradient(90deg,transparent,var(--gold-d) 20%,var(--gold-l) 50%,var(--gold-d) 80%,transparent);
  opacity:.7;
}
.idc-arc {
  position:absolute; border-radius:50%; pointer-events:none; z-index:0;
}
.arc-1 { width:90mm; height:90mm; border:1px solid rgba(200,146,42,.08); top:-30mm; right:-20mm; }
.arc-2 { width:60mm; height:60mm; border:1px solid rgba(200,146,42,.06); top:-10mm; right:0mm; }

/* Front Left strip */
.idc-left {
  position:absolute; top:4px; left:0; bottom:2.5px; width:82px;
  display:flex; flex-direction:column; align-items:center;
  padding:9px 0 8px; border-right:1px solid rgba(200,146,42,.15);
  z-index:5;
}
.idc-logo-area { display:flex; flex-direction:column; align-items:center; gap:2px; margin-bottom:7px; }
.idc-logo-img  { height:24px; width:auto; filter:brightness(1.1) drop-shadow(0 1px 3px rgba(0,0,0,.5)); object-fit:contain; }
.idc-logo-fallback {
  font-family:'Cinzel',serif; font-size:10px; font-weight:900;
  color:#fff; letter-spacing:.1em; display:none;
}
.idc-logo-tagline { font-size:4px; color:rgba(200,146,42,.65); letter-spacing:.1em; text-align:center; line-height:1.5; text-transform:uppercase; }
.idc-logo-div { width:40px; height:1px; background:linear-gradient(90deg,transparent,rgba(200,146,42,.35),transparent); margin-bottom:7px; }
.idc-avatar {
  width:52px; height:52px; border-radius:8px;
  background:linear-gradient(145deg,var(--gold-d) 0%,var(--gold-l) 60%,var(--gold-ll) 100%);
  display:flex; align-items:center; justify-content:center;
  font-family:'Cinzel',serif; font-size:18px; font-weight:900; color:var(--gdd);
  border:1.5px solid rgba(200,146,42,.35); box-shadow:0 3px 10px rgba(0,0,0,.45);
  margin-bottom:6px; flex-shrink:0; position:relative;
}
.idc-avatar::after {
  content:''; position:absolute; top:0; left:0; right:0; height:50%;
  background:linear-gradient(180deg,rgba(255,255,255,.18) 0%,transparent 100%);
  border-radius:6px 6px 0 0;
}
.idc-rank {
  border-radius:20px; padding:3px 8px; font-size:5.5px; font-weight:800;
  letter-spacing:.05em; text-align:center; max-width:72px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:auto;
}
.idc-card-type { margin-top:auto; font-size:4px; color:rgba(255,255,255,.2); letter-spacing:.12em; text-transform:uppercase; text-align:center; }

/* Front Right panel */
.idc-right {
  position:absolute; top:9px; left:87px; right:8px; bottom:7px;
  display:flex; flex-direction:column; z-index:5;
}
.idc-role { font-size:4.5px; color:rgba(200,146,42,.7); font-weight:800; letter-spacing:.22em; text-transform:uppercase; margin-bottom:3.5px; }
.idc-name {
  font-family:'Cinzel',serif; font-size:12px; font-weight:900;
  color:#fff; letter-spacing:.04em; line-height:1.15; margin-bottom:2px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  text-shadow:0 1px 4px rgba(0,0,0,.5);
}
.idc-mbpin-row { display:flex; align-items:center; gap:5px; margin-bottom:6px; }
.idc-mbpin-lbl { font-size:4.5px; color:rgba(200,146,42,.55); font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
.idc-mbpin-val {
  font-family:'Courier New',monospace; font-size:10px; font-weight:900;
  color:var(--gold-l); letter-spacing:.12em; text-shadow:0 0 8px rgba(200,146,42,.4);
}
.idc-div { height:1px; background:linear-gradient(90deg,rgba(200,146,42,.45),rgba(200,146,42,.05),transparent); margin-bottom:5px; }
.idc-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:3px; margin-bottom:5px; }
.idc-ic { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07); border-radius:4px; padding:4px 6px; }
.idc-ic-lbl { font-size:4px; color:rgba(255,255,255,.28); font-weight:700; text-transform:uppercase; letter-spacing:.1em; margin-bottom:2px; }
.idc-ic-val { font-size:7.5px; font-weight:700; color:rgba(255,255,255,.85); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.idc-phone-row { display:flex; align-items:center; gap:4px; margin-bottom:4px; }
.idc-phone-icon { font-size:6px; color:rgba(200,146,42,.6); }
.idc-phone-val { font-size:7px; font-weight:600; color:rgba(255,255,255,.65); letter-spacing:.06em; font-family:'Courier New',monospace; }
.idc-barcode-area {
  margin-top:auto; border-top:1px solid rgba(200,146,42,.12);
  padding-top:4px; display:flex; align-items:flex-end; gap:7px;
}
.idc-bars { display:flex; align-items:flex-end; gap:1.4px; height:18px; }
.idc-bars span { display:inline-block; background:rgba(200,146,42,.4); border-radius:1px; width:1.6px; }
.idc-barcode-txt { font-family:'Courier New',monospace; font-size:4.5px; color:rgba(255,255,255,.18); letter-spacing:.1em; padding-bottom:1px; }
.idc-active { margin-left:auto; display:flex; align-items:center; gap:2.5px; font-size:5.5px; font-weight:700; color:#4dac5e; padding-bottom:2px; }
.idc-active::before { content:''; width:5px; height:5px; border-radius:50%; background:#4dac5e; box-shadow:0 0 4px rgba(77,172,94,.6); }

/* ══ BACK ══ */
.idc-back {
  width:100%; height:100%;
  background:
    radial-gradient(ellipse 70% 60% at 100% 0%,rgba(26,59,34,.35) 0%,transparent 60%),
    linear-gradient(155deg,#0b1d0f 0%,#152e1b 50%,#0d2015 100%);
  position:relative; overflow:hidden;
}
.idc-back::before {
  content:''; position:absolute; inset:0; pointer-events:none; z-index:0;
  background-image:
    linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);
  background-size:14px 14px;
}
.idc-back-topbar {
  position:absolute; top:0; left:0; right:0; height:4px; z-index:10;
  background:linear-gradient(90deg,transparent,var(--gold-d) 8%,var(--gold-ll) 35%,var(--gold) 50%,var(--gold-ll) 65%,var(--gold-d) 92%,transparent);
}
.idc-back-botbar {
  position:absolute; bottom:0; left:0; right:0; height:2.5px; z-index:10;
  background:linear-gradient(90deg,transparent,var(--gold-d) 20%,var(--gold-l) 50%,var(--gold-d) 80%,transparent);
  opacity:.65;
}
.idc-back-left {
  position:absolute; top:9px; left:11px; bottom:8px; width:185px;
  display:flex; flex-direction:column; z-index:5;
}
.idc-back-coname { font-family:'Cinzel',serif; font-size:7px; font-weight:900; color:#fff; letter-spacing:.1em; margin-bottom:4px; line-height:1.3; }
.idc-back-coname em { color:var(--gold-l); font-style:normal; }
.idc-back-sep { height:1px; background:linear-gradient(90deg,var(--gold),rgba(200,146,42,.08)); margin-bottom:5px; }
.idc-back-cert { font-size:5.5px; color:rgba(255,255,255,.52); line-height:1.75; font-weight:300; margin-bottom:5px; }
.idc-back-cert strong { color:rgba(255,255,255,.82); font-weight:700; }
.idc-back-bullets { display:flex; flex-direction:column; gap:3px; margin-bottom:6px; }
.idc-back-bullet { display:flex; align-items:center; gap:4px; font-size:5.5px; color:rgba(255,255,255,.6); }
.idc-bul-dot {
  width:8px; height:8px; border-radius:50%; background:var(--gold);
  display:flex; align-items:center; justify-content:center;
  font-size:4.5px; color:var(--gdd); font-weight:900; flex-shrink:0;
}
.idc-back-contact { margin-top:auto; display:flex; flex-direction:column; gap:2.5px; }
.idc-back-contact-row { font-size:5.5px; color:rgba(255,255,255,.42); display:flex; align-items:center; gap:3px; }
.idc-back-disclaimer { font-size:4px; color:rgba(255,255,255,.2); line-height:1.55; border-top:1px solid rgba(255,255,255,.07); padding-top:4px; margin-top:4px; }
.idc-back-right {
  position:absolute; top:9px; right:9px; bottom:8px; width:105px;
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:5px; z-index:5;
}
.idc-qr-frame {
  width:78px; height:78px; background:#fff; border-radius:6px; padding:5px;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 3px 12px rgba(0,0,0,.5), 0 0 0 1px rgba(200,146,42,.25);
  position:relative;
}
.idc-qr-frame::before,.idc-qr-frame::after {
  content:''; position:absolute; width:10px; height:10px; border-color:var(--gold); border-style:solid;
}
.idc-qr-frame::before { top:-1px; left:-1px; border-width:2px 0 0 2px; border-radius:3px 0 0 0; }
.idc-qr-frame::after  { bottom:-1px; right:-1px; border-width:0 2px 2px 0; border-radius:0 0 3px 0; }
#idc-qr, #idc-qr img, #idc-qr canvas { width:68px !important; height:68px !important; display:block; }
.idc-qr-hint { font-size:4.5px; color:rgba(255,255,255,.32); text-align:center; letter-spacing:.07em; text-transform:uppercase; line-height:1.6; }
.idc-qr-mbpin {
  font-family:'Courier New',monospace; font-size:6.5px; font-weight:700; color:var(--gold-l);
  letter-spacing:.12em; background:rgba(200,146,42,.12); border:1px solid rgba(200,146,42,.28);
  border-radius:4px; padding:2px 8px; text-align:center;
}

/* ─── ACTIONS ─── */
.icp-actions { display:flex; gap:.75rem; justify-content:center; flex-wrap:wrap; margin:1.75rem 0 2rem; }
.icp-btn {
  display:inline-flex; align-items:center; gap:.45rem;
  padding:.72rem 1.6rem; border-radius:50px; border:none;
  font-family:'Outfit',sans-serif; font-size:.84rem; font-weight:700;
  cursor:pointer; transition:all .25s; text-decoration:none; letter-spacing:.03em;
}
.icp-btn-dl {
  background:linear-gradient(135deg,var(--gold-d),var(--gold-l));
  color:var(--gdd); box-shadow:0 4px 20px rgba(200,146,42,.4);
  animation:btnPulse 2.5s ease-in-out 1.2s infinite;
}
@keyframes btnPulse {
  0%,100%{box-shadow:0 4px 20px rgba(200,146,42,.4);}
  50%{box-shadow:0 4px 28px rgba(200,146,42,.65),0 0 0 8px rgba(200,146,42,.08);}
}
.icp-btn-dl:hover { background:linear-gradient(135deg,var(--gold-l),var(--gold-ll)); transform:translateY(-2px); animation:none; }
.icp-btn-print { background:#fff; color:var(--gd); border:1.5px solid var(--ivdd); }
.icp-btn-print:hover { border-color:var(--gl); box-shadow:0 4px 16px rgba(26,59,34,.12); transform:translateY(-1px); }
.icp-btn-wa { background:rgba(37,211,102,.1); color:#128C7E; border:1.5px solid rgba(37,211,102,.25); }
.icp-btn-wa:hover { background:rgba(37,211,102,.2); transform:translateY(-1px); }
.icp-btn-back { background:var(--ivd); color:var(--mu); border:1.5px solid var(--ivdd); font-size:.78rem; }
.icp-btn-back:hover { color:var(--gd); border-color:var(--gl); transform:translateY(-1px); }

/* ─── INFO TILES ─── */
.icp-info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.5rem; }
.icp-info-tile {
  background:#fff; border:1.5px solid var(--ivdd); border-radius:14px;
  padding:1.2rem; text-align:center; box-shadow:0 2px 10px rgba(26,59,34,.05);
  transition:transform .25s cubic-bezier(.34,1.3,.64,1), box-shadow .25s;
}
.icp-info-tile:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(26,59,34,.1); }
.icp-info-icon { font-size:1.6rem; margin-bottom:.45rem; display:block; }
.icp-info-lbl { font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--mu); margin-bottom:.3rem; }
.icp-info-val { font-family:'Cinzel',serif; font-size:.95rem; font-weight:800; color:var(--gd); word-break:break-all; }

/* ─── PRINT ─── */
@media print {
  *{ -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
  body{ background:#fff !important; }
  .icp-header,.icp-label,.icp-actions,.icp-info-grid,nav,footer,.mf-footer,.nav-landing,.nav-dashboard{ display:none !important; }
  .icp-page{ padding:0 !important; background:#fff !important; }
  .icp-wrap{ padding:5mm !important; }
  .icp-cards-row{ gap:6mm !important; }
  .id-card{ width:85.6mm !important; height:54mm !important; border-radius:3.5mm !important; box-shadow:none !important; transform:none !important; animation:none !important; }
  .icp-card-side-label{ font-size:6pt !important; }
}
@page{ size:A4; margin:1cm; }

@media(max-width:720px){ .icp-cards-row{ flex-direction:column; } .icp-info-grid{ grid-template-columns:1fr 1fr; } }
@media(max-width:420px){ .id-card{ width:100%; max-width:323px; } .icp-actions{ flex-direction:column; align-items:stretch; } .icp-btn{ justify-content:center; } .icp-info-grid{ grid-template-columns:1fr; } }
</style>

<!-- ══ PAGE HEADER ══ -->
<div class="icp-header">
  <div class="container icp-header-inner">
    <h1>🪪 My <em>Mfills ID Card</em></h1>
    <p>Your official Business Partner identity card — print-ready &amp; PDF downloadable</p>
  </div>
</div>

<div class="icp-page">
<div class="icp-wrap">

  <div class="icp-label">Your ID Card — Front &amp; Back</div>
  <div class="icp-cards-row" id="icpCardsRow">

    <!-- ═══ FRONT ═══ -->
    <div class="icp-card-col">
      <div class="icp-card-side-label">Front Side</div>
      <div class="id-card" id="idcFront">
        <div class="idc-front">
          <div class="idc-topbar"></div>
          <div class="idc-arc arc-1"></div>
          <div class="idc-arc arc-2"></div>

          <div class="idc-left">
            <div class="idc-logo-area">
              <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" class="idc-logo-img"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
              <span class="idc-logo-fallback">🌿 MFILLS®</span>
              <span class="idc-logo-tagline">Filling Life<br>with Wellness</span>
            </div>
            <div class="idc-logo-div"></div>
            <div class="idc-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="idc-rank" style="background:<?= $ri['bg'] ?>;color:<?= $ri['tc'] ?>">
              <?= $ri['icon'] ?> <?= htmlspecialchars($ri['label']) ?>
            </div>
            <div class="idc-card-type">MBP ID Card</div>
          </div>

          <div class="idc-right">
            <div class="idc-role">Mfills Business Partner</div>
            <div class="idc-name"><?= htmlspecialchars($name) ?></div>
            <div class="idc-mbpin-row">
              <span class="idc-mbpin-lbl">MBPIN</span>
              <span class="idc-mbpin-val"><?= htmlspecialchars($mbpin) ?></span>
            </div>
            <div class="idc-div"></div>
            <div class="idc-info-grid">
              <div class="idc-ic">
                <div class="idc-ic-lbl">Referral Code</div>
                <div class="idc-ic-val"><?= htmlspecialchars($refCode) ?></div>
              </div>
              <div class="idc-ic">
                <div class="idc-ic-lbl">Member Since</div>
                <div class="idc-ic-val"><?= htmlspecialchars($joinDate) ?></div>
              </div>
              <div class="idc-ic" style="grid-column:1/-1">
                <div class="idc-ic-lbl">Email</div>
                <div class="idc-ic-val" style="font-size:6.5px">
                  <?= htmlspecialchars(strlen($email) > 28 ? substr($email, 0, 26) . '…' : $email) ?>
                </div>
              </div>
            </div>
            <?php if ($phone): ?>
            <div class="idc-phone-row">
              <span class="idc-phone-icon">📱</span>
              <span class="idc-phone-val"><?= htmlspecialchars($phone) ?></span>
            </div>
            <?php endif; ?>
            <div class="idc-barcode-area">
              <div>
                <div class="idc-bars" id="idcBars"></div>
                <div class="idc-barcode-txt"><?= htmlspecialchars($mbpin) ?></div>
              </div>
              <div class="idc-active">Active</div>
            </div>
          </div>

          <div class="idc-botbar"></div>
        </div>
      </div>
    </div>

    <!-- ═══ BACK ═══ -->
    <div class="icp-card-col">
      <div class="icp-card-side-label">Back Side</div>
      <div class="id-card" id="idcBack">
        <div class="idc-back">
          <div class="idc-back-topbar"></div>

          <div class="idc-back-left">
            <div class="idc-back-coname">MFILLS <em>INDIA</em> PRIVATE LIMITED</div>
            <div class="idc-back-sep"></div>
            <div class="idc-back-cert">
              This certifies that the holder is an <strong>independent
              Mfills Business Partner</strong> duly registered under
              the Mfills Business Partnership Programme.
            </div>
            <div class="idc-back-bullets">
              <div class="idc-back-bullet"><div class="idc-bul-dot">✓</div> Authorized to promote Mfills products</div>
              <div class="idc-back-bullet"><div class="idc-bul-dot">✓</div> Not an employee of the company</div>
              <div class="idc-back-bullet"><div class="idc-bul-dot">✓</div> Subject to Mfills Code of Ethics</div>
            </div>
            <div class="idc-back-contact">
              <div class="idc-back-contact-row">🌐 mfillsindia.com</div>
              <div class="idc-back-contact-row">📧 care@mfillsindia.com</div>
              <div class="idc-back-contact-row">📞 088 77777 889</div>
            </div>
            <div class="idc-back-disclaimer">
              ⚠ For identification only · If found return to Mfills India Pvt. Ltd. · CIN: U74999JH2021PTC016067
            </div>
          </div>

          <div class="idc-back-right">
            <div class="idc-qr-frame">
              <div id="idc-qr"></div>
            </div>
            <div class="idc-qr-hint">Scan to verify<br>partner identity</div>
            <div class="idc-qr-mbpin"><?= htmlspecialchars($mbpin) ?></div>
          </div>

          <div class="idc-back-botbar"></div>
        </div>
      </div>
    </div>

  </div><!-- /.icp-cards-row -->

  <!-- ── Actions ── -->
  <div class="icp-actions">
    <button class="icp-btn icp-btn-dl" onclick="downloadPdf()" id="pdfBtn">⬇️ Download PDF</button>
    <button class="icp-btn icp-btn-print" onclick="window.print()">🖨️ Print Card</button>
    <a href="https://wa.me/?text=<?= urlencode($waMsg) ?>"
       target="_blank" rel="noopener" class="icp-btn icp-btn-wa">💬 Share on WhatsApp</a>
    <a href="<?= APP_URL ?>/dashboard.php" class="icp-btn icp-btn-back">← Dashboard</a>
  </div>

  <!-- ── Info tiles ── -->
  <div class="icp-label">Card Details</div>
  <div class="icp-info-grid">
    <div class="icp-info-tile">
      <span class="icp-info-icon">🪪</span>
      <div class="icp-info-lbl">MBPIN</div>
      <div class="icp-info-val"><?= htmlspecialchars($mbpin) ?></div>
    </div>
    <div class="icp-info-tile">
      <span class="icp-info-icon">🔗</span>
      <div class="icp-info-lbl">Referral Code</div>
      <div class="icp-info-val"><?= htmlspecialchars($refCode) ?></div>
    </div>
    <div class="icp-info-tile">
      <span class="icp-info-icon">📅</span>
      <div class="icp-info-lbl">Member Since</div>
      <div class="icp-info-val"><?= htmlspecialchars($joinDate) ?></div>
    </div>
  </div>

</div><!-- /.icp-wrap -->
</div><!-- /.icp-page -->

<script>
/* ── Barcode bars ── */
(function(){
  var el = document.getElementById('idcBars');
  if (!el) return;
  var h = [14,20,11,18,14,8,20,14,18,11,20,14,11,18,20,14,8,18,14,20];
  h.forEach(function(v,i){
    var b = document.createElement('span');
    b.style.height  = v + 'px';
    b.style.opacity = (0.22 + (i%4) * 0.14).toFixed(2);
    el.appendChild(b);
  });
})();

/* ── QR Code ── */
(function(){
  new QRCode(document.getElementById('idc-qr'), {
    text:         <?= json_encode($verifyUrl) ?>,
    width:        68, height: 68,
    colorDark:    '#0b1d0f',
    colorLight:   '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
})();

/* ── 3D Tilt ── */
['idcFront','idcBack'].forEach(function(id){
  var c = document.getElementById(id);
  if (!c) return;
  c.addEventListener('mousemove', function(e){
    var r = c.getBoundingClientRect();
    var x = (e.clientX - r.left) / r.width  - .5;
    var y = (e.clientY - r.top)  / r.height - .5;
    c.style.transform = 'perspective(800px) rotateY('+(x*14)+'deg) rotateX('+(-y*9)+'deg) scale(1.025)';
  });
  c.addEventListener('mouseleave', function(){
    c.style.transition = 'transform .5s cubic-bezier(.34,1.3,.64,1)';
    c.style.transform  = '';
    setTimeout(function(){ c.style.transition = ''; }, 500);
  });
});

/* ── PDF Download ── */
async function downloadPdf(){
  var btn  = document.getElementById('pdfBtn');
  var orig = btn.innerHTML;
  btn.innerHTML = '⏳ Generating PDF…';
  btn.disabled  = true;
  btn.style.animation = 'none';

  ['idcFront','idcBack'].forEach(function(id){
    var c = document.getElementById(id);
    if(c){ c.style.transform='none'; c.style.transition='none'; }
  });

  try {
    var front = document.getElementById('idcFront');
    var back  = document.getElementById('idcBack');

    var cf = await html2canvas(front, { scale:3, useCORS:true, allowTaint:true, backgroundColor:null, logging:false });
    var cb = await html2canvas(back,  { scale:3, useCORS:true, allowTaint:true, backgroundColor:null, logging:false });

    var { jsPDF } = window.jspdf;
    var cW = 85.6, cH = 54;
    var pW = cW + 10, pH = cH * 2 + 22;

    var pdf = new jsPDF({ orientation:'portrait', unit:'mm', format:[pW, pH] });
    pdf.setFillColor(255,255,255);
    pdf.rect(0,0,pW,pH,'F');

    /* Front */
    pdf.addImage(cf.toDataURL('image/png'), 'PNG', 5, 4, cW, cH);
    pdf.setFontSize(5.5); pdf.setTextColor(150,150,150);
    pdf.text('FRONT SIDE', pW/2, cH+8, {align:'center'});

    /* Back */
    pdf.addImage(cb.toDataURL('image/png'), 'PNG', 5, cH+11, cW, cH);
    pdf.text('BACK SIDE', pW/2, cH*2+14.5, {align:'center'});

    /* Footer */
    pdf.setFontSize(4.5); pdf.setTextColor(170,160,145);
    pdf.text(
      'Mfills Business Partner ID · MBPIN: <?= addslashes(htmlspecialchars($mbpin)) ?> · mfillsindia.com',
      pW/2, pH-2, {align:'center'}
    );

    pdf.save('Mfills_IDCard_<?= preg_replace('/[^A-Za-z0-9\-_]/', '', $mbpin) ?>.pdf');

  } catch(err){
    alert('PDF generation failed.\nUse Print (Ctrl+P) → "Save as PDF" instead.\n\nError: ' + err.message);
    console.error(err);
  } finally {
    btn.innerHTML = orig;
    btn.disabled  = false;
    btn.style.animation = '';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>