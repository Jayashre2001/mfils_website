<?php
// admin/login.php
require_once __DIR__ . '/config.php';
adminStartSession();

if (isAdminLoggedIn()) {
    header('Location: ' . rtrim(APP_URL,'/') . '/admin/index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_USERNAME && $p === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username']  = $u;
        header('Location: ' . rtrim(APP_URL,'/') . '/admin/index.php'); exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --maroon:#6B1C2D;--maroon-d:#4A1220;--maroon-l:#8B2E43;
  --gold:#C8973A;--gold-l:#E4B86A;--gold-d:#A07828;
  --cream:#FAF4EB;--ink:#1C1208;--muted:#7A6B5A;
}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  background:linear-gradient(135deg,var(--maroon-d) 0%,var(--maroon) 50%,var(--gold-d) 100%);
  display:flex;align-items:center;justify-content:center;padding:1.5rem;
}
.login-wrap{
  width:100%;max-width:420px;
  background:#fff;border-radius:16px;
  box-shadow:0 24px 64px rgba(0,0,0,.35);
  overflow:hidden;
}
.login-top{
  background:var(--maroon-d);
  padding:2rem 2rem 1.5rem;
  text-align:center;
  border-bottom:3px solid var(--gold);
}
.login-top .icon{
  width:60px;height:60px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--gold-l));
  display:inline-flex;align-items:center;justify-content:center;
  font-size:26px;margin-bottom:1rem;
  box-shadow:0 4px 16px rgba(200,151,58,.4);
}
.login-top h1{
  font-family:'Playfair Display',serif;
  font-size:1.5rem;color:var(--gold-l);margin-bottom:.25rem;
}
.login-top p{color:rgba(250,244,235,.6);font-size:.85rem;}
.login-body{padding:2rem;}
.form-group{margin-bottom:1.1rem;}
label{display:block;font-size:.8rem;font-weight:600;color:var(--muted);
  text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;}
input{
  width:100%;padding:.7rem 1rem;
  border:1.5px solid #E5DDD4;border-radius:8px;
  font-family:'DM Sans',sans-serif;font-size:.95rem;
  background:var(--cream);color:var(--ink);
  transition:border-color .2s;outline:none;
}
input:focus{border-color:var(--maroon);background:#fff;}
.btn-login{
  width:100%;padding:.75rem;
  background:linear-gradient(135deg,var(--maroon),var(--maroon-l));
  color:#fff;border:none;border-radius:8px;
  font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;
  cursor:pointer;transition:opacity .2s,transform .15s;
  letter-spacing:.03em;margin-top:.5rem;
}
.btn-login:hover{opacity:.9;transform:translateY(-1px);}
.error{
  background:#FEE2E2;color:#991B1B;
  border:1px solid #FECACA;border-left:4px solid #EF4444;
  border-radius:8px;padding:.75rem 1rem;font-size:.875rem;
  margin-bottom:1.1rem;
}
.back{text-align:center;margin-top:1.25rem;font-size:.85rem;color:var(--muted);}
.back a{color:var(--maroon);font-weight:600;}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-top">
    <div class="icon">🥻</div>
    <h1><?= APP_NAME ?></h1>
    <p>Admin Control Panel</p>
  </div>
  <div class="login-body">
    <?php if ($error): ?>
      <div class="error">⚠ <?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="admin" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Sign In →</button>
    </form>
    <div class="back"><a href="<?= APP_URL ?>">← Back to main site</a></div>
  </div>
</div>
</body>
</html>
