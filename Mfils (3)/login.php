<?php
// login.php
require_once __DIR__ . '/includes/functions.php';
startSession();
if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }
$flash = getFlash();
$error = '';
$fpMsg = ''; $fpError = ''; $fpSuccess = false; $fpResetLink = '';

/* ── Forgot Password Handler ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $fpIdentifier = trim($_POST['fp_identifier'] ?? '');
    if (!$fpIdentifier) {
        $fpError = 'Please enter your username or registered email.';
    } else {
        try {
            /* Load mailer if not already loaded */
            $mailerFile = __DIR__ . '/includes/mailer.php';
            if (file_exists($mailerFile)) require_once $mailerFile;

            $db   = db();

            /* Ensure reset columns exist — silently add if missing */
            try {
                $db->exec("ALTER TABLE users
                    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL DEFAULT NULL,
                    ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL DEFAULT NULL");
            } catch (Exception $colEx) { /* columns already exist — ignore */ }

            $stmt = $db->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$fpIdentifier, $fpIdentifier]);
            $fpUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fpUser && !empty($fpUser['email'])) {
                /* Generate secure token */
                $token     = bin2hex(random_bytes(32));
                $expires   = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $resetLink = APP_URL . '/reset-password.php?token=' . $token;

                /* Save token to DB */
                $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?")
                   ->execute([$token, $expires, $fpUser['id']]);

                /* Build HTML email */
                $uname   = htmlspecialchars($fpUser['username'], ENT_QUOTES);
                $appUrl  = APP_URL;
                $year    = date('Y');
                $logoUrl = APP_URL . '/includes/images/logo2.png';

                $emailHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Your Mfills Password</title></head>
<body style="margin:0;padding:0;background:#0e2414;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0e2414;padding:32px 16px">
<tr><td align="center">
<table width="100%" style="max-width:540px;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5)">

  <!-- HEADER -->
  <tr>
    <td style="background:linear-gradient(135deg,#1a3b22 0%,#2a6336 100%);padding:32px 40px;text-align:center;border-bottom:3px solid #c8922a">
      <img src="{$logoUrl}" alt="Mfills" height="44" style="display:block;margin:0 auto 12px">
      <div style="font-family:Georgia,serif;font-size:1.3rem;font-weight:900;color:#fff;letter-spacing:.04em">MFILLS</div>
      <div style="font-size:.58rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(200,146,42,.65);margin-top:2px">Business Partner Portal</div>
    </td>
  </tr>

  <!-- GOLD BAND -->
  <tr>
    <td style="background:#c8922a;padding:12px 40px;text-align:center">
      <span style="font-size:.95rem;font-weight:800;color:#0e2414;letter-spacing:.04em">🔐 Password Reset Request</span>
    </td>
  </tr>

  <!-- BODY -->
  <tr>
    <td style="background:#f8f5ef;padding:36px 40px">

      <p style="font-size:.88rem;color:#152018;line-height:1.75;margin:0 0 18px">
        Hello <strong style="color:#1a3b22">{$uname}</strong>,
      </p>
      <p style="font-size:.88rem;color:#152018;line-height:1.75;margin:0 0 24px">
        We received a request to reset the password for your Mfills Business Partner account.
        Click the button below to set a new password. This link is valid for <strong>1 hour</strong>.
      </p>

      <!-- RESET BUTTON -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px">
        <tr>
          <td align="center">
            <a href="{$resetLink}"
               style="display:inline-block;background:linear-gradient(135deg,#1a3b22,#2a6336);
                      color:#fff;text-decoration:none;border-radius:50px;
                      padding:14px 40px;font-weight:800;font-size:.95rem;
                      letter-spacing:.03em;
                      box-shadow:0 6px 22px rgba(26,59,34,.4)">
              🔑 Reset My Password
            </a>
          </td>
        </tr>
      </table>

      <!-- LINK FALLBACK -->
      <div style="background:#fff;border:1.5px solid #ddd5c4;border-radius:10px;padding:14px 18px;margin-bottom:20px">
        <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#5a7a60;margin-bottom:6px">
          Or copy this link into your browser:
        </div>
        <div style="font-size:.75rem;color:#1a3b22;word-break:break-all;font-family:'Courier New',monospace;line-height:1.6">
          {$resetLink}
        </div>
      </div>

      <!-- NOTICE -->
      <div style="background:rgba(232,83,74,.06);border:1px solid rgba(232,83,74,.18);border-radius:8px;padding:12px 16px;margin-bottom:8px">
        <p style="font-size:.8rem;color:#7f1d1d;margin:0;line-height:1.6">
          ⚠️ If you did not request a password reset, please ignore this email.
          Your account remains secure and no changes have been made.
        </p>
      </div>

      <p style="font-size:.72rem;color:#5a7a60;margin:16px 0 0;line-height:1.6">
        This link will expire in <strong>1 hour</strong> from the time of this email.
        After that you will need to request a new reset link.
      </p>

    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#0e2414;padding:20px 40px;text-align:center;border-top:1px solid rgba(200,146,42,.1)">
      <p style="font-size:.7rem;color:rgba(255,255,255,.25);margin:0 0 5px;line-height:1.6">
        This email was sent because a password reset was requested for your Mfills account.<br>
        If you have questions, contact our support team.
      </p>
      <p style="font-size:.65rem;color:rgba(200,146,42,.35);margin:0">
        &copy; {$year} Mfills &middot; All rights reserved &middot;
        <a href="{$appUrl}" style="color:rgba(200,146,42,.5);text-decoration:none">{$appUrl}</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

                /* Send via mailer.php's mfillsSendMail() */
                if (function_exists('mfillsSendMail')) {
                    $mailResult = mfillsSendMail(
                        $fpUser['email'],
                        $fpUser['username'],
                        'Mfills — Password Reset Request',
                        $emailHtml
                    );
                    if ($mailResult['success']) {
                        $fpSuccess = true;
                        $fpMsg = 'Password reset link sent to <strong>' .
                                 htmlspecialchars(substr_replace($fpUser['email'], '****', 3, strpos($fpUser['email'],'@')-3)) .
                                 '</strong>. Please check your inbox and spam folder.';
                    } else {
                        /* Mail failed — log it but still show link so user is not stuck */
                        error_log('Mfills reset mail failed: ' . $mailResult['message']);
                        $fpSuccess = true;
                        $fpMsg = 'Email could not be delivered automatically. Please use this link to reset your password (valid 1 hour):';
                        $fpResetLink = $resetLink; /* shown in success screen */
                    }
                } else {
                    /* mailer.php not found — show link directly */
                    $fpSuccess   = true;
                    $fpMsg       = 'Use this link to reset your password (valid 1 hour):';
                    $fpResetLink = $resetLink;
                }

            } elseif ($fpUser && empty($fpUser['email'])) {
                $fpError = 'No email address is registered on this account. Please contact support.';
            } else {
                /* Security: do not reveal if account exists */
                $fpSuccess = true;
                $fpMsg = 'If an account exists with that username or email, a reset link has been sent. Please check your inbox.';
            }

        } catch (Exception $ex) {
            error_log('Mfills forgot password error: ' . $ex->getMessage());
            $fpError = 'Something went wrong. Please try again in a few minutes.';
        }
    }
}

/* ── Login Handler ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';
    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/dashboard.php'); exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login – Mfills';
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
/* ═══════════════════════════════════════════
   MFILLS LOGIN — Forest Green + Gold Brand
   ═══════════════════════════════════════════ */
:root {
  --green-dd:  #0e2414;
  --green-d:   #1a3b22;
  --green:     #1d4a28;
  --green-m:   #2a6336;
  --green-l:   #3a8a4a;
  --green-ll:  #4dac5e;
  --gold:      #c8922a;
  --gold-l:    #e0aa40;
  --gold-d:    #a0721a;
  --gold-ll:   #f5c96a;
  --jade:      #0F7B5C;
  --jade-l:    #14A376;
  --jade-ll:   #1CC48D;
  --coral:     #E8534A;
  --ivory:     #f8f5ef;
  --ivory-d:   #ede8de;
  --ivory-dd:  #ddd5c4;
  --ink:       #152018;
  --muted:     #5a7a60;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Nunito', sans-serif;
  background: var(--green-dd);
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 2rem 1rem;
  position: relative; overflow: hidden;
}

/* ── Background grid ── */
body::before {
  content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image:
    linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
  background-size: 52px 52px;
}
body::after {
  content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image: radial-gradient(circle, rgba(200,146,42,.06) 1.5px, transparent 1.5px);
  background-size: 26px 26px;
}

/* ── Glows ── */
.bg-glow { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; }
.bg-glow-1 {
  width: 640px; height: 640px;
  background: radial-gradient(circle, rgba(58,138,74,.25) 0%, transparent 70%);
  top: -200px; right: -160px;
  animation: gdrift 18s ease-in-out infinite;
}
.bg-glow-2 {
  width: 440px; height: 440px;
  background: radial-gradient(circle, rgba(200,146,42,.14) 0%, transparent 70%);
  bottom: -110px; left: -90px;
  animation: gdrift 22s 5s ease-in-out infinite reverse;
}
.bg-glow-3 {
  width: 280px; height: 280px;
  background: radial-gradient(circle, rgba(28,196,141,.1) 0%, transparent 70%);
  top: 44%; right: 6%;
  animation: gdrift 14s 2s ease-in-out infinite;
}
@keyframes gdrift {
  0%,100% { transform: translate(0,0) scale(1); }
  33%     { transform: translate(22px,-18px) scale(1.06); }
  66%     { transform: translate(-16px,22px) scale(.96); }
}

/* ── Floating particles ── */
.particle {
  position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
  background: rgba(200,146,42,.3);
  animation: particleDrift linear infinite;
}
@keyframes particleDrift {
  0%   { transform: translateY(0) scale(1);    opacity: .5; }
  50%  { transform: translateY(-70px) scale(1.2); opacity: .2; }
  100% { transform: translateY(-140px) scale(.7); opacity: 0; }
}

/* ══════════════════════════════════════
   PAGE CARD
══════════════════════════════════════ */
.page-wrap {
  position: relative; z-index: 1;
  display: flex; align-items: stretch;
  width: 100%; max-width: 920px;
  border-radius: 22px; overflow: hidden;
  box-shadow: 0 48px 120px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.05);
  animation: cardEntry .7s cubic-bezier(.34,1.2,.64,1) both;
}
@keyframes cardEntry {
  from { opacity: 0; transform: translateY(36px) scale(.97); }
  to   { opacity: 1; transform: none; }
}

/* ══════════════════════════════════════
   LEFT PANEL
══════════════════════════════════════ */
.left-panel {
  background: linear-gradient(160deg, var(--green-m) 0%, var(--green-d) 50%, var(--green-dd) 100%);
  flex: 0 0 320px;
  padding: 3.25rem 2.25rem;
  display: flex; flex-direction: column; justify-content: space-between;
  position: relative; overflow: hidden;
  border-right: 1px solid rgba(200,146,42,.15);
}
.left-panel::after {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(255,255,255,.025) 1px, transparent 1px);
  background-size: 22px 22px; pointer-events: none;
}

/* Brand / Logo */
.lp-brand { position: relative; z-index: 1; display: flex; align-items: center; gap: 10px; }
.lp-logo-img {
  height: 48px; width: auto;
  filter: brightness(1.1) drop-shadow(0 2px 6px rgba(0,0,0,.4));
  animation: logoFloat 4s ease-in-out infinite;
}
@keyframes logoFloat {
  0%,100% { transform: translateY(0); }
  50%     { transform: translateY(-5px); }
}
.lp-brand-text { display: flex; flex-direction: column; gap: 1px; line-height: 1; }
.lp-brand-name { font-family: 'Cinzel',serif; font-size: 1.3rem; font-weight: 900; color: var(--ivory); letter-spacing: .04em; }
.lp-brand-sub  { font-size: .6rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: rgba(200,146,42,.65); }

/* Welcome text */
.lp-welcome { position: relative; z-index: 1; }
.lp-welcome h2 { font-family: 'DM Serif Display',serif; font-size: 1.55rem; color: #fff; line-height: 1.25; margin-bottom: .6rem; }
.lp-welcome h2 em { color: var(--gold-l); font-style: italic; }
.lp-welcome p { font-size: .82rem; color: rgba(255,255,255,.42); line-height: 1.75; }

/* Gold divider */
.lp-divider { height: 1px; background: linear-gradient(90deg,transparent,rgba(200,146,42,.4),transparent); position: relative; z-index: 1; }

/* Stats */
.lp-stats { position: relative; z-index: 1; display: flex; flex-direction: column; gap: .6rem; }
.lp-stat {
  display: flex; align-items: center; gap: .75rem;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
  border-radius: 10px; padding: .7rem .9rem;
  transition: background .2s, border-color .2s, transform .2s; cursor: default;
}
.lp-stat:hover { background: rgba(200,146,42,.12); border-color: rgba(200,146,42,.3); transform: translateX(4px); }
.lp-stat-icon  { font-size: 1.1rem; flex-shrink: 0; }
.lp-stat-text  { font-size: .76rem; color: rgba(255,255,255,.65); line-height: 1.4; }
.lp-stat-text strong { color: #fff; }
.lp-stat-badge {
  margin-left: auto; flex-shrink: 0;
  background: rgba(200,146,42,.18); border: 1px solid rgba(200,146,42,.3);
  color: var(--gold-l); border-radius: 6px;
  font-size: .6rem; font-weight: 800; padding: .2rem .55rem;
  font-family: 'Cinzel',serif; letter-spacing: .05em;
}
.lp-stat-badge.jade  { background: rgba(28,196,141,.12); border-color: rgba(28,196,141,.22); color: var(--jade-ll); }
.lp-stat-badge.green { background: rgba(58,138,74,.15);  border-color: rgba(58,138,74,.25);  color: var(--green-ll); }

/* ══════════════════════════════════════
   RIGHT PANEL
══════════════════════════════════════ */
.right-panel {
  flex: 1; background: var(--ivory);
  padding: 3.25rem 2.75rem;
  display: flex; flex-direction: column; justify-content: center;
}

.rp-heading { font-family: 'DM Serif Display',serif; font-size: 1.9rem; color: var(--green-d); line-height: 1.15; margin-bottom: .3rem; letter-spacing: -.02em; }
.rp-heading em { color: var(--gold-d); font-style: italic; }
.rp-sub { font-size: .9rem; color: var(--muted); margin-bottom: 2rem; line-height: 1.6; }

/* Alerts */
.login-alert {
  padding: .85rem 1rem; border-radius: 10px;
  font-size: .875rem; font-weight: 600; margin-bottom: 1.5rem;
  display: flex; align-items: flex-start; gap: .5rem;
  animation: alertIn .4s ease both;
}
@keyframes alertIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
.login-alert.danger  { background: rgba(220,38,38,.08);  color: #B91C1C; border: 1px solid rgba(220,38,38,.2); }
.login-alert.success { background: rgba(15,123,92,.08);  color: var(--jade); border: 1px solid rgba(15,123,92,.2); }
.login-alert.info    { background: rgba(26,59,34,.08);   color: var(--green-d); border: 1px solid rgba(26,59,34,.15); }

/* Form */
.form-group { margin-bottom: 1.15rem; }
.form-label { display: block; font-size: .7rem; font-weight: 800; color: var(--green-d); margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .1em; font-family: 'Cinzel',serif; }
.form-control {
  width: 100%; padding: .78rem 1rem;
  border: 1.5px solid var(--ivory-dd); border-radius: 10px;
  background: #fff; font-family: 'Nunito',sans-serif;
  font-size: .95rem; color: var(--ink);
  transition: border-color .2s, box-shadow .2s, transform .15s; outline: none;
}
.form-control:focus { border-color: var(--green-l); box-shadow: 0 0 0 3px rgba(26,59,34,.1); transform: translateY(-1px); }
.form-control::placeholder { color: #a8b8a0; }
.form-hint { font-size: .74rem; color: var(--muted); margin-top: .3rem; }

/* Password wrap */
.pw-wrap { position: relative; }
.pw-wrap .form-control { padding-right: 2.8rem; }
.pw-eye {
  position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  font-size: .9rem; color: var(--muted); padding: .2rem; transition: color .2s, transform .2s;
}
.pw-eye:hover { color: var(--green-d); transform: translateY(-50%) scale(1.2); }

/* Forgot password link */
.forgot-row { display: flex; justify-content: flex-end; margin-top: .35rem; }
.forgot-link {
  font-size: .72rem; color: var(--gold-d); font-weight: 700;
  text-decoration: none; font-family: 'Cinzel',serif;
  background: none; border: none; cursor: pointer; padding: 0;
  transition: color .2s;
}
.forgot-link:hover { color: var(--green-d); text-decoration: underline; }

/* Submit button */
.btn-submit {
  width: 100%; padding: .9rem;
  background: linear-gradient(135deg, var(--green-d), var(--green-m));
  color: #fff; font-family: 'Cinzel',serif; font-size: .95rem; font-weight: 700;
  border: none; border-radius: 10px; cursor: pointer;
  box-shadow: 0 6px 22px rgba(26,59,34,.35);
  transition: all .25s; margin-top: .5rem;
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  position: relative; overflow: hidden;
}
.btn-submit::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.12),transparent); opacity:0; transition:opacity .25s; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(26,59,34,.45); }
.btn-submit:hover::before { opacity: 1; }
.btn-submit:active { transform: translateY(0); }
.btn-submit .spinner { display:none; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.btn-submit.loading .spinner { display: block; }
.btn-submit.loading .btn-text { display: none; }
@keyframes pulseRing {
  0%  { box-shadow: 0 0 0 0 rgba(26,59,34,.5); }
  70% { box-shadow: 0 0 0 14px rgba(26,59,34,0); }
  100%{ box-shadow: 0 0 0 0 rgba(26,59,34,0); }
}
.btn-submit { animation: pulseRing 2.8s ease-in-out 1.5s infinite; }
.btn-submit:hover { animation: none; }

/* Divider */
.divider { text-align:center; margin:1.5rem 0; position:relative; }
.divider::before { content:''; position:absolute; top:50%; left:0; right:0; height:1px; background:var(--ivory-dd); }
.divider span { position:relative; background:var(--ivory); padding:0 .75rem; font-size:.7rem; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.12em; }

/* Register link */
.btn-register {
  display:flex; align-items:center; justify-content:center; gap:.5rem;
  width:100%; padding:.82rem; background:transparent;
  border:1.5px solid var(--gold); color:var(--gold-d);
  font-family:'Cinzel',serif; font-size:.88rem; font-weight:700;
  border-radius:10px; text-decoration:none;
  transition:all .25s; position:relative; overflow:hidden;
}
.btn-register::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,var(--gold),var(--gold-l)); opacity:0; transition:opacity .25s; }
.btn-register:hover { color:var(--green-dd); border-color:var(--gold-l); transform:translateY(-1px); box-shadow:0 6px 20px rgba(200,146,42,.3); }
.btn-register:hover::before { opacity:1; }
.btn-register span { position:relative; z-index:1; }

/* Footer note */
.rp-footer { text-align:center; font-size:.68rem; color:var(--muted); margin-top:1.5rem; line-height:1.6; }

/* ══════════════════════════════════════
   FORGOT PASSWORD MODAL
══════════════════════════════════════ */
.fp-backdrop {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(6,15,8,.72); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem;
  opacity: 0; pointer-events: none;
  transition: opacity .3s;
}
.fp-backdrop.open { opacity: 1; pointer-events: all; }

.fp-modal {
  background: var(--ivory); border-radius: 20px;
  width: 100%; max-width: 440px;
  box-shadow: 0 40px 100px rgba(0,0,0,.5), 0 0 0 1px rgba(200,146,42,.15);
  transform: translateY(24px) scale(.96);
  transition: transform .38s cubic-bezier(.34,1.2,.64,1);
  overflow: hidden;
}
.fp-backdrop.open .fp-modal { transform: translateY(0) scale(1); }

/* Modal header */
.fp-header {
  background: linear-gradient(135deg, var(--green-d), var(--green-dd));
  padding: 1.6rem 1.75rem 1.35rem;
  position: relative;
  border-bottom: 2px solid rgba(200,146,42,.22);
}
.fp-header::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle,rgba(200,146,42,.08) 1.5px,transparent 1.5px);
  background-size: 20px 20px; pointer-events: none;
}
.fp-close {
  position: absolute; top: 1rem; right: 1rem;
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.7); width: 30px; height: 30px; border-radius: 8px;
  cursor: pointer; font-size: .9rem;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s; z-index: 1;
}
.fp-close:hover { background: rgba(232,83,74,.25); border-color: rgba(232,83,74,.4); color: #fff; transform: rotate(90deg); }
.fp-icon { font-size: 2rem; margin-bottom: .55rem; position: relative; z-index: 1; }
.fp-title { font-family: 'Cinzel',serif; font-size: 1.1rem; font-weight: 900; color: #fff; margin-bottom: .2rem; position: relative; z-index: 1; }
.fp-subtitle { font-size: .78rem; color: rgba(255,255,255,.45); position: relative; z-index: 1; }

/* Modal body */
.fp-body { padding: 1.6rem 1.75rem 1.75rem; }

.fp-steps {
  display: flex; gap: .75rem; margin-bottom: 1.35rem;
}
.fp-step {
  display: flex; align-items: center; gap: .4rem;
  font-size: .68rem; color: var(--muted); font-weight: 600;
}
.fp-step-dot {
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--ivory-dd); color: var(--muted);
  display: flex; align-items: center; justify-content: center;
  font-size: .6rem; font-weight: 800; flex-shrink: 0;
}
.fp-step.active .fp-step-dot { background: var(--green-d); color: #fff; }
.fp-step.done   .fp-step-dot { background: var(--jade);    color: #fff; }
.fp-step-arrow { color: var(--ivory-dd); font-size: .7rem; }

/* Alerts inside modal */
.fp-alert {
  padding: .8rem .95rem; border-radius: 10px;
  font-size: .82rem; font-weight: 600; margin-bottom: 1.1rem;
  display: flex; align-items: flex-start; gap: .45rem;
  animation: alertIn .35s ease both;
}
.fp-alert.danger  { background: rgba(220,38,38,.08);  color: #B91C1C; border: 1px solid rgba(220,38,38,.2); }
.fp-alert.success { background: rgba(15,123,92,.08);  color: var(--jade); border: 1px solid rgba(15,123,92,.2); }

/* Input in modal */
.fp-input-wrap { margin-bottom: 1.1rem; }
.fp-label { display: block; font-size: .68rem; font-weight: 800; color: var(--green-d); margin-bottom: .38rem; text-transform: uppercase; letter-spacing: .1em; font-family: 'Cinzel',serif; }
.fp-input {
  width: 100%; padding: .75rem 1rem;
  border: 1.5px solid var(--ivory-dd); border-radius: 10px;
  background: #fff; font-family: 'Nunito',sans-serif;
  font-size: .9rem; color: var(--ink); outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.fp-input:focus { border-color: var(--green-l); box-shadow: 0 0 0 3px rgba(26,59,34,.1); }
.fp-input::placeholder { color: #a8b8a0; }
.fp-input-hint { font-size: .72rem; color: var(--muted); margin-top: .3rem; line-height: 1.5; }

/* Submit button in modal */
.fp-submit {
  width: 100%; padding: .82rem; margin-bottom: .65rem;
  background: linear-gradient(135deg, var(--green-d), var(--green-m));
  color: #fff; font-family: 'Cinzel',serif; font-size: .88rem; font-weight: 700;
  border: none; border-radius: 10px; cursor: pointer;
  transition: all .25s;
  display: flex; align-items: center; justify-content: center; gap: .45rem;
  box-shadow: 0 4px 16px rgba(26,59,34,.3);
}
.fp-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(26,59,34,.4); }
.fp-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.fp-submit .fp-spinner { display:none; width:14px; height:14px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
.fp-submit.loading .fp-spinner { display:block; }
.fp-submit.loading .fp-btn-txt { display:none; }

.fp-cancel {
  width: 100%; padding: .72rem;
  background: transparent; border: 1.5px solid var(--ivory-dd); border-radius: 10px;
  color: var(--muted); font-size: .82rem; font-weight: 700; font-family: 'Cinzel',serif;
  cursor: pointer; transition: all .2s;
}
.fp-cancel:hover { border-color: var(--green-l); color: var(--green-d); }

/* Success screen inside modal */
.fp-success-screen { text-align: center; padding: .5rem 0; }
.fp-success-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: linear-gradient(135deg, var(--jade), var(--jade-l));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; margin: 0 auto 1rem;
  animation: popIn .5s cubic-bezier(.34,1.6,.64,1) both;
}
@keyframes popIn { from{transform:scale(0)} to{transform:scale(1)} }
.fp-success-title { font-family: 'Cinzel',serif; font-size: 1rem; font-weight: 900; color: var(--green-d); margin-bottom: .4rem; }
.fp-success-text  { font-size: .82rem; color: var(--muted); line-height: 1.7; margin-bottom: 1.25rem; }
.fp-success-note  {
  background: rgba(15,123,92,.08); border: 1px solid rgba(15,123,92,.2);
  border-radius: 10px; padding: .75rem .9rem;
  font-size: .78rem; color: var(--jade); font-weight: 600; line-height: 1.6;
  margin-bottom: 1.1rem; text-align: left;
}
.fp-back-login {
  display: flex; align-items: center; justify-content: center; gap: .4rem;
  width: 100%; padding: .8rem;
  background: linear-gradient(135deg, var(--gold-d), var(--gold-l));
  color: var(--green-dd); font-family: 'Cinzel',serif;
  font-size: .88rem; font-weight: 800; border: none; border-radius: 10px;
  cursor: pointer; transition: all .22s;
  box-shadow: 0 4px 16px rgba(200,146,42,.3);
}
.fp-back-login:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(200,146,42,.4); }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 740px) {
  .page-wrap { flex-direction: column; max-width: 460px; border-radius: 18px; }
  .left-panel { flex: none; padding: 2rem 1.75rem; }
  .lp-welcome, .lp-stats, .lp-divider { display: none; }
  .lp-brand { justify-content: center; }
  .right-panel { padding: 2rem 1.5rem; }
}
@media (max-width: 400px) {
  .right-panel { padding: 1.75rem 1.25rem; }
  .rp-heading { font-size: 1.6rem; }
  .fp-modal { border-radius: 16px; }
  .fp-body  { padding: 1.25rem; }
  .fp-header { padding: 1.35rem 1.25rem 1.1rem; }
}
</style>
</head>
<body>

<!-- Background glows -->
<div class="bg-glow bg-glow-1"></div>
<div class="bg-glow bg-glow-2"></div>
<div class="bg-glow bg-glow-3"></div>

<!-- ══════════════════════════════════════
     FORGOT PASSWORD MODAL
══════════════════════════════════════ -->
<div class="fp-backdrop" id="fpBackdrop" onclick="if(event.target===this)closeFP()">
  <div class="fp-modal" id="fpModal">

    <!-- Header -->
    <div class="fp-header">
      <button class="fp-close" onclick="closeFP()" title="Close">✕</button>
      <div class="fp-icon">🔑</div>
      <div class="fp-title">Reset Your Password</div>
      <div class="fp-subtitle">We'll send a reset link to your registered email</div>
    </div>

    <!-- Body -->
    <div class="fp-body" id="fpBody">

      <?php if ($fpSuccess): ?>
      <!-- ── Server-side success (form was submitted) ── -->
      <div class="fp-success-screen">
        <div class="fp-success-icon">✓</div>
        <?php if (!empty($fpResetLink)): ?>
          <div class="fp-success-title">Reset Link Ready</div>
          <div class="fp-success-text"><?= $fpMsg ?></div>
          <div class="fp-success-note" style="text-align:left">
            <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--jade);margin-bottom:.45rem">Your Reset Link (valid 1 hour):</div>
            <div id="fpLinkTxt" style="font-size:.72rem;word-break:break-all;color:var(--green-d);font-family:'Courier New',monospace;line-height:1.6;margin-bottom:.6rem"><?= e($fpResetLink) ?></div>
            <button onclick="copyResetLink()"
                    id="fpCopyBtn"
                    style="background:var(--green-d);color:#fff;border:none;border-radius:6px;padding:.35rem .85rem;font-size:.72rem;font-weight:700;cursor:pointer;font-family:'Cinzel',serif">
              📋 Copy Link
            </button>
          </div>
          <a href="<?= e($fpResetLink) ?>" class="fp-back-login" style="text-decoration:none;display:flex;margin-top:.5rem">🔑 Open Reset Page</a>
        <?php else: ?>
          <div class="fp-success-title">Reset Link Sent!</div>
          <div class="fp-success-text"><?= $fpMsg ?></div>
          <div class="fp-success-note">
            📧 Check your inbox and spam folder.<br>
            ⏱ The link expires in <strong>1 hour</strong>.<br>
            🔁 If not received, try again after 5 minutes.
          </div>
          <button class="fp-back-login" onclick="closeFP()">← Back to Login</button>
        <?php endif; ?>
      </div>

      <?php elseif ($fpError): ?>
      <!-- ── Server-side error ── -->
      <?php /* Show modal open on page load */ ?>
      <div class="fp-steps">
        <div class="fp-step active"><div class="fp-step-dot">1</div> Identify</div>
        <div class="fp-step-arrow">›</div>
        <div class="fp-step"><div class="fp-step-dot">2</div> Email</div>
        <div class="fp-step-arrow">›</div>
        <div class="fp-step"><div class="fp-step-dot">3</div> Reset</div>
      </div>
      <div class="fp-alert danger">⚠️ <?= e($fpError) ?></div>
      <?php echo fpFormHtml(); ?>

      <?php else: ?>
      <!-- ── Default: fresh form ── -->
      <div class="fp-steps">
        <div class="fp-step active"><div class="fp-step-dot">1</div> Identify</div>
        <div class="fp-step-arrow">›</div>
        <div class="fp-step"><div class="fp-step-dot">2</div> Email</div>
        <div class="fp-step-arrow">›</div>
        <div class="fp-step"><div class="fp-step-dot">3</div> Reset</div>
      </div>
      <?php echo fpFormHtml(); ?>

      <?php endif; ?>

    </div><!-- /fp-body -->
  </div>
</div>

<?php
function fpFormHtml(): string {
    $v = htmlspecialchars($_POST['fp_identifier'] ?? '', ENT_QUOTES);
    return '
    <form method="POST" action="" id="fpForm">
      <input type="hidden" name="forgot_password" value="1">
      <div class="fp-input-wrap">
        <label class="fp-label" for="fp_identifier">Username or Email</label>
        <input type="text" name="fp_identifier" id="fp_identifier"
               class="fp-input"
               placeholder="Enter your username or email"
               value="' . $v . '"
               required autocomplete="username email">
        <p class="fp-input-hint">Enter the username or email you registered with. We\'ll send a secure reset link.</p>
      </div>
      <button type="submit" class="fp-submit" id="fpSubmit">
        <span class="fp-btn-txt">📧 Send Reset Link</span>
        <div class="fp-spinner"></div>
      </button>
      <button type="button" class="fp-cancel" onclick="closeFP()">Cancel — Back to Login</button>
    </form>';
}
?>

<div class="page-wrap">

  <!-- ══════════════════════════
       LEFT PANEL
  ══════════════════════════ -->
  <div class="left-panel">
    <div class="lp-brand">
      <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" class="lp-logo-img">
      <div class="lp-brand-text">
        <span class="lp-brand-name">MFILLS</span>
        <span class="lp-brand-sub">Partner Portal</span>
      </div>
    </div>

    <div class="lp-welcome">
      <h2>Welcome Back,<br><em>Business Partner.</em></h2>
      <p>Access your Mfills dashboard — track your BV, PSB earnings, network growth, and club rank all in one place.</p>
    </div>

    <div class="lp-divider"></div>

    <div class="lp-stats">
      <div class="lp-stat">
        <span class="lp-stat-icon">💰</span>
        <div class="lp-stat-text">Partner Sales Bonus<br><strong>7-Level PSB</strong></div>
        <span class="lp-stat-badge">40% BV</span>
      </div>
      <div class="lp-stat">
        <span class="lp-stat-icon">🛒</span>
        <div class="lp-stat-text">Business Club Activation<br><strong>2500 BV</strong> purchase</div>
        <span class="lp-stat-badge jade">MShop</span>
      </div>
      <div class="lp-stat">
        <span class="lp-stat-icon">👑</span>
        <div class="lp-stat-text">Leadership Reward Pool<br><strong>15%</strong> of company sales</div>
        <span class="lp-stat-badge green">4 Clubs</span>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════
       RIGHT PANEL
  ══════════════════════════ -->
  <div class="right-panel">

    <h1 class="rp-heading">Partner <em>Login</em></h1>
    <p class="rp-sub">Enter your credentials to access your Mfills Business Partner dashboard.</p>

    <?php if ($error): ?>
      <div class="login-alert danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($flash && !$fpSuccess && !$fpError): ?>
      <div class="login-alert <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate id="loginForm">
      <input type="hidden" name="login" value="1">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input type="text" name="username" id="username" class="form-control"
               placeholder="Your Mfills username"
               required autocomplete="username"
               value="<?= e($_POST['username'] ?? '') ?>">
        <p class="form-hint">Enter the username you registered with</p>
      </div>

      <div class="form-group">
        <label class="form-label" for="pwField">Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwField" class="form-control"
                 placeholder="Your password"
                 required autocomplete="current-password">
          <button type="button" class="pw-eye" onclick="togglePw()" id="pwEye" title="Show/hide password">👁</button>
        </div>
        <!-- ── Forgot password link ── -->
        <div class="forgot-row">
          <button type="button" class="forgot-link" onclick="openFP()">Forgot password?</button>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span class="btn-text">🔑 Login to My Dashboard</span>
        <div class="spinner"></div>
      </button>
    </form>

    <div class="divider"><span>New to Mfills?</span></div>

    <a href="<?= APP_URL ?>/register.php" class="btn-register">
      <span>🚀 Register Free — Get Your MBPIN</span>
    </a>

    <p class="rp-footer">
      Mfills Business Partner Portal &nbsp;·&nbsp; Direct Selling Platform
    </p>

  </div>
</div>

<script>
/* ── Copy reset link (fallback mode) ── */
function copyResetLink() {
  var el = document.getElementById('fpLinkTxt');
  var btn = document.getElementById('fpCopyBtn');
  if (!el || !btn) return;
  var txt = el.textContent.trim();
  if (navigator.clipboard) {
    navigator.clipboard.writeText(txt).then(function() {
      btn.textContent = '✅ Copied!';
      btn.style.background = 'var(--jade)';
      setTimeout(function() { btn.textContent = '📋 Copy Link'; btn.style.background = 'var(--green-d)'; }, 2500);
    });
  } else {
    /* Fallback for older browsers */
    var ta = document.createElement('textarea');
    ta.value = txt; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
    btn.textContent = '✅ Copied!';
    setTimeout(function() { btn.textContent = '📋 Copy Link'; }, 2500);
  }
}

/* ── Forgot Password modal ── */
function openFP() {
  document.getElementById('fpBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(function() {
    var inp = document.getElementById('fp_identifier');
    if (inp) inp.focus();
  }, 380);
}
function closeFP() {
  document.getElementById('fpBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeFP(); });

/* Auto-open if server returned fp error or success */
<?php if ($fpError || $fpSuccess): ?>
document.addEventListener('DOMContentLoaded', function() { openFP(); });
<?php endif; ?>

/* FP form loading state */
var fpForm = document.getElementById('fpForm');
if (fpForm) {
  fpForm.addEventListener('submit', function() {
    var btn = document.getElementById('fpSubmit');
    if (btn) { btn.classList.add('loading'); btn.disabled = true; }
  });
}

/* ── Password toggle (main form) ── */
function togglePw() {
  var inp = document.getElementById('pwField');
  var btn = document.getElementById('pwEye');
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.textContent = show ? '🙈' : '👁';
  btn.style.transform = 'translateY(-50%) scale(1.3)';
  setTimeout(function() { btn.style.transform = 'translateY(-50%)'; }, 200);
}

/* ── Submit loading state (main form) ── */
document.getElementById('loginForm').addEventListener('submit', function() {
  var btn = document.getElementById('submitBtn');
  btn.classList.add('loading'); btn.disabled = true;
});

/* ── Floating particles ── */
(function() {
  for (var i = 0; i < 12; i++) {
    var p = document.createElement('div');
    p.className = 'particle';
    var size = Math.random() * 5 + 3;
    p.style.cssText =
      'width:' + size + 'px;height:' + size + 'px;' +
      'left:' + (Math.random() * 100) + '%;' +
      'top:' + (Math.random() * 100) + '%;' +
      'animation-duration:' + (Math.random() * 7 + 7) + 's;' +
      'animation-delay:' + (Math.random() * 6) + 's;' +
      'opacity:' + (Math.random() * 0.35 + 0.1) + ';';
    document.body.appendChild(p);
  }
})();

/* ── Input focus animations ── */
document.querySelectorAll('.form-control').forEach(function(inp) {
  inp.addEventListener('focus', function()  { inp.parentElement.style.transform = 'translateY(-1px)'; });
  inp.addEventListener('blur',  function()  { inp.parentElement.style.transform = ''; });
});

/* ── Left panel stats stagger in ── */
document.querySelectorAll('.lp-stat').forEach(function(el, i) {
  el.style.opacity = '0'; el.style.transform = 'translateX(-20px)';
  el.style.transition = 'opacity .5s ease, transform .5s ease';
  setTimeout(function() { el.style.opacity = '1'; el.style.transform = 'translateX(0)'; }, 600 + i * 120);
});

/* ── Welcome text fade in ── */
var welcome = document.querySelector('.lp-welcome');
if (welcome) {
  welcome.style.opacity = '0'; welcome.style.transform = 'translateY(16px)';
  welcome.style.transition = 'opacity .6s ease, transform .6s ease';
  setTimeout(function() { welcome.style.opacity = '1'; welcome.style.transform = 'translateY(0)'; }, 300);
}

/* ── Right panel fields stagger ── */
document.querySelectorAll('.right-panel .form-group, .right-panel .btn-submit, .right-panel .divider, .right-panel .btn-register').forEach(function(el, i) {
  el.style.opacity = '0'; el.style.transform = 'translateY(14px)';
  el.style.transition = 'opacity .5s ease, transform .5s ease';
  setTimeout(function() { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, 400 + i * 80);
});
</script>
</body>
</html>