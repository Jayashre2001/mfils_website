<?php
// register.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';
startSession();
if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$refCode = trim($_GET['ref'] ?? trim($_POST['ref'] ?? ''));
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = $_POST['password']       ?? '';
    $confirm  = $_POST['confirm']        ?? '';
    $ref      = trim($_POST['ref']       ?? '');

    // ✅ Auto-generate username if empty
    // Name se banao: "Rahul Sharma" → "rahul_4821"
    if (empty($username) && !empty($fullName)) {
        $base     = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $fullName)[0]));
        $base     = substr($base ?: 'user', 0, 12);
        $username = $base . '_' . rand(1000, 9999);
    }
    // Email se fallback
    if (empty($username) && !empty($email)) {
        $base     = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
        $base     = substr($base ?: 'user', 0, 12);
        $username = $base . '_' . rand(1000, 9999);
    }
    // Last resort
    if (empty($username)) {
        $username = 'user_' . rand(10000, 99999);
    }

    if (!$fullName || !$username || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (strlen($fullName) < 3 || strlen($fullName) > 60) {
        $error = 'Full name must be 3–60 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'Username: 3–30 characters, letters/numbers/underscore only.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($username, $email, $password, $ref ?: null, $fullName);

        if ($result['success']) {
            try {
                $mbpin        = $result['mbpin']         ?? '';
                $referralCode = $result['referral_code'] ?? '';
                $referrerName = $result['referrer_name'] ?? '';
                if ($mbpin && $referralCode) {
                    $mailResult = sendWelcomeMail($email, $username, $mbpin, $referralCode, $referrerName);
                    if (!$mailResult['success']) {
                        error_log('Welcome mail failed: ' . $mailResult['message']);
                    }
                }
            } catch (\Throwable $e) {
                error_log('Welcome mail exception: ' . $e->getMessage());
            }

            setFlash('success', '🎉 Account created! Your MBPIN has been sent to your email.');
            header('Location: ' . APP_URL . '/login.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle  = 'Register – Mfills';
// ✅ POST-safe form action — no .php redirect issue
$formAction = '/Mfils/register.php' . ($refCode ? '?ref=' . urlencode($refCode) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Nunito:wght@300;400;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
:root{
  --green-dd:#0e2414;--green-d:#1a3b22;--green:#1d4a28;--green-m:#2a6336;
  --green-l:#3a8a4a;--green-ll:#4dac5e;
  --gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;--gold-ll:#f5c96a;
  --jade:#0F7B5C;--jade-l:#14A376;--jade-ll:#1CC48D;
  --coral:#E8534A;
  --ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;
  --ink:#152018;--muted:#5a7a60;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Nunito',sans-serif;background:var(--green-dd);min-height:100vh;
  display:flex;align-items:center;justify-content:center;padding:2rem 1rem;position:relative;overflow:hidden}
body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),
  linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);background-size:52px 52px}
body::after{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(200,146,42,.055) 1.5px,transparent 1.5px);background-size:26px 26px}
.bg-glow{position:fixed;border-radius:50%;pointer-events:none;z-index:0}
.bg-glow-1{width:640px;height:640px;background:radial-gradient(circle,rgba(58,138,74,.26) 0%,transparent 70%);top:-200px;left:-160px;animation:glowDrift 18s ease-in-out infinite}
.bg-glow-2{width:480px;height:480px;background:radial-gradient(circle,rgba(200,146,42,.15) 0%,transparent 70%);bottom:-120px;right:-100px;animation:glowDrift 22s 4s ease-in-out infinite reverse}
.bg-glow-3{width:300px;height:300px;background:radial-gradient(circle,rgba(15,123,92,.12) 0%,transparent 70%);top:38%;left:38%;animation:glowDrift 15s 2s ease-in-out infinite}
.bg-glow-4{width:220px;height:220px;background:radial-gradient(circle,rgba(200,146,42,.1) 0%,transparent 70%);top:10%;right:10%;animation:glowDrift 12s 6s ease-in-out infinite reverse}
@keyframes glowDrift{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(24px,-20px) scale(1.05)}66%{transform:translate(-18px,26px) scale(.96)}}
.particle{position:fixed;border-radius:50%;pointer-events:none;z-index:0;background:rgba(200,146,42,.28);animation:particleDrift linear infinite}
@keyframes particleDrift{0%{transform:translateY(0) scale(1);opacity:.5}50%{transform:translateY(-70px) scale(1.2);opacity:.2}100%{transform:translateY(-140px) scale(.7);opacity:0}}
.page-wrap{position:relative;z-index:1;display:flex;align-items:stretch;width:100%;max-width:1040px;
  border-radius:22px;overflow:hidden;box-shadow:0 48px 120px rgba(0,0,0,.55),0 0 0 1px rgba(255,255,255,.05);
  animation:cardEntry .7s cubic-bezier(.34,1.2,.64,1) both}
@keyframes cardEntry{from{opacity:0;transform:translateY(36px) scale(.97)}to{opacity:1;transform:none}}
.left-panel{background:linear-gradient(160deg,var(--green-m) 0%,var(--green-d) 50%,var(--green-dd) 100%);
  flex:0 0 300px;padding:3rem 2rem;display:flex;flex-direction:column;justify-content:space-between;
  position:relative;overflow:hidden;border-right:1px solid rgba(200,146,42,.15)}
.left-panel::after{content:'';position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(255,255,255,.025) 1px,transparent 1px);background-size:22px 22px}
.lp-brand{position:relative;z-index:1;display:flex;align-items:center;gap:10px}
.lp-logo-img{height:44px;width:auto;filter:brightness(1.1) drop-shadow(0 2px 6px rgba(0,0,0,.4));animation:logoFloat 4s ease-in-out infinite}
@keyframes logoFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
.lp-brand-text{display:flex;flex-direction:column;gap:1px;line-height:1}
.lp-brand-name{font-family:'Cinzel',serif;font-size:1.2rem;font-weight:900;color:var(--ivory);letter-spacing:.04em}
.lp-brand-sub{font-size:.58rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase;color:rgba(200,146,42,.65)}
.left-perks{position:relative;z-index:1;list-style:none;display:flex;flex-direction:column;gap:.8rem}
.left-perks li{display:flex;align-items:flex-start;gap:.7rem;color:rgba(255,255,255,.75);font-size:.82rem;
  font-weight:500;line-height:1.5;opacity:0;transform:translateX(-18px);transition:opacity .5s ease,transform .5s ease}
.left-perks li.visible{opacity:1;transform:translateX(0)}
.perk-icon{width:32px;height:32px;flex-shrink:0;border-radius:9px;background:rgba(255,255,255,.08);
  border:1px solid rgba(200,146,42,.2);display:flex;align-items:center;justify-content:center;font-size:.95rem;
  transition:background .2s,border-color .2s,transform .25s}
.left-perks li:hover .perk-icon{background:rgba(200,146,42,.18);border-color:rgba(200,146,42,.4);transform:scale(1.15) rotate(-5deg)}
.left-perks li strong{color:var(--gold-l)}
.lp-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(200,146,42,.4),transparent);position:relative;z-index:1}
.left-commission{position:relative;z-index:1}
.lc-label{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.32);margin-bottom:.6rem;font-family:'Cinzel',serif}
.lc-bars{display:flex;gap:4px;align-items:flex-end;height:44px}
.lc-bar-wrap{flex:1}
.lc-bar{width:100%;height:44px;border-radius:4px 4px 0 0;background:rgba(255,255,255,.08);position:relative;overflow:hidden}
.lc-bar-fill{position:absolute;bottom:0;left:0;right:0;height:0;border-radius:4px 4px 0 0;transition:height 1.4s cubic-bezier(.34,1.56,.64,1)}
.lc-bar-fill.c1{background:linear-gradient(to top,var(--gold-d),var(--gold-l))}
.lc-bar-fill.c2{background:linear-gradient(to top,var(--gold-d),var(--gold))}
.lc-bar-fill.c3{background:linear-gradient(to top,var(--jade),var(--jade-l))}
.lc-bar-fill.c4{background:linear-gradient(to top,var(--jade-l),var(--jade-ll))}
.lc-bar-fill.c5,.lc-bar-fill.c6,.lc-bar-fill.c7{background:linear-gradient(to top,var(--green-m),var(--green-l))}
.lc-bar-tag{font-size:.5rem;color:rgba(255,255,255,.28);text-align:center;margin-top:.3rem;font-weight:700;font-family:'Cinzel',serif}
.right-panel{flex:1;background:var(--ivory);padding:2.2rem 2.4rem;overflow-y:auto;max-height:96vh}
.right-panel::-webkit-scrollbar{width:4px}
.right-panel::-webkit-scrollbar-track{background:var(--ivory-d)}
.right-panel::-webkit-scrollbar-thumb{background:var(--ivory-dd);border-radius:10px}
.rp-heading{font-family:'DM Serif Display',serif;font-size:1.8rem;color:var(--green-d);line-height:1.15;margin-bottom:.25rem;letter-spacing:-.02em}
.rp-heading em{color:var(--gold-d);font-style:italic}
.rp-sub{font-size:.88rem;color:var(--muted);margin-bottom:1.3rem;line-height:1.6}
.alert{padding:.82rem 1rem;border-radius:10px;font-size:.875rem;font-weight:600;margin-bottom:1.1rem;display:flex;align-items:flex-start;gap:.5rem;animation:alertIn .4s ease both}
@keyframes alertIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
.alert-danger{background:rgba(232,83,74,.09);color:#B91C1C;border:1px solid rgba(232,83,74,.25)}
.info-notice{background:rgba(200,146,42,.08);border:1px solid rgba(200,146,42,.25);border-radius:10px;padding:.65rem .95rem;font-size:.77rem;color:var(--gold-d);font-weight:600;margin-bottom:1.1rem;display:flex;align-items:center;gap:.5rem}
.age-notice{display:flex;align-items:center;gap:.5rem;background:rgba(26,59,34,.06);border:1px solid rgba(26,59,34,.12);border-radius:8px;padding:.5rem .85rem;font-size:.75rem;color:var(--green-d);font-weight:600;margin-bottom:.9rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.name-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.form-group{margin-bottom:.95rem}
.form-label{display:block;font-size:.67rem;font-weight:800;color:var(--green-d);margin-bottom:.38rem;text-transform:uppercase;letter-spacing:.1em;font-family:'Cinzel',serif}
.form-label .opt{color:var(--muted);font-weight:500;text-transform:none;letter-spacing:0;font-family:'Nunito',sans-serif}
.form-control{width:100%;padding:.7rem .92rem;border:1.5px solid var(--ivory-dd);border-radius:10px;background:#fff;font-family:'Nunito',sans-serif;font-size:.92rem;color:var(--ink);transition:border-color .2s,box-shadow .2s,transform .15s;outline:none}
.form-control:focus{border-color:var(--green-l);box-shadow:0 0 0 3px rgba(26,59,34,.1);transform:translateY(-1px)}
.form-control::placeholder{color:#a8b8a0}
.form-control[readonly]{background:var(--ivory-d);color:var(--muted);cursor:not-allowed}
.form-hint{font-size:.73rem;color:var(--muted);margin-top:.28rem;line-height:1.5}
.username-wrap{position:relative}
.username-wrap .form-control{padding-right:2.8rem}
.auto-badge{position:absolute;right:.65rem;top:50%;transform:translateY(-50%);background:linear-gradient(135deg,var(--gold),var(--gold-l));color:var(--green-dd);border-radius:6px;font-size:.52rem;font-weight:900;padding:.2rem .5rem;letter-spacing:.08em;font-family:'Cinzel',serif;opacity:0;transition:opacity .3s;pointer-events:none;white-space:nowrap}
.auto-badge.show{opacity:1}
@keyframes autoFillPulse{0%{box-shadow:0 0 0 0 rgba(200,146,42,.5)}70%{box-shadow:0 0 0 10px rgba(200,146,42,0)}100%{box-shadow:0 0 0 0 rgba(200,146,42,0)}}
.form-control.auto-filled{border-color:var(--gold);animation:autoFillPulse .6s ease}
.pw-wrap{position:relative}
.pw-wrap .form-control{padding-right:2.5rem}
.pw-eye{position:absolute;right:.8rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:.88rem;color:var(--muted);padding:.2rem;transition:color .2s,transform .2s;line-height:1}
.pw-eye:hover{color:var(--green-d);transform:translateY(-50%) scale(1.2)}
.strength-track{height:5px;border-radius:5px;background:var(--ivory-dd);margin-top:.45rem;overflow:hidden}
.strength-bar{height:100%;width:0%;border-radius:5px;transition:all .35s}
.ref-badge{display:inline-flex;align-items:center;gap:.4rem;background:rgba(15,123,92,.1);border:1px solid rgba(15,123,92,.25);color:var(--jade);border-radius:20px;font-size:.7rem;font-weight:700;padding:.25rem .8rem;margin-bottom:.45rem;animation:badgePop .5s cubic-bezier(.34,1.56,.64,1) both}
@keyframes badgePop{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.btn-submit{width:100%;padding:.88rem;background:linear-gradient(135deg,var(--gold),var(--gold-l));color:var(--green-dd);font-family:'Cinzel',serif;font-size:.98rem;font-weight:700;border:none;border-radius:50px;cursor:pointer;box-shadow:0 6px 24px rgba(200,146,42,.4);transition:background .25s,transform .25s,box-shadow .25s;margin-top:.25rem;display:flex;align-items:center;justify-content:center;gap:.5rem;position:relative;overflow:hidden;animation:pulseGold 2.6s ease-in-out 1.8s infinite}
@keyframes pulseGold{0%{box-shadow:0 0 0 0 rgba(200,146,42,.5)}70%{box-shadow:0 0 0 14px rgba(200,146,42,0)}100%{box-shadow:0 0 0 0 rgba(200,146,42,0)}}
.btn-submit::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.18),transparent);opacity:0;transition:opacity .25s}
.btn-submit:hover{background:linear-gradient(135deg,var(--gold-l),var(--gold-ll));transform:translateY(-2px);box-shadow:0 10px 30px rgba(200,146,42,.5);animation:none}
.btn-submit:hover::before{opacity:1}
.btn-submit:active{transform:translateY(0)}
.btn-submit .spinner{display:none;width:16px;height:16px;border:2px solid rgba(14,36,20,.3);border-top-color:var(--green-dd);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.btn-submit.loading .spinner{display:block}
.btn-submit.loading .btn-text{opacity:.5}
.divider{text-align:center;margin:1.2rem 0;position:relative}
.divider::before{content:'';position:absolute;top:50%;left:0;right:0;height:1px;background:var(--ivory-dd)}
.divider span{position:relative;background:var(--ivory);padding:0 .75rem;font-size:.7rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.12em}
.btn-login-link{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.78rem;background:transparent;border:1.5px solid var(--green-m);color:var(--green-d);font-family:'Cinzel',serif;font-size:.86rem;font-weight:700;border-radius:50px;text-decoration:none;transition:all .25s;position:relative;overflow:hidden}
.btn-login-link::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--green-d),var(--green-m));opacity:0;transition:opacity .25s}
.btn-login-link:hover{color:#fff;transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,59,34,.3)}
.btn-login-link:hover::before{opacity:1}
.btn-login-link span{position:relative;z-index:1}
.form-group,.btn-submit,.divider,.btn-login-link{opacity:0;transform:translateY(12px);transition:opacity .5s ease,transform .5s ease}
@media(max-width:760px){
  .page-wrap{flex-direction:column;max-width:490px;border-radius:18px}
  .left-panel{flex:none;padding:1.75rem}
  .left-perks,.left-commission,.lp-divider{display:none}
  .lp-brand{justify-content:center}
  .right-panel{max-height:none;padding:1.75rem 1.4rem}
  .form-row,.name-row{grid-template-columns:1fr}
}
@media(max-width:400px){.right-panel{padding:1.5rem 1.1rem}.rp-heading{font-size:1.55rem}}
</style>
</head>
<body>
<div class="bg-glow bg-glow-1"></div>
<div class="bg-glow bg-glow-2"></div>
<div class="bg-glow bg-glow-3"></div>
<div class="bg-glow bg-glow-4"></div>

<div class="page-wrap">
  <div class="left-panel">
    <div class="lp-brand">
      <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" class="lp-logo-img">
      <div class="lp-brand-text">
        <span class="lp-brand-name">MFILLS</span>
        <span class="lp-brand-sub">Partner Portal</span>
      </div>
    </div>
    <ul class="left-perks" id="perksList">
      <li><span class="perk-icon">🆔</span><span><strong>MBPIN</strong> — Unique partner ID sent to your email</span></li>
      <li><span class="perk-icon">📊</span><span>Access to <strong>Advanced Partner Dashboard</strong></span></li>
      <li><span class="perk-icon">🪪</span><span>Download <strong>ID Card</strong> &amp; update KYC</span></li>
      <li><span class="perk-icon">🛒</span><span>Full access to <strong>MShop</strong></span></li>
      <li><span class="perk-icon">🎁</span><span><strong>100% Free</strong> — Open to 18+ individuals</span></li>
    </ul>
    <div class="lp-divider"></div>
    <div class="left-commission">
      <div class="lc-label">Partner Sales Bonus by Level</div>
      <div class="lc-bars">
        <?php foreach([['h'=>100,'cls'=>'c1'],['h'=>53,'cls'=>'c2'],['h'=>40,'cls'=>'c3'],['h'=>27,'cls'=>'c4'],['h'=>20,'cls'=>'c5'],['h'=>13,'cls'=>'c6'],['h'=>13,'cls'=>'c7']] as $b): ?>
        <div class="lc-bar-wrap"><div class="lc-bar"><div class="lc-bar-fill <?= $b['cls'] ?>" data-pct="<?= $b['h'] ?>"></div></div></div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;gap:4px;margin-top:.3rem">
        <?php foreach(range(1,7) as $l): ?><div class="lc-bar-tag" style="flex:1">L<?= $l ?></div><?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="right-panel" id="rightPanel">

    <h1 class="rp-heading">Become a <em>Free</em> MBP Today</h1>
    <p class="rp-sub">Register free and get instant access to your Partner Dashboard, MBPIN, ID Card &amp; MShop.</p>
    <div class="age-notice">🔞 Registration open to individuals <strong>18 years or older</strong> only.</div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <div class="info-notice">📧 Your <strong>MBPIN</strong> will be emailed immediately after registration.</div>

    <form method="POST" action="<?= e($formAction) ?>" id="regForm">
      <input type="hidden" name="ref" value="<?= e($refCode) ?>">

      <div class="name-row">
        <div class="form-group">
          <label class="form-label" for="fullNameField">Full Name</label>
          <input type="text" name="full_name" id="fullNameField" class="form-control"
            placeholder="e.g. Rahul Sharma" required maxlength="60"
            value="<?= e($_POST['full_name'] ?? '') ?>" autocomplete="name"
            oninput="autoUsername(this.value)">
          <p class="form-hint">Your legal name (for ID Card &amp; KYC)</p>
        </div>
        <div class="form-group">
          <label class="form-label" for="usernameField">
            Username
            <span style="color:var(--gold-d);font-size:.58rem;font-family:'Nunito',sans-serif;font-weight:700;text-transform:none;letter-spacing:0"> ✦ auto</span>
          </label>
          <div class="username-wrap">
            <input type="text" name="username" id="usernameField" class="form-control"
              placeholder="Auto se banega…" maxlength="30"
              value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username"
              oninput="onUsernameEdit()">
            <span class="auto-badge" id="autoBadge">AUTO</span>
          </div>
          <p class="form-hint" id="usernameHint">Name type karo — username khud banega</p>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="emailField">Email Address</label>
        <input type="email" name="email" id="emailField" class="form-control"
          placeholder="you@email.com" required
          value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email">
        <p class="form-hint">Your MBPIN will be sent to this email.</p>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="pwdField">Password</label>
          <div class="pw-wrap">
            <input type="password" name="password" id="pwdField" class="form-control"
              placeholder="Min. 6 characters" required oninput="checkStrength(this.value)">
            <button type="button" class="pw-eye" onclick="togglePw('pwdField','eye1')" id="eye1">👁</button>
          </div>
          <div class="strength-track"><div class="strength-bar" id="strengthBar"></div></div>
          <p class="form-hint" id="strengthText"></p>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirmField">Confirm Password</label>
          <div class="pw-wrap">
            <input type="password" name="confirm" id="confirmField" class="form-control"
              placeholder="Repeat password" required oninput="checkMatch()">
            <button type="button" class="pw-eye" onclick="togglePw('confirmField','eye2')" id="eye2">👁</button>
          </div>
          <p class="form-hint" id="matchText"></p>
        </div>
      </div>

      <?php if ($refCode): ?>
      <div class="form-group">
        <label class="form-label">Referred By</label>
        <div class="ref-badge">✅ Referral code applied: <strong><?= e($refCode) ?></strong></div>
        <input type="text" class="form-control" value="<?= e($refCode) ?>" readonly>
        <p class="form-hint">Sponsor's referral code automatically applied.</p>
      </div>
      <?php else: ?>
      <div class="form-group">
        <label class="form-label">Referral Code <span class="opt">(optional)</span></label>
        <input type="text" name="ref" class="form-control"
          placeholder="Enter sponsor's code" value="<?= e($_POST['ref'] ?? '') ?>">
        <p class="form-hint">No code? You can still join free.</p>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span class="btn-text">🚀 Create My Free MBP Account</span>
        <div class="spinner"></div>
      </button>
    </form>

    <div class="divider"><span>Already a partner?</span></div>
    <a href="<?= APP_URL ?>/login.php" class="btn-login-link">
      <span>🔑 Already an MBP? Login Here</span>
    </a>
    <p style="text-align:center;font-size:.67rem;color:var(--muted);margin-top:1rem;line-height:1.6">
      By registering you confirm you are 18+ and agree to our Terms &amp; Privacy Policy.
    </p>

  </div>
</div>

<script>
(function(){
  for(let i=0;i<12;i++){
    const p=document.createElement('div');p.className='particle';
    const s=Math.random()*5+3;
    p.style.cssText=`width:${s}px;height:${s}px;left:${Math.random()*100}%;top:${Math.random()*100}%;animation-duration:${Math.random()*7+7}s;animation-delay:${Math.random()*6}s;opacity:${Math.random()*.3+.1}`;
    document.body.appendChild(p);
  }
})();

document.querySelectorAll('#perksList li').forEach((el,i)=>setTimeout(()=>el.classList.add('visible'),500+i*130));
window.addEventListener('load',()=>{
  document.querySelectorAll('.lc-bar-fill').forEach((el,i)=>setTimeout(()=>{el.style.height=el.dataset.pct+'%'},600+i*110));
});
document.querySelectorAll('#rightPanel .form-group,#regForm .btn-submit,#rightPanel .divider,#rightPanel .btn-login-link').forEach((el,i)=>{
  setTimeout(()=>{el.style.opacity='1';el.style.transform='translateY(0)'},350+i*65);
});

/* ── Auto Username Generator ──
   "Rahul Sharma" → "rahul_4821"
   User edit kar sakta hai baad mein
*/
let userEdited = false;
let lastRand   = Math.floor(1000 + Math.random() * 9000);

function makeUsername(name) {
  const first = name.trim().split(/\s+/)[0] || '';
  const clean = first.toLowerCase().replace(/[^a-z0-9]/g, '');
  if (clean.length < 2) return '';
  return clean.substring(0, 12) + '_' + lastRand;
}

function autoUsername(nameVal) {
  if (userEdited) return;
  const un = makeUsername(nameVal);
  const el = document.getElementById('usernameField');
  const badge = document.getElementById('autoBadge');
  const hint  = document.getElementById('usernameHint');
  if (un) {
    el.value = un;
    el.classList.add('auto-filled');
    badge.classList.add('show');
    hint.textContent = '✦ Auto-generated — click to edit';
    hint.style.color = 'var(--gold-d)';
    hint.style.fontWeight = '700';
    setTimeout(() => el.classList.remove('auto-filled'), 700);
  } else {
    el.value = '';
    badge.classList.remove('show');
    hint.textContent = 'Name type karo — username khud banega';
    hint.style.color = '';
  }
}

function onUsernameEdit() {
  userEdited = true;
  document.getElementById('autoBadge').classList.remove('show');
  const hint = document.getElementById('usernameHint');
  hint.textContent = 'Edit freely — make it unique';
  hint.style.color = '';
  hint.style.fontWeight = '';
}

/* ── On submit: ensure username filled ── */
document.getElementById('regForm').addEventListener('submit', function() {
  const un = document.getElementById('usernameField');
  if (!un.value.trim()) {
    const name = document.getElementById('fullNameField').value;
    const gen  = makeUsername(name);
    un.value = gen || 'user_' + Math.floor(10000 + Math.random() * 90000);
  }
  document.getElementById('submitBtn').classList.add('loading');
});

function togglePw(id,bid){
  const i=document.getElementById(id),b=document.getElementById(bid),s=i.type==='password';
  i.type=s?'text':'password';b.textContent=s?'🙈':'👁';
  b.style.transform='translateY(-50%) scale(1.3)';setTimeout(()=>{b.style.transform='translateY(-50%)'},200);
}

function checkStrength(v){
  const bar=document.getElementById('strengthBar'),txt=document.getElementById('strengthText');
  let s=0;
  if(v.length>=6)s++;if(v.length>=10)s++;
  if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^a-zA-Z0-9]/.test(v))s++;
  const c=['#E8534A','#c8922a','#e0aa40','#14A376','#0F7B5C'],l=['Very Weak','Weak','Fair','Strong','Very Strong'];
  bar.style.width=(s*20)+'%';bar.style.background=c[s-1]||'transparent';
  txt.textContent=v.length?(l[s-1]||''):'';txt.style.color=c[s-1]||'#888';txt.style.fontWeight='700';
  checkMatch();
}

function checkMatch(){
  const p=document.getElementById('pwdField').value,c=document.getElementById('confirmField').value,t=document.getElementById('matchText');
  if(!c){t.textContent='';return;}
  if(p===c){t.textContent='✅ Passwords match';t.style.color='#0F7B5C';t.style.fontWeight='700';}
  else{t.textContent='❌ Do not match';t.style.color='#E8534A';t.style.fontWeight='700';}
}

document.querySelectorAll('.form-control:not([readonly])').forEach(inp=>{
  inp.addEventListener('focus',()=>{inp.parentElement.style.transform='translateY(-1px)'});
  inp.addEventListener('blur',()=>{inp.parentElement.style.transform=''});
});

const sb=document.getElementById('submitBtn');
sb.addEventListener('mousemove',e=>{
  const r=sb.getBoundingClientRect(),x=e.clientX-r.left-r.width/2,y=e.clientY-r.top-r.height/2;
  sb.style.transform=`translate(${x*.15}px,${y*.15}px) translateY(-2px)`;
});
sb.addEventListener('mouseleave',()=>{sb.style.transform=''});
</script>
</body>
</html>