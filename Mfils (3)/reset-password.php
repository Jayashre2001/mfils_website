<?php
// reset-password.php — Password reset via token
require_once __DIR__ . '/includes/functions.php';
startSession();
if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = false;
$user    = null;

/* ── Validate token ── */
if (!$token) {
    $error = 'Invalid or missing reset link. Please request a new one.';
} else {
    try {
        $stmt = db()->prepare("SELECT id, username, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $error = 'This reset link has expired or is invalid. Please request a new one.';
        }
    } catch (Exception $ex) {
        $error = 'Something went wrong. Please try again.';
    }
}

/* ── Handle new password submission ── */
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $newPass     = $_POST['new_password']     ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
        $error = 'Password must contain at least one letter and one number.';
    } else {
        try {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            db()->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?")
               ->execute([$hash, $user['id']]);
            $success = true;
            setFlash('success', '✅ Password reset successfully! Please login with your new password.');
        } catch (Exception $ex) {
            $error = 'Failed to update password. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password – Mfills';
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
:root {
  --green-dd: #0e2414; --green-d: #1a3b22; --green-m: #2a6336; --green-l: #3a8a4a;
  --gold: #c8922a; --gold-l: #e0aa40; --gold-d: #a0721a;
  --jade: #0F7B5C; --jade-l: #14A376;
  --coral: #E8534A;
  --ivory: #f8f5ef; --ivory-d: #ede8de; --ivory-dd: #ddd5c4;
  --ink: #152018; --muted: #5a7a60;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Nunito', sans-serif; background: var(--green-dd);
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  padding: 2rem 1rem; position: relative; overflow: hidden;
}
body::before {
  content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image: radial-gradient(circle,rgba(200,146,42,.06) 1.5px,transparent 1.5px);
  background-size: 26px 26px;
}
.bg-glow { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; }
.bg-glow-1 { width:560px; height:560px; top:-160px; right:-120px; background:radial-gradient(circle,rgba(58,138,74,.22) 0%,transparent 70%); animation:gdrift 18s ease-in-out infinite; }
.bg-glow-2 { width:380px; height:380px; bottom:-100px; left:-80px; background:radial-gradient(circle,rgba(200,146,42,.12) 0%,transparent 70%); animation:gdrift 22s 4s ease-in-out infinite reverse; }
@keyframes gdrift { 0%,100%{transform:translate(0,0)}33%{transform:translate(20px,-16px)}66%{transform:translate(-14px,20px)} }

/* Card */
.rp-card {
  position: relative; z-index: 1;
  background: var(--ivory); border-radius: 22px; overflow: hidden;
  width: 100%; max-width: 480px;
  box-shadow: 0 48px 120px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.05);
  animation: cardIn .65s cubic-bezier(.34,1.2,.64,1) both;
}
@keyframes cardIn { from{opacity:0;transform:translateY(32px) scale(.97)} to{opacity:1;transform:none} }

/* Card header */
.rp-header {
  background: linear-gradient(135deg, var(--green-d), var(--green-dd));
  padding: 2rem 2.25rem 1.75rem;
  border-bottom: 2px solid rgba(200,146,42,.22);
  position: relative;
}
.rp-header::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle,rgba(200,146,42,.08) 1.5px,transparent 1.5px);
  background-size: 20px 20px; pointer-events: none;
}
.rp-header-inner { position: relative; z-index: 1; }
.rp-brand { display: flex; align-items: center; gap: 9px; margin-bottom: 1.5rem; }
.rp-logo  { height: 38px; width: auto; filter: brightness(1.1) drop-shadow(0 2px 6px rgba(0,0,0,.4)); }
.rp-brand-name { font-family: 'Cinzel',serif; font-size: 1.1rem; font-weight: 900; color: var(--ivory); letter-spacing: .04em; }
.rp-header-icon { font-size: 2rem; margin-bottom: .5rem; }
.rp-title { font-family: 'DM Serif Display',serif; font-size: 1.5rem; color: #fff; margin-bottom: .2rem; }
.rp-title em { color: var(--gold-l); font-style: italic; }
.rp-sub { font-size: .8rem; color: rgba(255,255,255,.42); }

/* Card body */
.rp-body { padding: 2rem 2.25rem; }

/* Alerts */
.rp-alert {
  padding: .85rem 1rem; border-radius: 10px;
  font-size: .875rem; font-weight: 600; margin-bottom: 1.35rem;
  display: flex; align-items: flex-start; gap: .5rem;
  animation: alertIn .4s ease both;
}
@keyframes alertIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
.rp-alert.danger  { background:rgba(220,38,38,.08);  color:#B91C1C; border:1px solid rgba(220,38,38,.2); }
.rp-alert.success { background:rgba(15,123,92,.08);  color:var(--jade); border:1px solid rgba(15,123,92,.2); }
.rp-alert.info    { background:rgba(26,59,34,.08);   color:var(--green-d); border:1px solid rgba(26,59,34,.15); }

/* Form */
.form-group { margin-bottom: 1.1rem; }
.form-label { display:block; font-size:.68rem; font-weight:800; color:var(--green-d); margin-bottom:.38rem; text-transform:uppercase; letter-spacing:.1em; font-family:'Cinzel',serif; }
.form-control {
  width:100%; padding:.75rem 1rem;
  border:1.5px solid var(--ivory-dd); border-radius:10px;
  background:#fff; font-family:'Nunito',sans-serif;
  font-size:.92rem; color:var(--ink); outline:none;
  transition:border-color .2s, box-shadow .2s;
}
.form-control:focus { border-color:var(--green-l); box-shadow:0 0 0 3px rgba(26,59,34,.1); }
.form-control::placeholder { color:#a8b8a0; }
.form-hint { font-size:.72rem; color:var(--muted); margin-top:.3rem; }

/* Password strength bar */
.strength-wrap { margin-top: .5rem; }
.strength-bar {
  height: 4px; border-radius: 10px; overflow: hidden;
  background: var(--ivory-dd); margin-bottom: .3rem;
}
.strength-fill { height: 100%; width: 0%; border-radius: 10px; transition: width .35s, background .35s; }
.strength-label { font-size: .68rem; font-weight: 700; color: var(--muted); }

/* Pw wrap */
.pw-wrap { position: relative; }
.pw-wrap .form-control { padding-right: 2.8rem; }
.pw-eye { position:absolute; right:.9rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:.9rem; color:var(--muted); padding:.2rem; transition:color .2s,transform .2s; }
.pw-eye:hover { color:var(--green-d); transform:translateY(-50%) scale(1.15); }

/* Requirements checklist */
.pw-reqs { display:flex; flex-direction:column; gap:.28rem; margin-top:.6rem; }
.req-item { display:flex; align-items:center; gap:.4rem; font-size:.72rem; color:var(--muted); transition:color .2s; }
.req-item.met { color:var(--jade); }
.req-dot { width:7px; height:7px; border-radius:50%; background:var(--ivory-dd); flex-shrink:0; transition:background .2s; }
.req-item.met .req-dot { background:var(--jade); }

/* Submit button */
.btn-submit {
  width:100%; padding:.88rem; margin-top:.75rem;
  background:linear-gradient(135deg,var(--green-d),var(--green-m));
  color:#fff; font-family:'Cinzel',serif; font-size:.9rem; font-weight:700;
  border:none; border-radius:10px; cursor:pointer;
  box-shadow:0 6px 22px rgba(26,59,34,.3);
  transition:all .25s;
  display:flex; align-items:center; justify-content:center; gap:.45rem;
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(26,59,34,.4); }
.btn-submit:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.btn-submit .spinner { display:none; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
.btn-submit.loading .spinner { display:block; }
.btn-submit.loading .btn-txt { display:none; }

/* Success screen */
.success-screen { text-align:center; }
.success-ring {
  width:72px; height:72px; border-radius:50%;
  background:linear-gradient(135deg,var(--jade),var(--jade-l));
  display:flex; align-items:center; justify-content:center;
  font-size:2rem; margin:0 auto 1.1rem;
  animation:popIn .55s cubic-bezier(.34,1.6,.64,1) both;
  box-shadow:0 0 0 0 rgba(15,123,92,.4);
}
@keyframes popIn { from{transform:scale(0)} 70%{transform:scale(1.1);box-shadow:0 0 0 14px rgba(15,123,92,0)} to{transform:scale(1)} }
.success-title { font-family:'DM Serif Display',serif; font-size:1.35rem; color:var(--green-d); margin-bottom:.4rem; }
.success-text  { font-size:.82rem; color:var(--muted); line-height:1.7; margin-bottom:1.25rem; }
.btn-login {
  display:flex; align-items:center; justify-content:center; gap:.45rem;
  width:100%; padding:.88rem;
  background:linear-gradient(135deg,var(--gold-d),var(--gold-l));
  color:var(--green-dd); font-family:'Cinzel',serif; font-size:.9rem; font-weight:800;
  border:none; border-radius:10px; text-decoration:none;
  box-shadow:0 6px 20px rgba(200,146,42,.3); transition:all .25s;
}
.btn-login:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(200,146,42,.4); color:var(--green-dd); }

/* Invalid/expired token screen */
.invalid-screen { text-align:center; }
.invalid-icon { font-size:2.5rem; margin-bottom:.75rem; }
.invalid-title { font-family:'DM Serif Display',serif; font-size:1.25rem; color:var(--coral); margin-bottom:.4rem; }
.invalid-text  { font-size:.82rem; color:var(--muted); line-height:1.7; margin-bottom:1.25rem; }
.btn-secondary {
  display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
  width:100%; padding:.82rem;
  background:transparent; border:1.5px solid var(--ivory-dd); border-radius:10px;
  color:var(--muted); font-family:'Cinzel',serif; font-size:.82rem; font-weight:700;
  text-decoration:none; transition:all .22s; margin-top:.5rem;
}
.btn-secondary:hover { border-color:var(--green-l); color:var(--green-d); }

/* Footer */
.rp-footer { text-align:center; font-size:.68rem; color:var(--muted); margin-top:1.25rem; }

@media(max-width:480px) {
  .rp-card { border-radius:16px; }
  .rp-header,.rp-body { padding-left:1.5rem; padding-right:1.5rem; }
}
</style>
</head>
<body>

<div class="bg-glow bg-glow-1"></div>
<div class="bg-glow bg-glow-2"></div>

<div class="rp-card">

  <!-- Header -->
  <div class="rp-header">
    <div class="rp-header-inner">
      <div class="rp-brand">
        <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" class="rp-logo">
        <span class="rp-brand-name">MFILLS</span>
      </div>
      <div class="rp-header-icon">🔐</div>
      <div class="rp-title">Reset <em>Password</em></div>
      <div class="rp-sub">Create a new secure password for your account</div>
    </div>
  </div>

  <!-- Body -->
  <div class="rp-body">

    <?php if ($success): ?>
    <!-- ── Success ── -->
    <div class="success-screen">
      <div class="success-ring">✓</div>
      <div class="success-title">Password Updated!</div>
      <div class="success-text">
        Your password has been reset successfully.<br>
        You can now login with your new password.
      </div>
      <a href="<?= APP_URL ?>/login.php" class="btn-login">🔑 Login Now</a>
    </div>

    <?php elseif (!$user): ?>
    <!-- ── Invalid / expired token ── -->
    <div class="invalid-screen">
      <div class="invalid-icon">⚠️</div>
      <div class="invalid-title">Link Expired or Invalid</div>
      <div class="invalid-text">
        <?= e($error) ?><br><br>
        Password reset links are valid for <strong>1 hour</strong> only.
        Please request a new one from the login page.
      </div>
      <a href="<?= APP_URL ?>/login.php" class="btn-login">← Back to Login</a>
      <a href="<?= APP_URL ?>/login.php" class="btn-secondary">Request New Reset Link</a>
    </div>

    <?php else: ?>
    <!-- ── Reset form ── -->

    <?php if ($error): ?>
      <div class="rp-alert danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <div class="rp-alert info">
      🙋 Resetting password for: <strong><?= e($user['username']) ?></strong>
    </div>

    <form method="POST" action="" novalidate id="resetForm">
      <input type="hidden" name="token_ref" value="<?= e($token) ?>">

      <div class="form-group">
        <label class="form-label" for="newPw">New Password</label>
        <div class="pw-wrap">
          <input type="password" name="new_password" id="newPw" class="form-control"
                 placeholder="Minimum 8 characters"
                 required autocomplete="new-password"
                 oninput="checkStrength(this.value)">
          <button type="button" class="pw-eye" onclick="togglePw('newPw','eye1')" id="eye1">👁</button>
        </div>
        <!-- strength bar -->
        <div class="strength-wrap">
          <div class="strength-bar"><div class="strength-fill" id="sBar"></div></div>
          <div class="strength-label" id="sLabel">Enter a password</div>
        </div>
        <!-- requirements -->
        <div class="pw-reqs">
          <div class="req-item" id="req-len"><div class="req-dot"></div>At least 8 characters</div>
          <div class="req-item" id="req-let"><div class="req-dot"></div>Contains a letter</div>
          <div class="req-item" id="req-num"><div class="req-dot"></div>Contains a number</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="conPw">Confirm New Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm_password" id="conPw" class="form-control"
                 placeholder="Re-enter your new password"
                 required autocomplete="new-password"
                 oninput="checkMatch()">
          <button type="button" class="pw-eye" onclick="togglePw('conPw','eye2')" id="eye2">👁</button>
        </div>
        <p class="form-hint" id="matchHint"></p>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span class="btn-txt">🔒 Set New Password</span>
        <div class="spinner"></div>
      </button>
    </form>

    <div style="text-align:center;margin-top:1rem">
      <a href="<?= APP_URL ?>/login.php" style="font-size:.72rem;color:var(--muted);text-decoration:none;font-weight:700">← Back to Login</a>
    </div>

    <?php endif; ?>

    <p class="rp-footer">Mfills Business Partner Portal &nbsp;·&nbsp; Secure Password Reset</p>

  </div><!-- /rp-body -->
</div>

<script>
/* ── Password visibility toggle ── */
function togglePw(inputId, btnId) {
  var inp = document.getElementById(inputId);
  var btn = document.getElementById(btnId);
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.textContent = show ? '🙈' : '👁';
}

/* ── Strength checker ── */
function checkStrength(v) {
  var len = v.length >= 8;
  var let_ = /[A-Za-z]/.test(v);
  var num  = /[0-9]/.test(v);
  var spec = /[^A-Za-z0-9]/.test(v);

  document.getElementById('req-len').classList.toggle('met', len);
  document.getElementById('req-let').classList.toggle('met', let_);
  document.getElementById('req-num').classList.toggle('met', num);

  var score = [len, let_, num, spec, v.length >= 12].filter(Boolean).length;
  var bar   = document.getElementById('sBar');
  var lbl   = document.getElementById('sLabel');
  var configs = [
    { pct:'0%',   bg:'transparent', txt:'Enter a password' },
    { pct:'25%',  bg:'#E8534A',     txt:'Weak' },
    { pct:'50%',  bg:'#e0aa40',     txt:'Fair' },
    { pct:'75%',  bg:'#0F7B5C',     txt:'Good' },
    { pct:'100%', bg:'#1a3b22',     txt:'Strong 💪' },
  ];
  var cfg = configs[score] || configs[0];
  bar.style.width = cfg.pct; bar.style.background = cfg.bg;
  lbl.textContent = cfg.txt; lbl.style.color = cfg.bg || 'var(--muted)';
}

/* ── Match checker ── */
function checkMatch() {
  var np = document.getElementById('newPw').value;
  var cp = document.getElementById('conPw').value;
  var h  = document.getElementById('matchHint');
  if (!cp) { h.textContent = ''; return; }
  if (np === cp) { h.textContent = '✅ Passwords match'; h.style.color = 'var(--jade)'; }
  else           { h.textContent = '❌ Passwords do not match'; h.style.color = '#B91C1C'; }
}

/* ── Submit loading state ── */
var form = document.getElementById('resetForm');
if (form) {
  form.addEventListener('submit', function(e) {
    var np = document.getElementById('newPw').value;
    var cp = document.getElementById('conPw').value;
    if (np !== cp) { e.preventDefault(); alert('Passwords do not match.'); return; }
    var btn = document.getElementById('submitBtn');
    btn.classList.add('loading'); btn.disabled = true;
  });
}
</script>
</body>
</html>