<?php
// dashboard.php
$pageTitle = 'Dashboard – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$userId = currentUserId();
$user   = getUser($userId);
$stats  = getUserStats($userId);
$cs     = $stats['clubStatus']; // Business Club 3-state status
$refUrl = APP_URL . '/register.php?ref=' . $user['referral_code'];

function countPerLevel(int $rootId, int $maxLvl = 7): array {
    $counts = []; $ids = [$rootId];
    for ($lvl = 1; $lvl <= $maxLvl; $lvl++) {
        if (empty($ids)) break;
        $in   = implode(',', array_map('intval', $ids));
        $rows = db()->query("SELECT id FROM users WHERE referrer_id IN ($in)")->fetchAll();
        $newIds = array_column($rows, 'id');
        $counts[$lvl] = count($newIds);
        $ids = $newIds;
    }
    return $counts;
}
$levelCounts  = countPerLevel($userId);
$totalNetwork = array_sum($levelCounts);

// KYC status
$kyc = null;
try {
    $stmt = db()->prepare("SELECT * FROM kyc_submissions WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$kycStatus = $kyc['status'] ?? null;

// ID card
$userCard = getUserIDCard($userId);
if (!$userCard) { createUserIdCard($userId); $userCard = getUserIDCard($userId); }

// Recent orders & notifications
$recentOrders  = getUserOrders($userId, 5);
$notifications = getUserNotifications($userId, false, 10);
$unreadCount   = getUnreadNotificationCount($userId);
$announcements = getActiveAnnouncements($userId);

// Rank map
$rankMap = [
    'CC'   => ['label'=>'Chairman Club',         'icon'=>'👑','bg'=>'linear-gradient(135deg,#FFD700,#FFA500)','tc'=>'#1a3b22'],
    'GAC'  => ['label'=>'Global Ambassador Club','icon'=>'🌍','bg'=>'linear-gradient(135deg,#1a3b22,#2a6336)','tc'=>'#e0aa40'],
    'PC'   => ['label'=>'Prestige Club',         'icon'=>'🏆','bg'=>'linear-gradient(135deg,#8B4513,#CD7F32)','tc'=>'#fff'],
    'RSC'  => ['label'=>'Rising Star Club',      'icon'=>'⭐','bg'=>'linear-gradient(135deg,#1565C0,#1E88E5)','tc'=>'#fff'],
    'NONE' => ['label'=>'Business Partner',      'icon'=>'🌿','bg'=>'linear-gradient(135deg,#1a3b22,#2a6336)','tc'=>'#e0aa40'],
];
$curRank  = $stats['clubRank'] ?? 'NONE';
$rankInfo = $rankMap[$curRank] ?? $rankMap['NONE'];

$uname     = $user['full_name'] ?? $user['username'] ?? 'Partner';
$nameParts = explode(' ', trim($uname));
$initials  = strtoupper(mb_substr($nameParts[0],0,1) . (isset($nameParts[1]) ? mb_substr($nameParts[1],0,1) : mb_substr($nameParts[0],1,1)));

include __DIR__ . '/includes/header.php';
?>
<style>
:root {
  --green-dd:#0e2414;--green-d:#1a3b22;--green:#1d4a28;--green-m:#2a6336;
  --green-l:#3a8a4a;--green-ll:#4dac5e;
  --gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;--gold-ll:#f5c96a;
  --jade:#0F7B5C;--jade-l:#13a077;--jade-d:#0a5a43;
  --coral:#E8534A;
  --ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;
  --ink:#152018;--muted:#5a7a60;
}

/* ── PAGE HEADER ── */
.dash-header{background:linear-gradient(135deg,var(--green-dd) 0%,var(--green-d) 50%,var(--green-m) 100%);padding:2.5rem 0 3.5rem;position:relative;overflow:hidden;border-bottom:3px solid var(--gold);}
.dash-header::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(200,146,42,.08) 1.5px,transparent 1.5px);background-size:24px 24px;pointer-events:none;}
.dash-header::after{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.018) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.018) 1px,transparent 1px);background-size:48px 48px;pointer-events:none;}
.dash-header-arc{position:absolute;border-radius:50%;pointer-events:none;}
.dash-header-arc-1{width:340px;height:240px;border:2px solid rgba(200,146,42,.12);bottom:-80px;left:-60px;animation:arcPulse 6s ease-in-out infinite;}
.dash-header-arc-2{width:280px;height:280px;border:2px solid rgba(77,172,94,.1);top:-100px;right:-80px;animation:arcPulse 8s 2s ease-in-out infinite reverse;}
@keyframes arcPulse{0%,100%{transform:scale(1);opacity:.6;}50%{transform:scale(1.06);opacity:1;}}
.dash-header-glow{position:absolute;border-radius:50%;pointer-events:none;animation:headerGlow 8s ease-in-out infinite;}
.hg-1{width:400px;height:400px;background:radial-gradient(circle,rgba(58,138,74,.2) 0%,transparent 70%);top:-100px;right:-50px;}
.hg-2{width:280px;height:280px;background:radial-gradient(circle,rgba(200,146,42,.12) 0%,transparent 70%);bottom:-80px;left:20%;animation-delay:3s;}
@keyframes headerGlow{0%,100%{transform:scale(1) translate(0,0);}50%{transform:scale(1.1) translate(10px,-8px);}}
.dash-header .container{position:relative;z-index:1;}
.dash-header h1{font-family:'Cinzel','Georgia',serif;font-size:clamp(1.4rem,3vw,1.95rem);font-weight:900;color:#fff;margin-bottom:.3rem;line-height:1.2;animation:fadeSlideUp .5s ease both;}
.dash-header h1 em{color:var(--gold-l);font-style:italic;}
.dash-header p{color:rgba(255,255,255,.48);font-size:.85rem;margin-bottom:.9rem;animation:fadeSlideUp .5s .1s ease both;}
@keyframes fadeSlideUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:none;}}
.hdr-badges{display:flex;gap:.6rem;flex-wrap:wrap;animation:fadeSlideUp .5s .2s ease both;}
.hbadge{border-radius:20px;padding:.22rem .85rem;font-size:.75rem;font-weight:700;transition:transform .2s;}
.hbadge:hover{transform:translateY(-1px);}
.hb-pin{background:rgba(255,255,255,.1);color:rgba(255,255,255,.85);}
.hb-club{background:rgba(15,123,92,.28);color:#6DDFB8;}
.hb-warn{background:rgba(232,83,74,.22);color:#FCA5A5;}
.hb-lapsed{background:rgba(200,146,42,.28);color:#fde68a;}
.hb-rank{background:rgba(200,146,42,.25);color:var(--gold-l);}

/* ── LAYOUT ── */
.container{max-width:1160px;margin:0 auto;padding:0 1.5rem;}
.dash-body{background:var(--ivory);padding:2rem 0 4rem;}

/* ── STAT TILES ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;margin-top:-1.75rem;position:relative;z-index:2;}
.stat-tile{background:#fff;border-radius:16px;padding:1.5rem 1.25rem;border:1.5px solid var(--ivory-dd);box-shadow:0 4px 20px rgba(26,59,34,.08);border-top:3px solid var(--ivory-dd);transition:transform .25s cubic-bezier(.34,1.3,.64,1),box-shadow .25s;display:flex;flex-direction:column;gap:.25rem;opacity:0;transform:translateY(24px);animation:tileEntry .5s ease forwards;}
.stat-tile:nth-child(1){animation-delay:.1s;}.stat-tile:nth-child(2){animation-delay:.18s;}.stat-tile:nth-child(3){animation-delay:.26s;}.stat-tile:nth-child(4){animation-delay:.34s;}
@keyframes tileEntry{to{opacity:1;transform:translateY(0);}}
.stat-tile:hover{transform:translateY(-5px) scale(1.015);box-shadow:0 14px 36px rgba(26,59,34,.14);}
.stat-tile.t-green{border-top-color:var(--green-d);}.stat-tile.t-gold{border-top-color:var(--gold);}.stat-tile.t-coral{border-top-color:var(--coral);}.stat-tile.t-jade{border-top-color:var(--jade);}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;margin-bottom:.4rem;transition:transform .3s cubic-bezier(.34,1.56,.64,1);}
.stat-tile:hover .stat-icon{transform:scale(1.2) rotate(-5deg);}
.si-green{background:rgba(26,59,34,.08);}.si-gold{background:rgba(200,146,42,.1);}.si-coral{background:rgba(232,83,74,.1);}.si-jade{background:rgba(15,123,92,.1);}
.stat-val{font-family:'Cinzel','Georgia',serif;font-size:1.8rem;font-weight:700;color:var(--ink);line-height:1;}
.stat-label{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}
.stat-sub{font-size:.77rem;color:var(--muted);margin-top:.2rem;}
.bv-prog-bar{height:5px;background:var(--ivory-dd);border-radius:4px;margin-top:.4rem;overflow:hidden;}
.bv-prog-fill{height:100%;border-radius:4px;transition:width 1.2s cubic-bezier(.22,1,.36,1);}

/* ── BUSINESS CLUB STATUS CARD ── */
.club-status-card{display:flex;align-items:flex-start;gap:1.5rem;flex-wrap:wrap;padding:1.25rem 1.5rem;border-radius:16px;border:1.5px solid var(--ivory-dd);margin-top:1.1rem;background:#fff;box-shadow:0 2px 12px rgba(26,59,34,.06);opacity:0;transform:translateY(16px);transition:opacity .5s .45s ease,transform .5s .45s ease;}
.club-status-card.visible{opacity:1;transform:none;}
.club-status-card.active{border-left:4px solid var(--jade);}
.club-status-card.lapsed{border-left:4px solid var(--gold);}
.club-status-card.inactive{border-left:4px solid var(--coral);}
.csc-left{display:flex;gap:1rem;align-items:flex-start;flex:1;min-width:260px;}
.csc-right{display:flex;flex-direction:column;gap:.65rem;min-width:240px;flex:1;}
.csc-icon{font-size:2rem;flex-shrink:0;margin-top:.1rem;}
.csc-title{font-family:'Cinzel','Georgia',serif;font-size:.95rem;font-weight:800;color:var(--green-d);margin-bottom:.25rem;}
.csc-msg{font-size:.82rem;color:var(--muted);line-height:1.65;margin-bottom:.6rem;}
.csc-benefits{display:flex;flex-wrap:wrap;gap:.4rem;}
.csc-benefit{font-size:.72rem;font-weight:700;border-radius:20px;padding:.2rem .7rem;}
.csc-benefit.unlocked{background:rgba(15,123,92,.1);color:var(--jade);}
.csc-benefit.locked{background:rgba(232,83,74,.08);color:var(--coral);text-decoration:line-through;opacity:.75;}
.csc-prog-label{display:flex;justify-content:space-between;font-size:.72rem;font-weight:700;color:var(--muted);margin-bottom:.3rem;}
.csc-prog-label span{color:var(--green-d);font-family:'Cinzel',serif;}
.csc-prog-track{height:8px;background:var(--ivory-dd);border-radius:4px;overflow:hidden;}
.csc-prog-fill{height:100%;border-radius:4px;transition:width 1.2s cubic-bezier(.22,1,.36,1);position:relative;overflow:hidden;}
.csc-prog-fill::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);animation:shimmerBar 2s linear infinite;}
.csc-prog-fill.active{background:linear-gradient(90deg,var(--jade-d),var(--jade-l));}
.csc-prog-fill.lapsed{background:linear-gradient(90deg,var(--gold-d),var(--gold-l));}
.csc-prog-fill.inactive{background:linear-gradient(90deg,var(--coral),#f87171);}
@keyframes shimmerBar{from{transform:translateX(-100%);}to{transform:translateX(100%);}}
.csc-shop-btn{display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--gold-d),var(--gold-l));color:var(--green-dd);border:none;border-radius:50px;padding:.65rem 1.25rem;font-size:.8rem;font-weight:800;font-family:'Nunito',sans-serif;text-decoration:none;box-shadow:0 4px 14px rgba(200,146,42,.35);transition:all .22s;margin-top:.3rem;animation:btnPulse 2.5s ease-in-out 2s infinite;}
.csc-shop-btn:hover{transform:translateY(-2px);animation:none;}

/* ── SECTION TITLE ── */
.dash-section-title{font-family:'Cinzel','Georgia',serif;font-size:1.15rem;font-weight:700;color:var(--green-d);margin:2.25rem 0 1rem;display:flex;align-items:center;gap:.6rem;opacity:0;transform:translateX(-16px);transition:opacity .6s ease,transform .6s ease;}
.dash-section-title.visible{opacity:1;transform:none;}
.dash-section-title::after{content:'';flex:1;height:1.5px;background:linear-gradient(90deg,var(--gold),transparent);border-radius:2px;}

/* ── CARDS ── */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
.card{background:#fff;border-radius:16px;border:1.5px solid var(--ivory-dd);box-shadow:0 2px 12px rgba(26,59,34,.05);overflow:hidden;opacity:0;transform:translateY(20px);transition:opacity .6s ease,transform .6s ease,box-shadow .25s;}
.card.visible{opacity:1;transform:translateY(0);}
.card:hover{box-shadow:0 8px 28px rgba(26,59,34,.1);}
.card-header{font-family:'Cinzel','Georgia',serif;font-size:.92rem;font-weight:700;color:var(--green-d);padding:.9rem 1.25rem;border-bottom:1.5px solid var(--ivory-dd);background:var(--ivory-d);display:flex;align-items:center;gap:.5rem;}
.card-body{padding:1.25rem;}

/* ── BV BAR ── */
.bv-bar{background:#fff;border-radius:14px;border:1.5px solid var(--ivory-dd);padding:1.1rem 1.5rem;display:flex;gap:2rem;flex-wrap:wrap;align-items:center;margin-top:1.1rem;box-shadow:0 2px 10px rgba(26,59,34,.05);opacity:0;transform:translateY(16px);transition:opacity .5s .4s ease,transform .5s .4s ease;}
.bv-bar.visible{opacity:1;transform:none;}
.bv-item .bv-lbl{font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.2rem;}
.bv-item .bv-val{font-family:'Cinzel','Georgia',serif;font-size:1.35rem;font-weight:700;transition:transform .3s cubic-bezier(.34,1.56,.64,1);}
.bv-item:hover .bv-val{transform:scale(1.1);}
.bv-val-green{color:var(--green-d);}.bv-val-gold{color:var(--gold);}
.rank-prog{margin-left:auto;min-width:170px;}
.rank-next{font-size:.8rem;color:var(--muted);margin-bottom:.35rem;}
.rank-track{background:var(--ivory-dd);border-radius:4px;height:8px;overflow:hidden;}
.rank-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--green-m),var(--green-l),var(--jade));transition:width 1.2s cubic-bezier(.22,1,.36,1);position:relative;overflow:hidden;}
.rank-fill::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);animation:shimmerBar 2s linear infinite;}
.rank-sub{font-size:.7rem;color:var(--muted);margin-top:.25rem;}

/* ── REFERRAL ── */
.ref-box{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;}
.ref-input{flex:1;min-width:0;padding:.65rem .9rem;background:var(--ivory-d);border:1.5px solid var(--ivory-dd);border-radius:8px;font-family:'Nunito',sans-serif;font-size:.8rem;color:var(--muted);outline:none;cursor:text;transition:border-color .2s;}
.ref-input:focus{border-color:var(--green-l);}
.copy-btn{background:var(--green-d);color:#fff;border:none;border-radius:8px;padding:.65rem 1.1rem;font-size:.82rem;font-weight:800;cursor:pointer;transition:all .2s;white-space:nowrap;font-family:'Nunito',sans-serif;}
.copy-btn:hover{background:var(--green-m);transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,59,34,.3);}
.copy-btn.copied{background:var(--jade);}
.ref-code-badge{display:inline-flex;align-items:center;gap:.4rem;background:rgba(200,146,42,.1);border:1px solid rgba(200,146,42,.25);color:var(--gold-d);border-radius:20px;font-size:.75rem;font-weight:800;padding:.22rem .75rem;margin-top:.65rem;}
.share-btns{display:flex;gap:.6rem;margin-top:.85rem;flex-wrap:wrap;}
.share-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .9rem;border-radius:20px;font-size:.75rem;font-weight:800;text-decoration:none;transition:all .22s;border:none;cursor:pointer;font-family:'Nunito',sans-serif;}
.share-wa{background:rgba(37,211,102,.1);color:#128C7E;}
.share-wa:hover{background:rgba(37,211,102,.2);transform:translateY(-1px);}
.share-copy{background:rgba(26,59,34,.08);color:var(--green-d);}
.share-copy:hover{background:rgba(26,59,34,.15);transform:translateY(-1px);}

/* ── COMMISSION TABLE ── */
.comm-table{width:100%;border-collapse:collapse;font-size:.875rem;}
.comm-table thead th{padding:.65rem 1rem;text-align:left;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);background:var(--ivory-d);border-bottom:1.5px solid var(--ivory-dd);}
.comm-table tbody tr{border-bottom:1px solid var(--ivory-d);transition:background .15s;}
.comm-table tbody tr:last-child{border-bottom:none;}
.comm-table tbody tr:hover{background:var(--ivory-d);}
.comm-table tbody td{padding:.75rem 1rem;vertical-align:middle;color:var(--ink);}
.lvl-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;font-size:.72rem;font-weight:800;}
.lvl-1{background:rgba(200,146,42,.18);color:var(--gold-d);}.lvl-2{background:rgba(26,59,34,.14);color:var(--green-d);}
.lvl-3{background:rgba(15,123,92,.14);color:var(--jade-d);}.lvl-4,.lvl-5,.lvl-6,.lvl-7{background:rgba(90,122,96,.1);color:var(--muted);}
.rate-pill{display:inline-block;background:rgba(26,59,34,.08);color:var(--green-d);border-radius:20px;font-size:.75rem;font-weight:800;padding:.15rem .65rem;}
.rate-pill.top{background:rgba(200,146,42,.15);color:var(--gold-d);}
.member-bar-wrap{display:flex;align-items:center;gap:.6rem;}
.member-bar{flex:1;height:5px;border-radius:3px;background:var(--ivory-dd);overflow:hidden;max-width:80px;}
.member-bar-fill{height:100%;border-radius:3px;background:var(--jade);transition:width .8s ease;}
.member-info{display:flex;flex-direction:column;line-height:1.3;}
.member-count{font-size:.8rem;font-weight:700;color:var(--ink);}
.member-bv{font-size:.68rem;color:var(--muted);font-weight:600;}
.earned-amt{font-weight:800;color:var(--green-d);position:relative;display:inline-flex;align-items:center;gap:.35rem;cursor:default;}
.earned-amt .bv-tip{position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--green-dd);color:#fff;font-size:.72rem;font-weight:700;padding:.3rem .65rem;border-radius:8px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .18s,transform .18s;transform:translateX(-50%) translateY(4px);z-index:10;}
.earned-amt .bv-tip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:5px solid transparent;border-top-color:var(--green-dd);}
.earned-amt:hover .bv-tip{opacity:1;transform:translateX(-50%) translateY(0);}
.earned-zero{color:var(--muted);font-weight:400;}

/* ── LEADERSHIP CLUBS ── */
.club-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-top:1rem;}
.club-card{border-radius:12px;padding:1.1rem 1rem;border:1.5px solid var(--ivory-dd);background:#fff;text-align:center;transition:transform .3s cubic-bezier(.34,1.3,.64,1),box-shadow .25s,border-color .25s;opacity:0;transform:translateY(20px) scale(.96);}
.club-card.visible{opacity:1;transform:translateY(0) scale(1);}
.club-card:hover{transform:translateY(-4px) scale(1.03);box-shadow:0 10px 28px rgba(26,59,34,.12);}
.club-card.achieved{border-color:var(--gold);box-shadow:0 0 0 2px rgba(200,146,42,.18);}
.club-icon{font-size:1.6rem;margin-bottom:.4rem;display:block;transition:transform .35s cubic-bezier(.34,1.56,.64,1);}
.club-card:hover .club-icon{transform:scale(1.25) rotate(-8deg);}
.club-name{font-family:'Cinzel','Georgia',serif;font-size:.8rem;font-weight:700;color:var(--green-d);margin-bottom:.25rem;}
.club-abbr{display:inline-block;background:rgba(26,59,34,.07);color:var(--muted);border-radius:20px;font-size:.68rem;font-weight:800;padding:.1rem .55rem;margin-bottom:.4rem;}
.club-bv{font-size:.72rem;color:var(--muted);font-weight:600;line-height:1.4;}
.dist-section{margin-top:1.5rem;border-top:1.5px solid var(--ivory-dd);padding-top:1.2rem;}
.dist-lbl{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.8rem;}
.dist-row{display:flex;align-items:center;gap:.7rem;margin-bottom:.55rem;}
.dist-level{width:54px;font-size:.72rem;font-weight:800;color:var(--muted);}
.dist-track{flex:1;height:8px;background:var(--ivory-dd);border-radius:4px;overflow:hidden;}
.dist-fill{height:100%;border-radius:4px;transition:width .7s ease;position:relative;overflow:hidden;}
.dist-fill::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);animation:shimmerBar 2s linear infinite;}
.dist-pct{width:36px;font-size:.72rem;font-weight:800;text-align:right;}

/* ── RECENT COMMISSIONS ── */
.recent-table{width:100%;border-collapse:collapse;font-size:.875rem;}
.recent-table thead th{padding:.65rem 1rem;text-align:left;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);background:var(--ivory-d);border-bottom:1.5px solid var(--ivory-dd);}
.recent-table tbody tr{border-bottom:1px solid var(--ivory-d);transition:background .15s;}
.recent-table tbody tr:last-child{border-bottom:none;}
.recent-table tbody tr:hover{background:var(--ivory-d);}
.recent-table tbody td{padding:.75rem 1rem;vertical-align:middle;color:var(--ink);}
.date-cell{color:var(--muted)!important;font-size:.8rem;}
.comm-positive{font-weight:800;color:var(--jade);display:inline-flex;align-items:center;gap:.25rem;}
.comm-positive::before{content:'+';}
.buyer-cell{display:flex;align-items:center;gap:.6rem;}
.buyer-avatar{width:28px;height:28px;border-radius:8px;background:rgba(26,59,34,.08);border:1.5px solid var(--ivory-dd);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:var(--green-d);flex-shrink:0;text-transform:uppercase;}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:3rem 2rem;color:var(--muted);}
.empty-icon{font-size:3rem;margin-bottom:.75rem;display:block;opacity:.5;animation:floatIcon 3s ease-in-out infinite;}
@keyframes floatIcon{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.empty-state h3{font-family:'Cinzel','Georgia',serif;font-size:1.15rem;color:var(--green-d);margin-bottom:.35rem;}
.empty-state p{font-size:.875rem;line-height:1.7;margin-bottom:1rem;}
.btn-share{display:inline-flex;align-items:center;gap:.4rem;background:linear-gradient(135deg,var(--gold),var(--gold-l));color:var(--green-dd);font-weight:800;font-family:'Nunito',sans-serif;font-size:.875rem;padding:.6rem 1.5rem;border-radius:50px;text-decoration:none;box-shadow:0 4px 14px rgba(200,146,42,.4);transition:all .2s;animation:pulseGold 2.5s ease-in-out 1s infinite;}
@keyframes pulseGold{0%{box-shadow:0 0 0 0 rgba(200,146,42,.5);}70%{box-shadow:0 0 0 12px rgba(200,146,42,0);}100%{box-shadow:0 0 0 0 rgba(200,146,42,0);}}
.btn-share:hover{transform:translateY(-2px);animation:none;}
@keyframes btnPulse{0%,100%{box-shadow:0 4px 16px rgba(200,146,42,.35);}50%{box-shadow:0 4px 24px rgba(200,146,42,.6),0 0 0 7px rgba(200,146,42,.08);}}
.table-scroll{overflow-x:auto;}
.count-up{display:inline-block;}

/* ── NOTIFICATIONS ── */
.notification-bell{position:fixed;top:20px;right:20px;width:48px;height:48px;background:linear-gradient(135deg,var(--green-d),var(--green-m));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;cursor:pointer;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,.2);transition:transform .2s;}
.notification-bell:hover{transform:scale(1.1);}
.notification-count{position:absolute;top:-5px;right:-5px;background:var(--coral);color:white;font-size:.7rem;font-weight:800;min-width:20px;height:20px;border-radius:10px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid white;}
.notifications-dropdown{position:fixed;top:80px;right:20px;width:380px;max-height:500px;background:white;border-radius:16px;border:1.5px solid var(--ivory-dd);box-shadow:0 10px 30px rgba(0,0,0,.2);z-index:1001;display:none;overflow:hidden;}
.notifications-dropdown.show{display:block;animation:slideDown .3s ease;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.notifications-header{padding:1rem;background:linear-gradient(135deg,var(--green-d),var(--green-m));color:white;display:flex;justify-content:space-between;align-items:center;}
.notifications-header h3{font-family:'Cinzel',serif;font-size:.95rem;margin:0;}
.mark-read-btn{background:rgba(255,255,255,.2);border:none;color:white;font-size:.7rem;padding:.3rem .8rem;border-radius:20px;cursor:pointer;transition:background .2s;}
.mark-read-btn:hover{background:rgba(255,255,255,.3);}
.notifications-list{max-height:400px;overflow-y:auto;}
.notification-item{padding:1rem;border-bottom:1px solid var(--ivory-d);display:flex;gap:.8rem;transition:background .2s;cursor:pointer;}
.notification-item:hover{background:var(--ivory);}
.notification-item.unread{background:rgba(26,59,34,.05);}
.notification-item.important{border-left:3px solid var(--gold);}
.notification-icon{font-size:1.2rem;flex-shrink:0;}
.notification-content{flex:1;}
.notification-title{font-weight:700;font-size:.85rem;color:var(--ink);margin-bottom:.2rem;}
.notification-message{font-size:.8rem;color:var(--muted);margin-bottom:.3rem;line-height:1.4;}
.notification-time{font-size:.7rem;color:var(--muted);opacity:.7;}
.mark-read{background:none;border:1px solid var(--green-d);color:var(--green-d);width:24px;height:24px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:.8rem;cursor:pointer;flex-shrink:0;transition:all .2s;}
.mark-read:hover{background:var(--green-d);color:white;}
.notifications-footer{padding:.8rem 1rem;text-align:center;border-top:1px solid var(--ivory-dd);background:var(--ivory-d);}
.notifications-footer a{color:var(--green-d);font-size:.8rem;font-weight:700;text-decoration:none;}

/* ── ID CARD ── */
.dash-idcard-wrap{display:flex;gap:2rem;align-items:center;flex-wrap:wrap;}
.dash-idcard-preview{width:280px;flex-shrink:0;border-radius:14px;overflow:hidden;background:linear-gradient(160deg,#0b1d0f 0%,#1a3b22 50%,#0d2015 100%);box-shadow:0 12px 36px rgba(0,0,0,.28),0 0 0 1px rgba(200,146,42,.25);position:relative;font-family:'Outfit',sans-serif;transition:transform .35s cubic-bezier(.34,1.3,.64,1);}
.dash-idcard-preview:hover{transform:translateY(-4px) scale(1.02);}
.dicp-topbar{height:3px;background:linear-gradient(90deg,transparent,var(--gold-d) 15%,var(--gold-ll) 50%,var(--gold-d) 85%,transparent);}
.dicp-body{padding:1rem;}
.dicp-header{display:flex;align-items:center;gap:.65rem;margin-bottom:.75rem;}
.dicp-avatar{width:40px;height:40px;border-radius:8px;flex-shrink:0;background:linear-gradient(135deg,var(--gold-d),var(--gold-l));display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:13px;font-weight:900;color:var(--green-dd);border:1.5px solid rgba(200,146,42,.4);}
.dicp-name{font-family:'Cinzel',serif;font-size:10px;font-weight:900;color:#fff;letter-spacing:.04em;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;}
.dicp-role{font-size:7px;color:rgba(200,146,42,.65);font-weight:700;letter-spacing:.12em;text-transform:uppercase;}
.dicp-rank{display:inline-flex;align-items:center;gap:3px;border-radius:20px;padding:3px 9px;font-size:7px;font-weight:800;margin-bottom:.65rem;letter-spacing:.04em;}
.dicp-info{display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-bottom:.65rem;}
.dicp-cell{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:5px;padding:5px 7px;}
.dicp-cell-lbl{font-size:5px;color:rgba(255,255,255,.3);font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:2px;}
.dicp-cell-val{font-size:8px;font-weight:700;color:rgba(255,255,255,.82);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.dicp-mbpin{font-family:'Courier New',monospace;font-size:9px;font-weight:900;color:var(--gold-l);letter-spacing:.1em;}
.dicp-footer{border-top:1px solid rgba(200,146,42,.12);padding-top:.5rem;display:flex;align-items:center;justify-content:space-between;}
.dicp-logo{font-family:'Cinzel',serif;font-size:8px;font-weight:900;color:rgba(255,255,255,.5);letter-spacing:.1em;}
.dicp-active{font-size:6.5px;font-weight:700;color:#4dac5e;display:flex;align-items:center;gap:3px;}
.dicp-active::before{content:'';width:5px;height:5px;border-radius:50%;background:#4dac5e;box-shadow:0 0 4px rgba(77,172,94,.6);}
.dicp-botbar{height:2px;background:linear-gradient(90deg,transparent,rgba(200,146,42,.5),transparent);}
.dash-idcard-actions{flex:1;min-width:200px;}
.dash-idcard-actions h3{font-family:'Cinzel',serif;font-size:1rem;color:var(--green-d);margin-bottom:.4rem;font-weight:700;}
.dash-idcard-actions p{font-size:.82rem;color:var(--muted);line-height:1.65;margin-bottom:1.1rem;}
.idcard-btns{display:flex;flex-direction:column;gap:.65rem;}
.idcard-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;padding:.75rem 1.25rem;border-radius:50px;border:none;font-family:'Outfit',sans-serif;font-size:.84rem;font-weight:700;cursor:pointer;transition:all .22s;text-decoration:none;letter-spacing:.02em;}
.idcard-btn-primary{background:linear-gradient(135deg,var(--gold-d),var(--gold-l));color:var(--green-dd);box-shadow:0 4px 16px rgba(200,146,42,.35);animation:btnPulse 2.5s ease-in-out 1.5s infinite;}
.idcard-btn-primary:hover{background:linear-gradient(135deg,var(--gold-l),var(--gold-ll));transform:translateY(-2px);animation:none;}
.idcard-btn-secondary{background:var(--ivory-d);color:var(--green-d);border:1.5px solid var(--ivory-dd);}
.idcard-btn-secondary:hover{border-color:var(--green-l);background:var(--ivory);transform:translateY(-1px);}
.idcard-btn-wa{background:rgba(37,211,102,.1);color:#0a7055;border:1.5px solid rgba(37,211,102,.25);}
.idcard-btn-wa:hover{background:rgba(37,211,102,.18);transform:translateY(-1px);}
.idcard-last-dl{font-size:.72rem;color:var(--muted);margin-top:.35rem;text-align:center;}

/* ── ANNOUNCEMENTS ── */
.announcements-list{display:flex;flex-direction:column;gap:1rem;}
.announcement-item{display:flex;gap:1rem;padding:1rem;border-radius:12px;background:var(--ivory);border:1px solid var(--ivory-dd);transition:transform .2s;}
.announcement-item:hover{transform:translateX(5px);}
.announcement-item.unread{background:rgba(200,146,42,.05);border-left:3px solid var(--gold);}
.announcement-item.info{border-left:3px solid var(--green-l);}.announcement-item.success{border-left:3px solid var(--jade);}
.announcement-item.warning{border-left:3px solid var(--gold);}.announcement-item.urgent{border-left:3px solid var(--coral);}
.announcement-icon{font-size:1.5rem;flex-shrink:0;}
.announcement-content{flex:1;}
.announcement-content h4{font-family:'Cinzel',serif;font-size:.95rem;color:var(--green-d);margin-bottom:.3rem;}
.announcement-content p{font-size:.85rem;color:var(--ink);margin-bottom:.5rem;line-height:1.6;}
.announcement-meta{display:flex;justify-content:space-between;align-items:center;font-size:.75rem;color:var(--muted);}

/* ── PURCHASES ── */
.purchases-list{display:flex;flex-direction:column;gap:.8rem;}
.purchase-item{display:flex;gap:1rem;padding:.8rem;border-radius:12px;background:var(--ivory);border:1px solid var(--ivory-dd);cursor:pointer;transition:all .2s;}
.purchase-item:hover{transform:translateX(5px);box-shadow:0 4px 12px rgba(0,0,0,.1);}
.purchase-image{width:60px;height:60px;border-radius:10px;overflow:hidden;flex-shrink:0;}
.purchase-image img{width:100%;height:100%;object-fit:cover;}
.purchase-details{flex:1;}
.purchase-details h4{font-size:.9rem;color:var(--green-d);margin-bottom:.3rem;}
.purchase-meta{display:flex;gap:1rem;align-items:center;margin-bottom:.2rem;font-size:.8rem;flex-wrap:wrap;}
.purchase-date{color:var(--muted);}
.purchase-amount{font-weight:700;color:var(--green-d);}
.purchase-status{padding:.2rem .5rem;border-radius:20px;font-size:.7rem;font-weight:700;}
.status-processing{background:rgba(200,146,42,.1);color:var(--gold-d);}
.status-shipped{background:rgba(15,123,92,.1);color:var(--jade);}
.status-delivered{background:rgba(26,59,34,.1);color:var(--green-d);}
.status-cancelled{background:rgba(232,83,74,.1);color:var(--coral);}
.view-all-link{text-align:right;margin-top:1rem;}
.view-all-link a{color:var(--green-d);font-size:.85rem;font-weight:700;text-decoration:none;}

/* ── KYC ── */
.kyc-update-form{margin-top:1rem;}
.kyc-update-form .form-group{margin-bottom:1rem;}
.kyc-update-form .form-label{display:block;font-size:.75rem;font-weight:700;color:var(--green-d);margin-bottom:.3rem;}
.kyc-update-form textarea{width:100%;padding:.8rem;border:1.5px solid var(--ivory-dd);border-radius:10px;font-family:'Nunito',sans-serif;resize:vertical;outline:none;}

/* ── RESPONSIVE ── */
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr);}.grid-2{grid-template-columns:1fr;}.club-grid{grid-template-columns:repeat(2,1fr);}.notifications-dropdown{width:320px;right:10px;}}
@media(max-width:640px){
  .stats-grid{grid-template-columns:repeat(2,1fr);gap:.75rem;}.stat-val{font-size:1.4rem!important;}.dash-header{padding:2rem 0 3rem;}
  .ref-box{flex-direction:column;}.ref-input,.copy-btn{width:100%;}.copy-btn{justify-content:center;display:flex;}.bv-bar{gap:1rem;}
  .notification-bell{width:40px;height:40px;font-size:1.2rem;top:10px;right:10px;}.notifications-dropdown{width:300px;top:60px;right:10px;}
  .dash-idcard-wrap{flex-direction:column;}.dash-idcard-preview{width:100%;max-width:320px;}
  .csc-left,.csc-right{min-width:unset;width:100%;}
  .comm-table thead{display:none;}.comm-table tbody tr{display:grid;grid-template-columns:1fr 1fr;gap:.3rem .75rem;padding:.75rem 1rem;border-bottom:1px solid var(--ivory-d);}
  .comm-table tbody tr:last-child{border-bottom:none;}.comm-table tbody td{padding:0;border:none;font-size:.82rem;display:flex;align-items:center;}
  .comm-table tbody td::before{content:attr(data-label);font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-right:.35rem;}
  .recent-table thead{display:none;}.recent-table tbody tr{display:block;padding:.85rem 1rem;border-bottom:1px solid var(--ivory-d);}
  .recent-table tbody tr:last-child{border-bottom:none;}.recent-table tbody td{display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;border:none;font-size:.875rem;}
  .recent-table tbody td::before{content:attr(data-label);font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);}
  .recent-table tbody td.date-cell{justify-content:flex-start;font-size:.75rem;padding-bottom:.35rem;border-bottom:1px dashed var(--ivory-dd);margin-bottom:.2rem;}
  .recent-table tbody td.date-cell::before{display:none;}
}
@media(max-width:380px){.stats-grid{grid-template-columns:1fr;}.stat-tile{flex-direction:row;align-items:center;gap:1rem;}}
</style>

<!-- ════ PAGE HEADER ════ -->
<div class="dash-header">
  <div class="dash-header-glow hg-1"></div>
  <div class="dash-header-glow hg-2"></div>
  <div class="dash-header-arc dash-header-arc-1"></div>
  <div class="dash-header-arc dash-header-arc-2"></div>
  <div class="container">
    <h1>Welcome back, <em><?= e($user['username']) ?></em> 👋</h1>
    <p>Mfills Business Partner Dashboard — <?= date('d F Y') ?></p>
    <div class="hdr-badges">
      <span class="hbadge hb-pin">🪪 MBPIN: <strong><?= e($stats['mbpin'] ?: ($user['mbpin'] ?? '—')) ?></strong></span>
      <?php if ($curRank !== 'NONE' && defined('CLUB_RANKS') && isset(CLUB_RANKS[$curRank])): ?>
        <span class="hbadge hb-rank"><?= CLUB_RANKS[$curRank]['icon'] ?> <?= CLUB_RANKS[$curRank]['name'] ?></span>
      <?php endif; ?>
      <?php if ($cs['status'] === 'active'): ?>
        <span class="hbadge hb-club">✅ Mfills Business Club — Active</span>
      <?php elseif ($cs['status'] === 'lapsed'): ?>
        <span class="hbadge hb-lapsed">⚠️ Club Renewal Required — <?= number_format($cs['bv_left'], 0) ?> BV needed</span>
      <?php else: ?>
        <span class="hbadge hb-warn">🔒 Business Club Not Activated — <?= number_format($cs['bv_left'], 0) ?> BV needed</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Notification Bell -->
<div class="notification-bell" onclick="toggleNotifications()">
  🔔
  <?php if ($unreadCount > 0): ?>
    <span class="notification-count"><?= $unreadCount ?></span>
  <?php endif; ?>
</div>

<!-- Notifications Dropdown -->
<div class="notifications-dropdown" id="notificationsDropdown">
  <div class="notifications-header">
    <h3>Notifications</h3>
    <?php if ($unreadCount > 0): ?>
      <button onclick="markAllRead()" class="mark-read-btn">✓ Mark all read</button>
    <?php endif; ?>
  </div>
  <div class="notifications-list">
    <?php if (empty($notifications)): ?>
      <div class="notification-item"><div class="notification-content"><div class="notification-message">No notifications</div></div></div>
    <?php else: ?>
      <?php foreach ($notifications as $note): ?>
        <div class="notification-item <?= !$note['is_read']?'unread':'' ?> <?= $note['is_important']?'important':'' ?>"
             onclick="viewNotification(<?= $note['id'] ?>, '<?= e($note['link'] ?? '') ?>')">
          <div class="notification-icon">
            <?php $icons=['announcement'=>'📢','kyc_update'=>'🆔','commission'=>'💰','order_update'=>'📦','system'=>'⚙️']; echo $icons[$note['type']] ?? '📌'; ?>
          </div>
          <div class="notification-content">
            <div class="notification-title"><?= e($note['title']) ?></div>
            <div class="notification-message"><?= e($note['message']) ?></div>
            <div class="notification-time"><?= timeAgo($note['created_at']) ?></div>
          </div>
          <?php if (!$note['is_read']): ?>
            <button onclick="event.stopPropagation();markRead(<?= $note['id'] ?>)" class="mark-read">✓</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="notifications-footer"><a href="<?= APP_URL ?>/notifications.php">View all notifications</a></div>
</div>

<div class="dash-body">
<div class="container">

  <!-- ════ STAT TILES ════ -->
  <div class="stats-grid">
    <div class="stat-tile t-green">
      <div class="stat-icon si-green">💰</div>
      <div class="stat-label">Wallet Balance</div>
      <div class="stat-val count-up" data-target="<?= $user['wallet'] ?>" data-prefix="₹" data-decimals="2">₹<?= number_format($user['wallet'],2) ?></div>
      <div class="stat-sub">Available to withdraw</div>
    </div>

    <!-- Monthly BV tile with progress toward 2500 -->
    <div class="stat-tile t-gold">
      <div class="stat-icon si-gold">📊</div>
      <div class="stat-label">Monthly BV</div>
      <div class="stat-val count-up" data-target="<?= $stats['monthlyBv']??0 ?>" data-suffix=" BV" data-decimals="0"><?= number_format($stats['monthlyBv']??0,0) ?> <span style="font-size:1.1rem;font-weight:700;color:var(--gold)">BV</span></div>
      <div class="bv-prog-bar">
        <div class="bv-prog-fill" style="width:<?= $cs['monthly_pct'] ?>%;background:<?= $cs['status']==='active'?'var(--jade)':($cs['status']==='lapsed'?'var(--gold)':'var(--coral)') ?>"></div>
      </div>
      <div class="stat-sub" style="margin-top:.25rem">
        <?php if ($cs['status'] === 'active'): ?>
          <span style="color:var(--jade);font-weight:700">✅ Club Active this month</span>
        <?php elseif ($cs['status'] === 'lapsed'): ?>
          <span style="color:var(--gold-d);font-weight:700">⚠ <?= number_format($cs['bv_left'],0) ?> BV left to renew</span>
        <?php else: ?>
          <span style="color:var(--coral);font-weight:700">🔒 <?= number_format($cs['bv_left'],0) ?> BV to activate</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="stat-tile t-coral">
      <div class="stat-icon si-coral">🏆</div>
      <div class="stat-label">PSB Earned (Lifetime)</div>
      <div class="stat-val count-up" data-target="<?= $stats['totalComm'] ?>" data-prefix="₹" data-decimals="2">₹<?= number_format($stats['totalComm'],2) ?></div>
      <div class="stat-sub">Partner Sales Bonus total</div>
    </div>

    <div class="stat-tile t-jade">
      <div class="stat-icon si-jade">👥</div>
      <div class="stat-label">Network Size</div>
      <div class="stat-val count-up" data-target="<?= $totalNetwork ?>" data-decimals="0"><?= $totalNetwork ?></div>
      <div class="stat-sub"><?= $stats['directReferrals'] ?> direct sponsors</div>
    </div>
  </div>

  <!-- ════ BUSINESS CLUB STATUS CARD ════ -->
  <div class="club-status-card <?= $cs['status'] ?>" id="clubStatusCard">
    <div class="csc-left">
      <div class="csc-icon">
        <?= $cs['status']==='active' ? '✅' : ($cs['status']==='lapsed' ? '⚠️' : '🔒') ?>
      </div>
      <div>
        <div class="csc-title">Mfills Business Club — <?= e($cs['label']) ?></div>
        <div class="csc-msg"><?= e($cs['message']) ?></div>
        <div class="csc-benefits">
          <span class="csc-benefit <?= $cs['is_active']?'unlocked':'locked' ?>"><?= $cs['is_active']?'✅':'🔒' ?> MShop Plus Access</span>
          <span class="csc-benefit <?= $cs['is_active']?'unlocked':'locked' ?>"><?= $cs['is_active']?'✅':'🔒' ?> PSB Earnings</span>
          <span class="csc-benefit <?= $cs['is_active']?'unlocked':'locked' ?>"><?= $cs['is_active']?'✅':'🔒' ?> Full Income Participation</span>
        </div>
      </div>
    </div>
    <div class="csc-right">
      <?php if ($cs['status'] === 'inactive'): ?>
      <div>
        <div class="csc-prog-label">One-time Activation <span><?= number_format($cs['lifetime_bv'],0) ?> / 2,500 BV</span></div>
        <div class="csc-prog-track"><div class="csc-prog-fill inactive" style="width:<?= $cs['lifetime_pct'] ?>%"></div></div>
      </div>
      <?php endif; ?>
      <div>
        <div class="csc-prog-label">This Month's BV <span><?= number_format($cs['monthly_bv'],0) ?> / 2,500 BV</span></div>
        <div class="csc-prog-track"><div class="csc-prog-fill <?= $cs['status'] ?>" style="width:<?= $cs['monthly_pct'] ?>%"></div></div>
      </div>
      <?php if (!$cs['is_active']): ?>
        <a href="<?= APP_URL ?>/shop.php" class="csc-shop-btn">
          🛒 Shop Now to <?= $cs['status']==='inactive'?'Activate':'Renew' ?> — Need <?= number_format($cs['bv_left'],0) ?> BV
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ════ BV BAR ════ -->
  <div class="bv-bar" id="bvBar">
    <div class="bv-item">
      <div class="bv-lbl">Group BV (Lifetime)</div>
      <div class="bv-val bv-val-green"><?= number_format($stats['groupBv']??0,0) ?></div>
    </div>
    <div class="bv-item">
      <div class="bv-lbl">Club Income Earned</div>
      <div class="bv-val bv-val-gold">₹<?= number_format($stats['clubIncomeTotal']??0,2) ?></div>
    </div>
    <div class="bv-item">
      <div class="bv-lbl">My Purchases</div>
      <div class="bv-val" style="color:var(--ink)"><?= $stats['totalOrders'] ?> <span style="font-size:.9rem;color:var(--muted)">orders</span></div>
    </div>
    <div class="rank-prog">
      <?php
      $nextRank = null; $nextBv = 0;
      $clubDefs = ['RSC'=>['name'=>'Rising Star Club','icon'=>'⭐','monthly_bv'=>50000],'PC'=>['name'=>'Prestige Club','icon'=>'🏆','monthly_bv'=>200000],'GAC'=>['name'=>'Global Ambassador Club','icon'=>'🌍','monthly_bv'=>1000000],'CC'=>['name'=>'Chairman Club','icon'=>'👑','monthly_bv'=>5000000]];
      foreach ($clubDefs as $key => $info) {
        if (($stats['groupBv']??0) < $info['monthly_bv']) { $nextRank=$info; $nextBv=$info['monthly_bv']; break; }
      }
      $pct = $nextBv > 0 ? min(100, round((($stats['groupBv']??0)/$nextBv)*100)) : 100;
      ?>
      <?php if ($nextRank): ?>
        <div class="rank-next">Next rank: <strong><?= $nextRank['icon'].' '.$nextRank['name'] ?></strong></div>
        <div class="rank-track"><div class="rank-fill" id="rankFill" style="width:0%" data-target="<?= $pct ?>%"></div></div>
        <div class="rank-sub"><?= number_format($stats['groupBv']??0,0) ?> / <?= number_format($nextBv,0) ?> BV</div>
      <?php else: ?>
        <div style="font-size:.85rem;font-weight:700;color:var(--jade)">👑 Chairman Club — Highest Rank Achieved!</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ════ REFERRAL + PSB ════ -->
  <div class="dash-section-title" id="st1">🔗 Referral &amp; Earnings</div>
  <div class="grid-2">
    <div class="card" id="refCard">
      <div class="card-header">🔗 Your Referral Link</div>
      <div class="card-body">
        <p style="font-size:.84rem;color:var(--muted);margin-bottom:.85rem;line-height:1.65">Share your referral link and earn PSB when your 7-level network purchases Mfills products!</p>
        <div class="ref-box">
          <input type="text" id="refInput" class="ref-input" value="<?= e($refUrl) ?>" readonly>
          <button class="copy-btn" id="copyBtn" onclick="copyRef(event)">📋 Copy</button>
        </div>
        <div><span class="ref-code-badge">🌿 Your code: <strong><?= e($user['referral_code']) ?></strong></span></div>
        <div class="share-btns">
          <a href="https://wa.me/?text=<?= urlencode('Join me on Mfills! Register free: '.$refUrl) ?>" target="_blank" class="share-btn share-wa">💬 Share on WhatsApp</a>
          <button onclick="copyRef2()" class="share-btn share-copy">🔗 Copy Link</button>
        </div>
      </div>
    </div>
    <div class="card" id="psbCard">
      <div class="card-header">📊 Partner Sales Bonus (PSB) — 7 Levels</div>
      <?php if (!$cs['is_active']): ?>
      <div style="background:rgba(232,83,74,.06);border-bottom:1px solid rgba(232,83,74,.15);padding:.7rem 1.25rem;font-size:.78rem;color:var(--coral);font-weight:600;display:flex;align-items:center;gap:.5rem;">
        🔒 PSB earnings are locked until your Business Club membership is active.
        <a href="<?= APP_URL ?>/shop.php" style="color:var(--coral);font-weight:800;text-decoration:underline;">Activate now →</a>
      </div>
      <?php endif; ?>
      <div class="table-scroll">
        <table class="comm-table">
          <thead>
            <tr>
              <th>Level</th>
              <th>Rate</th>
              <th>Members &amp; BV</th>
              <th>Earned</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $rates   = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];
          $commMap = array_column($stats['commByLevel'], null, 'level');
          $maxMembers = max(array_values($levelCounts) ?: [1]);
          foreach ($rates as $lvl => $rate):
            $c       = $commMap[$lvl] ?? ['cnt'=>0,'total'=>0,'bv_total'=>0];
            $members = $levelCounts[$lvl] ?? 0;
            $barPct  = $maxMembers>0 ? round(($members/max($maxMembers,1))*100) : 0;
            $lvlBv   = $c['bv_total'] ?? 0; // Total BV generated at this level
          ?>
          <tr>
            <td data-label="Level">
              <div class="lvl-badge lvl-<?= $lvl ?>"><?= $lvl ?></div>
            </td>
            <td data-label="Rate">
              <span class="rate-pill <?= $lvl==1?'top':'' ?>"><?= $rate ?>%</span>
            </td>
            <td data-label="Members &amp; BV">
              <div class="member-bar-wrap">
                <div class="member-bar">
                  <div class="member-bar-fill" style="width:0%" data-target="<?= $barPct ?>%"></div>
                </div>
                <div class="member-info">
                  <span class="member-count"><?= $members ?> members</span>
                  <?php if ($lvlBv > 0): ?>
                    <span class="member-bv"><?= number_format($lvlBv, 0) ?> BV</span>
                  <?php else: ?>
                    <span class="member-bv">— BV</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td data-label="Earned">
              <?php
                // commission_amt is stored in ₹, 1 BV = ₹1, so BV = commission_amt
                $earnedBv = $c['total'] ?? 0;
              ?>
              <?php if ($earnedBv > 0): ?>
                <span class="earned-amt">
                  <?= number_format($earnedBv, 0) ?> BV
                  <span class="bv-tip">≈ ₹<?= number_format($earnedBv, 2) ?></span>
                </span>
              <?php else: ?>
                <span class="earned-zero">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ════ LEADERSHIP CLUBS ════ -->
  <div class="dash-section-title" id="st2">👑 Mfills Leadership Clubs</div>
  <div class="card" id="clubCard">
    <div class="card-body">
      <div style="background:linear-gradient(135deg,rgba(26,59,34,.04),rgba(200,146,42,.04));border:1.5px solid rgba(200,146,42,.2);border-radius:14px;padding:1.1rem 1.25rem;margin-bottom:1.25rem;">
        <p style="font-size:.85rem;color:var(--ink);line-height:1.7;margin-bottom:.6rem;">The <strong style="color:var(--green-d)">Mfills Club Income Program</strong> rewards Business Partners based on Business Volume generated within their organisation.</p>
        <p style="font-size:.75rem;color:var(--muted);line-height:1.6;font-style:italic;border-top:1px dashed var(--ivory-dd);padding-top:.55rem;margin-top:.5rem;">⚠️ Qualification subject to verification and compliance with Mfills policies.</p>
      </div>
      <div class="club-grid">
        <?php
        $curGroupBv = $stats['groupBv'] ?? 0;
        $nextClub = null;
        foreach ($clubDefs as $k => $cd) { if ($curGroupBv < $cd['monthly_bv']) { $nextClub=$k; break; } }
        $idx = 0;
        foreach ($clubDefs as $key => $cd):
          $achieved = $curGroupBv >= $cd['monthly_bv'];
          $bvDisplay = ['RSC'=>'50,000 BV','PC'=>'2,00,000 BV','GAC'=>'10,00,000 BV','CC'=>'50,00,000 BV'];
        ?>
        <div class="club-card <?= $achieved?'achieved':'' ?>" data-delay="<?= $idx*80 ?>">
          <span class="club-icon"><?= $cd['icon'] ?></span>
          <div class="club-name"><?= $cd['name'] ?></div>
          <span class="club-abbr"><?= $key ?></span>
          <div class="club-bv"><span style="font-size:.6rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);display:block;margin-bottom:.12rem;">Network BV</span><?= $bvDisplay[$key] ?? '' ?></div>
          <?php if ($nextClub===$key): ?><span style="font-size:.7rem;color:var(--jade);font-weight:700;margin-top:.5rem;display:block">← Your next goal</span>
          <?php elseif ($achieved): ?><span style="font-size:.7rem;color:var(--jade);font-weight:700;margin-top:.5rem;display:block">✅ Achieved</span>
          <?php endif; ?>
        </div>
        <?php $idx++; endforeach; ?>
      </div>
      <div class="dist-section">
        <div class="dist-lbl">PSB Distribution per Level (40% total)</div>
        <?php
        $distRates  = [15,8,6,4,3,2,2];
        $distColors = ['var(--gold)','var(--green-d)','var(--jade)','var(--muted)','var(--muted)','var(--muted)','var(--muted)'];
        foreach ($distRates as $i => $dr):
          $w = round(($dr/15)*100);
        ?>
        <div class="dist-row">
          <div class="dist-level">Level <?= $i+1 ?></div>
          <div class="dist-track"><div class="dist-fill" style="width:0%;background:<?= $distColors[$i] ?>" data-target="<?= $w ?>%"></div></div>
          <div class="dist-pct" style="color:<?= $distColors[$i] ?>"><?= $dr ?>%</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ════ RECENT COMMISSIONS ════ -->
  <div class="dash-section-title" id="st3">⚡ Recent Commission Activity</div>
  <?php if (empty($stats['recentComm'])): ?>
    <div class="card" id="recentCard">
      <div class="empty-state">
        <span class="empty-icon">🌿</span>
        <h3>No commissions yet</h3>
        <p>
          <?php if (!$cs['is_active']): ?>
            Activate your Business Club membership first, then share your referral link to start earning PSB!
          <?php else: ?>
            Share your referral link to start earning PSB automatically!
          <?php endif; ?>
        </p>
        <?php if (!$cs['is_active']): ?>
          <a href="<?= APP_URL ?>/shop.php" class="btn-share">🛒 Activate Business Club</a>
        <?php else: ?>
          <a href="https://wa.me/?text=<?= urlencode('Join me on Mfills! '.$refUrl) ?>" class="btn-share" target="_blank">💬 Share on WhatsApp Now</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
  <div class="card" id="recentCard">
    <div class="table-scroll">
      <table class="recent-table">
        <thead><tr><th>Date</th><th>Buyer</th><th>Product</th><th>Level</th><th>Rate</th><th>Commission</th></tr></thead>
        <tbody>
        <?php foreach ($stats['recentComm'] as $c): ?>
        <tr>
          <td class="date-cell"><?= date('d M Y',strtotime($c['created_at'])) ?></td>
          <td data-label="Buyer"><div class="buyer-cell"><div class="buyer-avatar"><?= strtoupper(substr($c['buyer_name'],0,1)) ?></div><?= e($c['buyer_name']) ?></div></td>
          <td data-label="Product"><?= e($c['product_name']) ?></td>
          <td data-label="Level"><div class="lvl-badge lvl-<?= e($c['level']) ?>"><?= e($c['level']) ?></div></td>
          <td data-label="Rate"><span class="rate-pill <?= $c['level']==1?'top':'' ?>"><?= e($c['rate']) ?>%</span></td>
          <td data-label="Commission"><span class="comm-positive">₹<?= number_format($c['commission_amt'],2) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ════ ID CARD ════ -->
  <div class="dash-section-title" id="st4">🪪 Your Mfills ID Card</div>
  <div class="card" id="idCard">
    <div class="card-body">
      <div class="dash-idcard-wrap">
        <div class="dash-idcard-preview">
          <div class="dicp-topbar"></div>
          <div class="dicp-body">
            <div class="dicp-header">
              <div class="dicp-avatar"><?= e($initials) ?></div>
              <div>
                <div class="dicp-name"><?= e($uname) ?></div>
                <div class="dicp-role">Mfills Business Partner</div>
              </div>
            </div>
            <div class="dicp-rank" style="background:<?= $rankInfo['bg'] ?>;color:<?= $rankInfo['tc'] ?>">
              <?= $rankInfo['icon'] ?> <?= e($rankInfo['label']) ?>
            </div>
            <div class="dicp-info">
              <div class="dicp-cell"><div class="dicp-cell-lbl">MBPIN</div><div class="dicp-cell-val dicp-mbpin"><?= e($stats['mbpin'] ?? $user['mbpin'] ?? '—') ?></div></div>
              <div class="dicp-cell"><div class="dicp-cell-lbl">Ref Code</div><div class="dicp-cell-val"><?= e($user['referral_code'] ?? '—') ?></div></div>
              <div class="dicp-cell"><div class="dicp-cell-lbl">Since</div><div class="dicp-cell-val"><?= isset($user['created_at']) ? date('M Y',strtotime($user['created_at'])) : date('M Y') ?></div></div>
              <div class="dicp-cell"><div class="dicp-cell-lbl">Club</div><div class="dicp-cell-val" style="color:<?= $cs['is_active']?'#4dac5e':'#E8534A' ?>"><?= $cs['is_active']?'Active':'Inactive' ?></div></div>
            </div>
            <div class="dicp-footer">
              <div class="dicp-logo">🌿 MFILLS®</div>
              <div class="dicp-active" style="color:<?= $cs['is_active']?'#4dac5e':'#E8534A' ?>">
                <?= $cs['is_active']?'Active':'Inactive' ?>
              </div>
            </div>
          </div>
          <div class="dicp-botbar"></div>
        </div>
        <div class="dash-idcard-actions">
          <h3>🪪 Your Official ID Card</h3>
          <p>Download your print-ready ID card with QR code, MBPIN, and rank badge — or share it directly on WhatsApp.</p>
          <div class="idcard-btns">
            <a href="<?= APP_URL ?>/idcard.php" class="idcard-btn idcard-btn-primary">⬇️ Download / View ID Card</a>
            <a href="<?= APP_URL ?>/idcard.php" class="idcard-btn idcard-btn-secondary" target="_blank">👁️ Preview Full Card</a>
            <a href="https://wa.me/?text=<?= urlencode('I am a verified Mfills Business Partner! MBPIN: '.($stats['mbpin']??'').' | Join: '.APP_URL.'/register.php?ref='.urlencode($user['referral_code']??'')) ?>"
               target="_blank" rel="noopener" class="idcard-btn idcard-btn-wa">💬 Share on WhatsApp</a>
          </div>
          <div class="idcard-last-dl">Last downloaded: <?= $userCard['last_downloaded_at'] ? date('d M Y',strtotime($userCard['last_downloaded_at'])) : 'Never' ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ════ ANNOUNCEMENTS ════ -->
  <?php if (!empty($announcements)): ?>
  <div class="dash-section-title" id="st5">📢 Announcements</div>
  <div class="card" id="announcements">
    <div class="card-body">
      <div class="announcements-list">
        <?php foreach ($announcements as $ann): ?>
          <div class="announcement-item <?= e($ann['type']) ?> <?= !$ann['is_read']?'unread':'' ?>">
            <div class="announcement-icon"><?php $aicons=['info'=>'ℹ️','success'=>'✅','warning'=>'⚠️','urgent'=>'🚨']; echo $aicons[$ann['type']]??'📢'; ?></div>
            <div class="announcement-content">
              <h4><?= e($ann['title']) ?></h4>
              <p><?= nl2br(e($ann['content'])) ?></p>
              <div class="announcement-meta">
                <span><?= date('d M Y',strtotime($ann['created_at'])) ?></span>
                <?php if (!$ann['is_read']): ?><button onclick="markAnnouncementRead(<?= $ann['id'] ?>)" class="mark-read-btn">Mark as read</button><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ════ RECENT PURCHASES ════ -->
  <div class="dash-section-title" id="st6">📦 Recent Purchases</div>
  <div class="card" id="purchases">
    <div class="card-body">
      <?php if (empty($recentOrders)): ?>
        <div class="empty-state">
          <span class="empty-icon">🛒</span>
          <h3>No purchases yet</h3>
          <p>Start shopping at MShop to track your orders here!</p>
          <a href="<?= APP_URL ?>/shop.php" class="btn-share" style="text-decoration:none;">🛒 Browse Products</a>
        </div>
      <?php else: ?>
        <div class="purchases-list">
          <?php foreach ($recentOrders as $order): ?>
            <div class="purchase-item" onclick="viewOrder(<?= $order['id'] ?>)">
              <div class="purchase-image">
                <img src="<?= e($order['product_image'] ?? '/assets/img/product-placeholder.jpg') ?>" alt="<?= e($order['product_name']) ?>">
              </div>
              <div class="purchase-details">
                <h4><?= e($order['product_name']) ?></h4>
                <div class="purchase-meta">
                  <span class="purchase-date"><?= date('d M Y',strtotime($order['created_at'])) ?></span>
                  <span class="purchase-amount">₹<?= number_format($order['amount'],2) ?></span>
                  <span class="purchase-status status-<?= $order['delivery_status']??'pending' ?>"><?= ucfirst($order['delivery_status']??'pending') ?></span>
                </div>
                <?php if (!empty($order['tracking_number'])): ?>
                  <div style="font-size:.75rem;color:var(--muted)">Tracking: <strong><?= e($order['tracking_number']) ?></strong></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="view-all-link"><a href="<?= APP_URL ?>/wallet.php">View all purchases →</a></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ════ KYC UPDATE ════ -->
  <?php if ($kycStatus === 'approved'): ?>
  <div class="dash-section-title" id="st7">🔄 Update KYC Information</div>
  <div class="card" id="kycUpdate">
    <div class="card-body">
      <p style="margin-bottom:1rem;color:var(--muted);">If your KYC information has changed, request an update — admin will review and approve.</p>
      <form method="POST" action="<?= APP_URL ?>/request-kyc-update.php" class="kyc-update-form" onsubmit="return submitKycUpdate(event)">
        <div class="form-group">
          <label class="form-label">Reason for Update</label>
          <textarea name="update_reason" class="form-control" rows="3" placeholder="Explain what needs to be updated and why..." required></textarea>
        </div>
        <button type="submit" class="idcard-btn idcard-btn-primary" style="width:auto;padding:.7rem 1.5rem;">📝 Request KYC Update</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<script>
function copyRef(){ _doCopy(document.getElementById('refInput').value,document.getElementById('copyBtn'),'✅ Copied!','📋 Copy'); }
function copyRef2(){ var el=document.getElementById('refInput'); if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(el.value).then(function(){alert('Link copied!');});}else{el.select();document.execCommand('copy');alert('Link copied!');} }
function _doCopy(text,btn,done,orig){ if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(text).then(function(){ btn.textContent=done;btn.classList.add('copied');setTimeout(function(){btn.textContent=orig;btn.classList.remove('copied');},2200);}); }else{document.getElementById('refInput').select();document.execCommand('copy');btn.textContent=done;setTimeout(function(){btn.textContent=orig;},2200);} }

function toggleNotifications(){ document.getElementById('notificationsDropdown').classList.toggle('show'); }
document.addEventListener('click',function(e){ var d=document.getElementById('notificationsDropdown'),b=document.querySelector('.notification-bell'); if(b&&!b.contains(e.target)&&d&&!d.contains(e.target)) d.classList.remove('show'); });
function markRead(id){ fetch('<?= APP_URL ?>/mark-notification-read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(function(){ location.reload(); }); }
function markAllRead(){ fetch('<?= APP_URL ?>/mark-all-notifications-read.php',{method:'POST'}).then(function(){ location.reload(); }); }
function viewNotification(id,link){ markRead(id); if(link) setTimeout(function(){ window.location.href=link; },300); }
function viewOrder(id){ window.location.href='<?= APP_URL ?>/order-details.php?id='+id; }
function markAnnouncementRead(id){ fetch('<?= APP_URL ?>/mark-announcement-read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(function(){ location.reload(); }); }
function submitKycUpdate(e){ e.preventDefault(); var reason=e.target.update_reason.value.trim(); if(!reason){alert('Please enter a reason.');return false;} fetch('<?= APP_URL ?>/request-kyc-update.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'reason='+encodeURIComponent(reason)}).then(function(r){return r.json();}).then(function(d){alert(d.message);if(d.success)e.target.reset();}); return false; }

/* Scroll reveal */
var revObs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(!e.isIntersecting)return;e.target.classList.add('visible');revObs.unobserve(e.target);});},{threshold:0.1});
['st1','st2','st3','st4','st5','st6','st7','bvBar','refCard','psbCard','clubCard','recentCard','idCard','announcements','purchases','kycUpdate','clubStatusCard'].forEach(function(id){var el=document.getElementById(id);if(el)revObs.observe(el);});

/* Club cards stagger */
var clubObs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(!e.isIntersecting)return;e.target.querySelectorAll('.club-card').forEach(function(c){setTimeout(function(){c.classList.add('visible');},parseInt(c.dataset.delay||0));});clubObs.unobserve(e.target);});},{threshold:0.1});
var cg=document.querySelector('.club-grid');if(cg)clubObs.observe(cg.parentElement);

/* Animated bars */
var barObs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(!e.isIntersecting)return;
  e.target.querySelectorAll('.member-bar-fill').forEach(function(el){el.style.width=el.getAttribute('data-target');});
  e.target.querySelectorAll('.dist-fill').forEach(function(el,i){setTimeout(function(){el.style.width=el.getAttribute('data-target');},i*90);});
  var rf=e.target.querySelector('#rankFill');if(rf)setTimeout(function(){rf.style.width=rf.getAttribute('data-target');},200);
  barObs.unobserve(e.target);
});},{threshold:0.15});
document.querySelectorAll('.card,.bv-bar').forEach(function(el){barObs.observe(el);});

/* Count-up */
function animateCount(el){ var target=parseFloat(el.dataset.target)||0,prefix=el.dataset.prefix||'',suffix=el.dataset.suffix||'',decimals=parseInt(el.dataset.decimals)||0,duration=1200,start=null;
  function step(ts){if(!start)start=ts;var prog=Math.min((ts-start)/duration,1),ease=1-Math.pow(1-prog,3),val=target*ease;
    var numStr=decimals>0?val.toFixed(decimals):Math.round(val).toLocaleString('en-IN');
    if(suffix==' BV'){el.innerHTML=prefix+numStr+' <span style="font-size:1.1rem;font-weight:700;color:var(--gold)">BV</span>';}
    else{el.textContent=prefix+numStr+suffix;}
    if(prog<1)requestAnimationFrame(step);
    else{var finalStr=decimals>0?target.toFixed(decimals):Math.round(target).toLocaleString('en-IN');
      if(suffix==' BV'){el.innerHTML=prefix+finalStr+' <span style="font-size:1.1rem;font-weight:700;color:var(--gold)">BV</span>';}
      else{el.textContent=prefix+finalStr+suffix;}
    }
  } requestAnimationFrame(step);
}
var countObs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(!e.isIntersecting)return;e.target.querySelectorAll('.count-up').forEach(animateCount);countObs.unobserve(e.target);});},{threshold:0.5});
var sg=document.querySelector('.stats-grid');if(sg)countObs.observe(sg);

/* Section title reveal */
var titleObs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');titleObs.unobserve(e.target);}});},{threshold:0.4});
document.querySelectorAll('.dash-section-title').forEach(function(el){titleObs.observe(el);});

/* 3D tilt on stat tiles */
document.querySelectorAll('.stat-tile').forEach(function(tile){
  tile.addEventListener('mousemove',function(e){var r=tile.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;tile.style.transform='perspective(500px) rotateY('+(x*8)+'deg) rotateX('+(-y*8)+'deg) translateY(-5px)';});
  tile.addEventListener('mouseleave',function(){tile.style.transform='';});
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>