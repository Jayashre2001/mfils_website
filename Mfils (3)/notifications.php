<?php
// notifications.php — Mfills Partner Notifications
$pageTitle = 'Notifications – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$userId = currentUserId();
$user   = getUser($userId);

// ── Mark single notification read (AJAX) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $nid = (int)$_POST['nid'];
    try {
        db()->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND (user_id=? OR user_id IS NULL)")
             ->execute([$nid, $userId]);
    } catch (Exception $e) {}
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// ── Mark all read (form submit) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    try {
        db()->prepare("UPDATE notifications SET is_read=1 WHERE (user_id=? OR user_id IS NULL) AND is_read=0")
             ->execute([$userId]);
    } catch (Exception $e) {}
    setFlash('success', 'All notifications marked as read.');
    header('Location: notifications.php');
    exit;
}

// ── Fetch notifications ───────────────────────────────────────
$notifications = [];
$unreadCount   = 0;
try {
    $stmt = db()->prepare("
        SELECT *,
               COALESCE(link_url, link) AS display_link,
               CASE WHEN user_id IS NULL THEN 'broadcast' ELSE 'personal' END AS scope
        FROM notifications
        WHERE (user_id = ? OR user_id IS NULL)
          AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY
          CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
          created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = db()->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE (user_id=? OR user_id IS NULL) AND is_read=0
          AND (expires_at IS NULL OR expires_at > NOW())
    ");
    $stmt2->execute([$userId]);
    $unreadCount = (int)$stmt2->fetchColumn();
} catch (Exception $e) {
    // Table may not exist yet — show empty state
}

// ── Type metadata ─────────────────────────────────────────────
$typeInfo = [
    'announcement' => [
        'label'  => 'Announcement',
        'color'  => '#e0aa40',
        'glow'   => 'rgba(200,146,42,.18)',
        'bg'     => 'rgba(200,146,42,.08)',
        'border' => 'rgba(200,146,42,.25)',
    ],
    'commission' => [
        'label'  => 'Commission',
        'color'  => '#4dac5e',
        'glow'   => 'rgba(77,172,94,.18)',
        'bg'     => 'rgba(77,172,94,.08)',
        'border' => 'rgba(77,172,94,.25)',
    ],
    'kyc' => [
        'label'  => 'KYC',
        'color'  => '#60a5fa',
        'glow'   => 'rgba(96,165,250,.18)',
        'bg'     => 'rgba(96,165,250,.08)',
        'border' => 'rgba(96,165,250,.25)',
    ],
    'system' => [
        'label'  => 'System',
        'color'  => '#94a3b8',
        'glow'   => 'rgba(148,163,184,.15)',
        'bg'     => 'rgba(148,163,184,.07)',
        'border' => 'rgba(148,163,184,.2)',
    ],
    'promo' => [
        'label'  => 'Promo',
        'color'  => '#f87171',
        'glow'   => 'rgba(248,113,113,.18)',
        'bg'     => 'rgba(248,113,113,.08)',
        'border' => 'rgba(248,113,113,.25)',
    ],
];

$priorityMeta = [
    'urgent' => ['label' => 'Urgent',   'color' => '#f87171', 'bg' => 'rgba(248,113,113,.12)'],
    'high'   => ['label' => 'Priority', 'color' => '#fbbf24', 'bg' => 'rgba(251,191,36,.12)'],
    'normal' => ['label' => '', 'color' => '', 'bg' => ''],
    'low'    => ['label' => '', 'color' => '', 'bg' => ''],
];

// ── SVG icon per type ─────────────────────────────────────────
function notifSvgIcon(string $type): string {
    $icons = [
        'announcement' => '<svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'commission'   => '<svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'kyc'          => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="8" cy="14" r=".5" fill="currentColor"/><circle cx="12" cy="14" r=".5" fill="currentColor"/><circle cx="16" cy="14" r=".5" fill="currentColor"/></svg>',
        'system'       => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07M8.46 8.46a5 5 0 0 0 0 7.07"/></svg>',
        'promo'        => '<svg viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
    ];
    return $icons[$type] ?? $icons['system'];
}

// ── Group by date ─────────────────────────────────────────────
function notifGroupByDate(array $items): array {
    $groups = [];
    foreach ($items as $item) {
        $date  = date('Y-m-d', strtotime($item['created_at']));
        $label = match($date) {
            date('Y-m-d')                      => 'Today',
            date('Y-m-d', strtotime('-1 day')) => 'Yesterday',
            default                            => date('d F Y', strtotime($item['created_at'])),
        };
        $groups[$label][] = $item;
    }
    return $groups;
}

// ── Time ago ──────────────────────────────────────────────────
function notifTimeAgo(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60)     return 'Just now';
    if ($d < 3600)   return (int)($d / 60) . 'm ago';
    if ($d < 86400)  return (int)($d / 3600) . 'h ago';
    if ($d < 604800) return (int)($d / 86400) . 'd ago';
    return date('d M Y', strtotime($dt));
}

$grouped    = notifGroupByDate($notifications);
$typeCounts = array_count_values(array_column($notifications, 'type'));
$urgentCount = array_reduce($notifications, fn($c, $n) => $c + ($n['priority'] === 'urgent' ? 1 : 0), 0);

include __DIR__ . '/includes/header.php';

// getFlash returns array {type, msg} — handle correctly
$flash = getFlash();
?>
<style>
/* ═══════════════════════════════════════════════════
   MFILLS NOTIFICATIONS — Dark Luxury Editorial
═══════════════════════════════════════════════════ */
:root{
  --g-dd:#060e09;--g-d:#0e2414;--g-m:#1a3b22;--g-b:#2a6336;
  --g-l:#3a8a4a;--g-ll:#4dac5e;
  --gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;--gold-ll:#f5c96a;
  --jade:#0F7B5C;--coral:#E8534A;
  --ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;
  --ink:#152018;--muted:#5a7a60;
}

/* ══ PAGE HEADER ══ */
.nh{
  background:linear-gradient(140deg,var(--g-dd) 0%,var(--g-d) 45%,var(--g-m) 100%);
  padding:3rem 0 4.5rem;position:relative;overflow:hidden;
  border-bottom:2px solid rgba(200,146,42,.3);
}
.nh::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background-image:
    radial-gradient(circle,rgba(200,146,42,.06) 1.5px,transparent 1.5px),
    radial-gradient(circle,rgba(77,172,94,.04) 1px,transparent 1px);
  background-size:28px 28px,14px 14px;
  background-position:0 0,14px 14px;
}
.nh-blob{position:absolute;border-radius:50%;pointer-events:none;filter:blur(80px);}
.nhb1{width:500px;height:300px;background:radial-gradient(ellipse,rgba(200,146,42,.12) 0%,transparent 70%);top:-80px;right:-100px;animation:blobDrift 18s ease-in-out infinite;}
.nhb2{width:350px;height:350px;background:radial-gradient(circle,rgba(77,172,94,.1) 0%,transparent 70%);bottom:-120px;left:-60px;animation:blobDrift 22s 5s ease-in-out infinite reverse;}
.nhb3{width:200px;height:200px;background:radial-gradient(circle,rgba(15,123,92,.08) 0%,transparent 70%);top:40%;left:35%;animation:blobDrift 14s 2s ease-in-out infinite;}
@keyframes blobDrift{
  0%,100%{transform:translate(0,0) scale(1);}
  33%{transform:translate(18px,-14px) scale(1.06);}
  66%{transform:translate(-12px,18px) scale(.95);}
}
.nh .container{position:relative;z-index:1;}
.nh-title{
  font-family:'Cinzel',serif;font-size:clamp(1.6rem,4vw,2.2rem);
  font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em;margin-bottom:.3rem;
  animation:fadeUp .5s ease both;
}
.nh-title span{
  background:linear-gradient(135deg,var(--gold-l),var(--gold-ll));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  font-style:italic;
}
.nh-sub{color:rgba(255,255,255,.38);font-size:.82rem;font-family:'Nunito',sans-serif;animation:fadeUp .5s .1s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
.nh-row{display:flex;align-items:flex-start;justify-content:space-between;gap:1.25rem;flex-wrap:wrap;}
.nh-meta{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:.9rem;animation:fadeUp .5s .2s ease both;}
.unread-widget{
  display:inline-flex;align-items:center;gap:.65rem;
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
  border-radius:14px;padding:.55rem 1rem;backdrop-filter:blur(4px);
}
.uw-num{font-family:'Cinzel',serif;font-size:1.5rem;font-weight:900;line-height:1;}
.uw-num.has-unread{color:var(--gold-l);}
.uw-num.all-read{color:rgba(77,172,94,.9);}
.uw-label{font-size:.72rem;color:rgba(255,255,255,.45);font-family:'Nunito',sans-serif;line-height:1.3;}
.uw-label strong{display:block;font-size:.8rem;color:rgba(255,255,255,.75);font-weight:700;}
.btn-mark-all{
  display:inline-flex;align-items:center;gap:.4rem;
  background:linear-gradient(135deg,rgba(200,146,42,.18),rgba(200,146,42,.1));
  color:var(--gold-l);border:1px solid rgba(200,146,42,.3);
  border-radius:50px;padding:.5rem 1.1rem;
  font-family:'Nunito',sans-serif;font-size:.78rem;font-weight:800;
  cursor:pointer;transition:all .22s;white-space:nowrap;
}
.btn-mark-all:hover{background:rgba(200,146,42,.28);border-color:rgba(200,146,42,.5);transform:translateY(-1px);}

/* ══ STATS STRIP ══ */
.stats-strip{
  display:flex;gap:1px;background:rgba(200,146,42,.15);
  border-radius:16px;overflow:hidden;margin-top:-2.5rem;
  position:relative;z-index:5;
  box-shadow:0 8px 32px rgba(0,0,0,.18);
  animation:fadeUp .5s .3s ease both;
}
.ss-item{
  flex:1;background:var(--g-m);padding:.85rem 1.25rem;
  display:flex;flex-direction:column;gap:.18rem;transition:background .2s;
}
.ss-item:first-child{border-radius:16px 0 0 16px;}
.ss-item:last-child{border-radius:0 16px 16px 0;}
.ss-item:hover{background:var(--g-b);}
.ss-num{font-family:'Cinzel',serif;font-size:1.35rem;font-weight:800;color:#fff;line-height:1;}
.ss-lbl{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.38);font-family:'Nunito',sans-serif;}
.ss-item.gold .ss-num{color:var(--gold-l);}
.ss-item.jade .ss-num{color:#6DDFB8;}
.ss-item.coral .ss-num{color:#fca5a5;}

/* ══ BODY ══ */
.nb{background:var(--ivory);padding:1.75rem 0 5rem;}
.notif-wrap{max-width:740px;margin:0 auto;padding:0 1.5rem;}

/* ══ FLASH ══ */
.flash-bar{
  border-radius:12px;padding:.75rem 1.1rem;font-size:.84rem;font-weight:700;
  margin-bottom:1.25rem;font-family:'Nunito',sans-serif;
  display:flex;align-items:center;gap:.6rem;
}
.flash-success{background:rgba(15,123,92,.1);color:var(--jade);border-left:3px solid var(--jade);}
.flash-danger{background:rgba(232,83,74,.08);color:#9A1A09;border-left:3px solid var(--coral);}

/* ══ FILTER TABS ══ */
.nf-bar{
  display:flex;gap:.45rem;flex-wrap:wrap;
  margin-bottom:1.75rem;padding-bottom:1.1rem;
  border-bottom:1.5px solid var(--ivory-dd);
}
.nf-tab{
  display:inline-flex;align-items:center;gap:.35rem;
  padding:.4rem .95rem;border-radius:50px;
  border:1.5px solid var(--ivory-dd);background:#fff;
  font-size:.72rem;font-weight:800;color:var(--muted);
  font-family:'Nunito',sans-serif;cursor:pointer;
  transition:all .2s cubic-bezier(.34,1.3,.64,1);white-space:nowrap;
}
.nf-tab:hover{border-color:var(--g-l);color:var(--g-m);transform:translateY(-1px);}
.nf-tab.active{background:var(--g-d);border-color:var(--g-d);color:#fff;box-shadow:0 4px 14px rgba(26,59,34,.25);}
.nf-tab-count{
  background:rgba(255,255,255,.2);border-radius:20px;padding:.06rem .38rem;font-size:.62rem;
}
.nf-tab:not(.active) .nf-tab-count{background:rgba(26,59,34,.07);color:var(--muted);}
.nf-tab svg{width:13px;height:13px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;}

/* ══ DATE SEPARATOR ══ */
.date-sep{display:flex;align-items:center;gap:.75rem;margin:1.5rem 0 .8rem;}
.date-sep-line{flex:1;height:1px;background:var(--ivory-dd);}
.date-sep-label{
  font-size:.65rem;font-weight:800;text-transform:uppercase;
  letter-spacing:.12em;color:var(--muted);
  background:var(--ivory-d);border-radius:20px;
  padding:.28rem .8rem;border:1px solid var(--ivory-dd);
  white-space:nowrap;font-family:'Nunito',sans-serif;
}

/* ══ NOTIFICATION CARD ══ */
.nc{
  background:#fff;border:1.5px solid var(--ivory-dd);border-radius:16px;
  padding:1.1rem 1.2rem 1rem;margin-bottom:.7rem;
  display:grid;grid-template-columns:52px 1fr auto;gap:.9rem;
  align-items:flex-start;cursor:pointer;
  transition:transform .25s cubic-bezier(.34,1.2,.64,1),box-shadow .25s,border-color .2s,background .2s;
  animation:ncIn .4s cubic-bezier(.34,1.2,.64,1) both;
  position:relative;overflow:hidden;
}
@keyframes ncIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
.nc::before{
  content:'';position:absolute;left:0;top:0;bottom:0;width:3.5px;
  border-radius:0 2px 2px 0;background:var(--nc-accent,var(--g-l));
  transform:scaleY(0);transform-origin:bottom;
  transition:transform .28s cubic-bezier(.34,1.3,.64,1);
}
.nc:hover{transform:translateX(4px) translateY(-2px);box-shadow:0 8px 28px rgba(26,59,34,.1);}
.nc:hover::before,.nc.is-unread::before{transform:scaleY(1);}
.nc.is-unread{background:rgba(26,59,34,.025);border-color:rgba(26,59,34,.1);}
.nc.pri-urgent{--nc-accent:#f87171;border-color:rgba(248,113,113,.2);background:rgba(248,113,113,.018);}
.nc.pri-high{--nc-accent:var(--gold);}

/* Icon */
.nc-icon-area{display:flex;flex-direction:column;align-items:center;gap:.35rem;}
.nc-icon-ring{
  width:44px;height:44px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  background:var(--nc-icon-bg,rgba(26,59,34,.07));
  border:1px solid var(--nc-icon-border,rgba(26,59,34,.12));
  transition:transform .3s cubic-bezier(.34,1.5,.64,1),box-shadow .3s;
  flex-shrink:0;position:relative;overflow:hidden;
}
.nc-icon-ring::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.12),transparent);pointer-events:none;}
.nc:hover .nc-icon-ring{transform:scale(1.12) rotate(-6deg);box-shadow:0 4px 14px var(--nc-glow,rgba(26,59,34,.18));}
.nc-icon-ring svg{width:20px;height:20px;stroke:var(--nc-color,var(--g-l));stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round;}
.nc-type-dot{width:6px;height:6px;border-radius:50%;background:var(--nc-color,var(--g-l));opacity:.6;}

/* Content */
.nc-body{min-width:0;}
.nc-head{display:flex;align-items:flex-start;gap:.45rem;margin-bottom:.3rem;flex-wrap:wrap;}
.nc-title{
  font-family:'Nunito',sans-serif;font-size:.9rem;font-weight:800;
  line-height:1.3;flex:1;min-width:0;
}
.nc.is-unread .nc-title{color:var(--g-d);}
.nc:not(.is-unread) .nc-title{color:rgba(21,32,24,.6);}
.nc-chip{
  display:inline-flex;align-items:center;
  font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;
  border-radius:20px;padding:.12rem .5rem;flex-shrink:0;font-family:'Cinzel',serif;
}
.nc-pri-chip{
  font-size:.6rem;font-weight:800;letter-spacing:.04em;
  border-radius:20px;padding:.12rem .5rem;flex-shrink:0;
  font-family:'Nunito',sans-serif;text-transform:uppercase;
}
.nc-broadcast{
  display:inline-flex;align-items:center;gap:.22rem;
  font-size:.58rem;font-weight:700;
  background:rgba(26,59,34,.07);color:var(--muted);border-radius:20px;padding:.1rem .42rem;
}
.nc-broadcast svg{width:9px;height:9px;stroke:currentColor;stroke-width:2.2;fill:none;stroke-linecap:round;}
.nc-message{
  font-size:.81rem;color:rgba(21,32,24,.55);line-height:1.62;margin-bottom:.55rem;
  font-family:'Nunito',sans-serif;
}
.nc.is-unread .nc-message{color:rgba(21,32,24,.72);}
.nc-footer{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;}
.nc-time{
  font-size:.68rem;color:rgba(90,122,96,.7);
  font-family:'Nunito',sans-serif;
  display:flex;align-items:center;gap:.25rem;
}
.nc-time svg{width:11px;height:11px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;}
.nc-link{
  font-size:.72rem;font-weight:800;color:var(--nc-color,var(--g-b));
  text-decoration:none;font-family:'Nunito',sans-serif;
  display:inline-flex;align-items:center;gap:.22rem;
  border-radius:20px;padding:.15rem .55rem;
  background:var(--nc-icon-bg,rgba(26,59,34,.06));
  transition:all .18s;
}
.nc-link:hover{filter:brightness(1.15);transform:translateX(2px);}
.nc-link svg{width:10px;height:10px;stroke:currentColor;stroke-width:2.5;fill:none;stroke-linecap:round;}
.nc-amount{
  font-family:'Cinzel',serif;font-size:.85rem;font-weight:700;color:var(--g-ll);
  background:rgba(77,172,94,.08);border-radius:20px;padding:.12rem .55rem;
}

/* Status side */
.nc-status{display:flex;flex-direction:column;align-items:center;gap:.4rem;padding-top:.15rem;}
.nc-unread-dot{
  width:9px;height:9px;border-radius:50%;background:var(--coral);flex-shrink:0;
  box-shadow:0 0 0 3px rgba(232,83,74,.18);
  animation:dotPulse 2.5s ease-in-out infinite;
}
@keyframes dotPulse{
  0%,100%{box-shadow:0 0 0 3px rgba(232,83,74,.18);}
  50%{box-shadow:0 0 0 7px rgba(232,83,74,.04);}
}
.nc-read-btn{
  width:28px;height:28px;border-radius:8px;
  background:rgba(26,59,34,.05);border:1px solid rgba(26,59,34,.1);
  color:rgba(26,59,34,.4);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:all .18s;flex-shrink:0;
}
.nc-read-btn:hover{background:rgba(15,123,92,.12);border-color:rgba(15,123,92,.3);color:#0F7B5C;}
.nc-read-btn svg{width:11px;height:11px;stroke:currentColor;stroke-width:2.8;fill:none;stroke-linecap:round;stroke-linejoin:round;}
.nc-read-check svg{width:13px;height:13px;stroke:rgba(26,59,34,.2);stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round;}

/* ══ EMPTY STATE ══ */
.notif-empty{
  text-align:center;padding:5rem 2.5rem;
  background:#fff;border-radius:20px;border:1.5px solid var(--ivory-dd);
  box-shadow:0 2px 12px rgba(26,59,34,.04);
}
.ei-ring{
  width:90px;height:90px;border-radius:50%;
  background:linear-gradient(135deg,var(--ivory-d),var(--ivory-dd));
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 1.25rem;border:2px solid var(--ivory-dd);
  animation:floatIcon 3.5s ease-in-out infinite;
}
@keyframes floatIcon{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.ei-ring svg{width:40px;height:40px;stroke:var(--muted);stroke-width:1.5;fill:none;stroke-linecap:round;stroke-linejoin:round;opacity:.4;}
.notif-empty h3{font-family:'DM Serif Display',serif;font-size:1.35rem;color:var(--g-d);margin-bottom:.4rem;}
.notif-empty p{font-size:.84rem;color:var(--muted);line-height:1.75;max-width:360px;margin:0 auto;}

/* DB error notice */
.db-notice{
  background:rgba(200,146,42,.06);border:1.5px solid rgba(200,146,42,.2);
  border-radius:14px;padding:1rem 1.25rem;margin-bottom:1.5rem;
  font-size:.82rem;color:#92400E;font-family:'Nunito',sans-serif;line-height:1.65;
}
.db-notice code{background:rgba(200,146,42,.12);padding:.1rem .4rem;border-radius:4px;font-size:.78rem;}

/* ══ SCROLL TOP ══ */
.scroll-top{
  position:fixed;bottom:2rem;right:2rem;z-index:100;
  width:44px;height:44px;border-radius:14px;
  background:linear-gradient(135deg,var(--g-d),var(--g-b));
  color:#fff;border:none;
  box-shadow:0 6px 20px rgba(26,59,34,.35);
  cursor:pointer;display:none;align-items:center;justify-content:center;
  transition:all .25s cubic-bezier(.34,1.3,.64,1);
}
.scroll-top.show{display:flex;}
.scroll-top:hover{transform:translateY(-3px) scale(1.06);}
.scroll-top svg{width:16px;height:16px;stroke:#fff;stroke-width:2.5;fill:none;stroke-linecap:round;stroke-linejoin:round;}

/* ══ RESPONSIVE ══ */
@media(max-width:680px){
  .nh{padding:2rem 0 3.5rem;}
  .nh-title{font-size:1.6rem;}
  .stats-strip{flex-wrap:wrap;gap:1px;}
  .ss-item{min-width:calc(50% - .5px);}
  .ss-item:nth-child(3){border-radius:0 0 0 16px;}
  .ss-item:nth-child(4){border-radius:0 0 16px 0;}
  .nc{grid-template-columns:44px 1fr auto;gap:.7rem;padding:.9rem 1rem .85rem;}
  .nc-icon-ring{width:40px;height:40px;border-radius:12px;}
}
@media(max-width:480px){
  .stats-strip{flex-direction:column;gap:1px;}
  .ss-item{border-radius:0!important;}
  .ss-item:first-child{border-radius:16px 16px 0 0!important;}
  .ss-item:last-child{border-radius:0 0 16px 16px!important;}
  .nc{grid-template-columns:40px 1fr auto;gap:.6rem;}
  .nc-icon-ring{width:36px;height:36px;}
  .nc-icon-ring svg{width:16px;height:16px;}
  .nf-bar{gap:.3rem;}
}
</style>

<!-- ════ HEADER ════ -->
<div class="nh">
  <div class="nhb1 nh-blob"></div>
  <div class="nhb2 nh-blob"></div>
  <div class="nhb3 nh-blob"></div>
  <div class="container">
    <div class="nh-row">
      <div>
        <div class="nh-title">Your <span>Notifications</span></div>
        <div class="nh-sub">Commission alerts · KYC updates · Company announcements</div>
        <div class="nh-meta">
          <div class="unread-widget">
            <div>
              <div class="uw-num <?= $unreadCount > 0 ? 'has-unread' : 'all-read' ?>">
                <?= $unreadCount > 0 ? $unreadCount : '✓' ?>
              </div>
            </div>
            <div class="uw-label">
              <strong><?= $unreadCount > 0 ? 'Unread' : 'All caught up' ?></strong>
              <?= $unreadCount > 0 ? 'notifications pending' : 'No new notifications' ?>
            </div>
          </div>
          <?php if ($unreadCount > 0): ?>
          <form method="POST" style="margin:0">
            <input type="hidden" name="mark_all_read" value="1">
            <button class="btn-mark-all" type="submit">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Mark all read
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ════ STATS STRIP ════ -->
<div class="container" style="position:relative;z-index:5">
  <div class="stats-strip">
    <div class="ss-item gold">
      <div class="ss-num"><?= count($notifications) ?></div>
      <div class="ss-lbl">Total</div>
    </div>
    <div class="ss-item">
      <div class="ss-num" id="ssUnread"><?= $unreadCount ?></div>
      <div class="ss-lbl">Unread</div>
    </div>
    <div class="ss-item jade">
      <div class="ss-num"><?= $typeCounts['commission'] ?? 0 ?></div>
      <div class="ss-lbl">Commissions</div>
    </div>
    <div class="ss-item coral">
      <div class="ss-num"><?= $urgentCount ?></div>
      <div class="ss-lbl">Urgent</div>
    </div>
  </div>
</div>

<!-- ════ BODY ════ -->
<div class="nb">
<div class="notif-wrap">

  <?php if ($flash): ?>
  <div class="flash-bar flash-<?= e($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✅' : '⚠️' ?> <?= e($flash['msg']) ?>
  </div>
  <?php endif; ?>

  <?php if (empty($notifications) && $unreadCount === 0): ?>
  <!-- DB table may not have data or table missing -->
  <div class="db-notice">
    💡 <strong>Notifications table needs setup.</strong>
    Run <code>notifications_schema_fix.sql</code> in phpMyAdmin on your
    <code>u302534731_Mlm_task</code> database to create the table and add sample data.
  </div>
  <?php endif; ?>

  <!-- ── Filter Tabs ── -->
  <div class="nf-bar" id="nfBar">
    <button class="nf-tab active" data-filter="all">
      All <span class="nf-tab-count"><?= count($notifications) ?></span>
    </button>
    <?php foreach ($typeInfo as $type => $ti):
      if (!isset($typeCounts[$type])) continue; ?>
    <button class="nf-tab" data-filter="<?= $type ?>">
      <?= notifSvgIcon($type) ?> <?= $ti['label'] ?>
      <span class="nf-tab-count"><?= $typeCounts[$type] ?></span>
    </button>
    <?php endforeach; ?>
    <?php if ($unreadCount > 0): ?>
    <button class="nf-tab" data-filter="unread">
      Unread <span class="nf-tab-count" id="tabUnreadCount"><?= $unreadCount ?></span>
    </button>
    <?php endif; ?>
  </div>

  <!-- ── Notifications List ── -->
  <?php if (empty($notifications)): ?>
  <div class="notif-empty">
    <div class="ei-ring">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    </div>
    <h3>All quiet here</h3>
    <p>You'll receive commission alerts, KYC status updates, company announcements and system notifications right here.</p>
  </div>

  <?php else: ?>
  <div id="notifList">
    <?php
    $cardIdx = 0;
    foreach ($grouped as $dateLabel => $items): ?>

    <div class="date-sep" data-group>
      <div class="date-sep-line"></div>
      <div class="date-sep-label"><?= e($dateLabel) ?></div>
      <div class="date-sep-line"></div>
    </div>

    <?php foreach ($items as $n):
      $ti       = $typeInfo[$n['type']] ?? $typeInfo['system'];
      $pm       = $priorityMeta[$n['priority'] ?? 'normal'] ?? $priorityMeta['normal'];
      $isUnread = !(bool)$n['is_read'];
      $priClass = in_array($n['priority'] ?? '', ['urgent','high']) ? 'pri-'.$n['priority'] : '';
      $delay    = min($cardIdx * 45, 400);
      $cardIdx++;

      /* Resolve display link — supports both link and link_url columns */
      $displayLink = $n['display_link'] ?? ($n['link_url'] ?? ($n['link'] ?? ''));
      $displayLinkText = $n['link_text'] ?? ($displayLink ? 'View Details' : '');

      /* Auto-detect commission amount from message */
      $commAmount = '';
      if ($n['type'] === 'commission' && preg_match('/₹[\d,\.]+/', $n['message'], $m)) {
          $commAmount = $m[0];
      }
    ?>
    <div class="nc <?= $isUnread ? 'is-unread' : '' ?> <?= $priClass ?>"
         data-type="<?= e($n['type']) ?>"
         data-read="<?= $n['is_read'] ? '1' : '0' ?>"
         data-id="<?= (int)$n['id'] ?>"
         id="notif-<?= (int)$n['id'] ?>"
         style="
           --nc-accent:<?= $ti['color'] ?>;
           --nc-color:<?= $ti['color'] ?>;
           --nc-icon-bg:<?= $ti['bg'] ?>;
           --nc-icon-border:<?= $ti['border'] ?>;
           --nc-glow:<?= $ti['glow'] ?>;
           animation-delay:<?= $delay ?>ms
         "
         onclick="markRead(<?= (int)$n['id'] ?>, this)">

      <!-- Icon -->
      <div class="nc-icon-area">
        <div class="nc-icon-ring"><?= notifSvgIcon($n['type']) ?></div>
        <div class="nc-type-dot"></div>
      </div>

      <!-- Content -->
      <div class="nc-body">
        <div class="nc-head">
          <span class="nc-title"><?= e($n['title']) ?></span>
          <span class="nc-chip" style="background:<?= $ti['bg'] ?>;color:<?= $ti['color'] ?>"><?= $ti['label'] ?></span>
          <?php if ($pm['label']): ?>
          <span class="nc-pri-chip" style="background:<?= $pm['bg'] ?>;color:<?= $pm['color'] ?>"><?= $pm['label'] ?></span>
          <?php endif; ?>
          <?php if (($n['scope'] ?? '') === 'broadcast'): ?>
          <span class="nc-broadcast">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            All Partners
          </span>
          <?php endif; ?>
        </div>

        <div class="nc-message"><?= nl2br(e($n['message'])) ?></div>

        <div class="nc-footer">
          <span class="nc-time">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?= notifTimeAgo($n['created_at']) ?>
          </span>
          <?php if ($displayLink && $displayLinkText): ?>
          <a href="<?= e(APP_URL . '/' . ltrim($displayLink, '/')) ?>" class="nc-link" onclick="event.stopPropagation()">
            <?= e($displayLinkText) ?>
            <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($commAmount): ?>
          <span class="nc-amount"><?= $commAmount ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Status -->
      <div class="nc-status">
        <?php if ($isUnread): ?>
          <div class="nc-unread-dot"></div>
          <button class="nc-read-btn" title="Mark as read"
            onclick="event.stopPropagation();markRead(<?= (int)$n['id'] ?>,document.getElementById('notif-<?= (int)$n['id'] ?>'))">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
        <?php else: ?>
          <span class="nc-read-check">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </span>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /.notif-wrap -->
</div><!-- /.nb -->

<button class="scroll-top" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})">
  <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script>
/* ══ MARK READ ══ */
function markRead(nid, card) {
  if (!card || card.dataset.read === '1') return;

  fetch('<?= APP_URL ?>/notifications.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'mark_read=1&nid='+nid
  })
  .then(function(r){ return r.json(); })
  .then(function(res){
    if (!res.success) return;
    card.classList.remove('is-unread');
    card.dataset.read = '1';
    var dot     = card.querySelector('.nc-unread-dot');
    var readBtn = card.querySelector('.nc-read-btn');
    var status  = card.querySelector('.nc-status');
    if (dot)     dot.remove();
    if (readBtn) readBtn.remove();
    if (status && !status.querySelector('.nc-read-check')) {
      status.innerHTML = '<span class="nc-read-check"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="rgba(26,59,34,.2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>';
    }
    updateUnreadCount(-1);
  })
  .catch(function(){});
}

function updateUnreadCount(delta) {
  var uw   = document.querySelector('.uw-num');
  var cur  = parseInt((uw && uw.textContent.trim()) || '0') || 0;
  var next = Math.max(0, cur + delta);
  if (uw) {
    if (next === 0) {
      uw.textContent = '✓';
      uw.className = 'uw-num all-read';
      var lbl = document.querySelector('.uw-label');
      if (lbl) lbl.innerHTML = '<strong>All caught up</strong>No new notifications';
      var mf = document.querySelector('form[style="margin:0"]');
      if (mf) mf.style.display = 'none';
    } else {
      uw.textContent = next;
    }
  }
  /* Stats strip */
  var ss = document.getElementById('ssUnread');
  if (ss) ss.textContent = next;
  /* Unread tab count */
  var tc = document.getElementById('tabUnreadCount');
  if (tc) tc.textContent = next;
}

/* ══ FILTER TABS ══ */
document.querySelectorAll('.nf-tab').forEach(function(tab) {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.nf-tab').forEach(function(t){ t.classList.remove('active'); });
    this.classList.add('active');
    var filter = this.dataset.filter;
    var cards  = document.querySelectorAll('.nc');
    var groups = document.querySelectorAll('[data-group]');

    cards.forEach(function(card, i) {
      var show =
        filter === 'all' ||
        card.dataset.type === filter ||
        (filter === 'unread' && card.dataset.read === '0');
      if (show) {
        card.style.display = '';
        card.style.animation = 'none';
        void card.offsetWidth;
        card.style.animationDelay = (Math.min(i, 12) * 35) + 'ms';
        card.style.animation = 'ncIn .35s cubic-bezier(.34,1.2,.64,1) both';
      } else {
        card.style.display = 'none';
      }
    });

    groups.forEach(function(grp) {
      var el = grp.nextElementSibling, hasVisible = false;
      while (el && !el.hasAttribute('data-group')) {
        if (el.classList.contains('nc') && el.style.display !== 'none') hasVisible = true;
        el = el.nextElementSibling;
      }
      grp.style.display = hasVisible ? '' : 'none';
    });
  });
});

/* ══ SCROLL TOP ══ */
window.addEventListener('scroll', function() {
  var btn = document.getElementById('scrollTopBtn');
  if (btn) btn.classList.toggle('show', window.scrollY > 400);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>