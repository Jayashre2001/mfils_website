<?php
// admin/products.php
require_once __DIR__ . '/config.php';
$pageTitle = 'MShop Products';
$pdo = db();

/* ── Helper: handle image input (URL or uploaded file) ────────────────── */
function resolveImage(?string $urlInput, string $fileKey): ?string {
    if (!empty($_FILES[$fileKey]['tmp_name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES[$fileKey];
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp','image/avif','image/heic','image/heif'];
        $maxBytes = 2 * 1024 * 1024;
        if (!in_array($file['type'], $allowed))  return null;
        if ($file['size'] > $maxBytes)            return null;
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_', true) . '.' . strtolower($ext);
        $uploadDir = dirname(__DIR__) . '/uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return APP_URL . '/uploads/products/' . $filename;
        }
        return null;
    }
    $url = trim($urlInput ?? '');
    return $url !== '' ? $url : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name         = trim($_POST['name'] ?? '');
        $desc         = trim($_POST['description'] ?? '');
        $price        = (float)($_POST['price'] ?? 0);
        $bv           = (float)($_POST['bv'] ?? $price);
        $discount_pct = (float)($_POST['discount_pct'] ?? 0);
        $img          = resolveImage($_POST['image_url'] ?? null, 'image_file');
        if ($name && $price > 0) {
            $pdo->prepare("INSERT INTO products (name,description,price,bv,discount_pct,image_url,is_active,created_at) VALUES (?,?,?,?,?,?,1,NOW())")
                ->execute([$name,$desc,$price,$bv,$discount_pct,$img]);
            setAdminFlash('success', "Product \"$name\" added to MShop.");
        }
    }
    if ($action === 'toggle') {
        $pid = (int)$_POST['pid'];
        $pdo->prepare("UPDATE products SET is_active = 1 - is_active WHERE id=?")->execute([$pid]);
        setAdminFlash('success', 'Product visibility toggled on MShop.');
    }
    if ($action === 'delete') {
        $pid = (int)$_POST['pid'];
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$pid]);
        setAdminFlash('success', 'Product removed from MShop.');
    }
    if ($action === 'edit') {
        $pid          = (int)$_POST['pid'];
        $name         = trim($_POST['name'] ?? '');
        $desc         = trim($_POST['description'] ?? '');
        $price        = (float)($_POST['price'] ?? 0);
        $bv           = (float)($_POST['bv'] ?? $price);
        $discount_pct = (float)($_POST['discount_pct'] ?? 0);
        $img          = resolveImage($_POST['image_url'] ?? null, 'image_file');
        if ($img === null) {
            $existing = $pdo->prepare("SELECT image_url FROM products WHERE id=?");
            $existing->execute([$pid]);
            $img = $existing->fetchColumn() ?: null;
        }
        $pdo->prepare("UPDATE products SET name=?,description=?,price=?,bv=?,discount_pct=?,image_url=? WHERE id=?")
            ->execute([$name,$desc,$price,$bv,$discount_pct,$img,$pid]);
        setAdminFlash('success', 'MShop product updated successfully.');
    }
    header('Location: products.php'); exit;
}

$products = $pdo->query(
  "SELECT p.*,
    (SELECT COUNT(*) FROM orders WHERE product_id=p.id) as order_count,
    (SELECT COALESCE(SUM(amount),0) FROM orders WHERE product_id=p.id AND status='completed') as revenue,
    (SELECT COALESCE(SUM(bv),0) FROM orders WHERE product_id=p.id AND status='completed') as total_bv
   FROM products p ORDER BY p.created_at DESC"
)->fetchAll();

$totalBv       = array_sum(array_column($products, 'total_bv'));
$totalRevenue  = array_sum(array_column($products, 'revenue'));
$activeCount   = count(array_filter($products, fn($p) => $p['is_active']));
$plusCount     = count(array_filter($products, fn($p) => (float)($p['discount_pct'] ?? 0) > 0));

require_once '_layout.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Outfit:wght@300;400;500;600;700;800&display=swap');

/* ══════════════════════════════════════════════
   MSHOP ADMIN — PRODUCTS PAGE
   Forest Green · Gold · Cream · Premium MLM
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
  --cream:   #FDFAF4;
  --cream-d: #F3EDE0;
  --border:  #DDD5C4;
  --muted:   #6b8a72;
  --ink:     #0e2414;
  --white:   #ffffff;
  --danger:  #C0392B;
  --danger-l:#e74c3c;
  --info:    #1a6fa8;
  --purple:  #6c3fa8;
}

/* ══ STATS STRIP ══ */
.mshop-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.75rem;
}
.stat-card {
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 14px;
  padding: 1rem 1.2rem;
  display: flex; align-items: center; gap: .85rem;
  box-shadow: 0 2px 12px rgba(14,36,20,.05);
  transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 22px rgba(14,36,20,.1); }
.stat-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.stat-icon svg { width: 20px; height: 20px; }
.si-green  { background: linear-gradient(135deg, #d6f0db, #b3e0bb); }
.si-gold   { background: linear-gradient(135deg, #fef0d4, #f5d98c); }
.si-blue   { background: linear-gradient(135deg, #d4e9f7, #a8d3f0); }
.si-purple { background: linear-gradient(135deg, #e8d8f7, #d0b0f0); }
.stat-val {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem; font-weight: 700; color: var(--g-d);
  line-height: 1.1;
}
.stat-label {
  font-size: .67rem; color: var(--muted);
  font-family: 'Outfit', sans-serif; font-weight: 500;
  text-transform: uppercase; letter-spacing: .07em;
  margin-top: .15rem;
}

/* ══ PAGE HEADER ══ */
.prod-header {
  display: flex; align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 2px solid var(--border);
  position: relative;
}
.prod-header::after {
  content: ''; position: absolute; bottom: -2px; left: 0;
  width: 80px; height: 2px;
  background: linear-gradient(90deg, var(--gold), var(--g-l));
}
.prod-header-left { display: flex; align-items: center; gap: 1rem; }
.prod-header-icon {
  width: 46px; height: 46px; border-radius: 12px;
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 14px rgba(14,36,20,.25);
}
.prod-header-icon svg { width: 22px; height: 22px; }
.prod-header h2 {
  font-family: 'Cinzel', serif;
  font-size: 1.2rem; color: var(--g-d);
  font-weight: 700; margin: 0; line-height: 1.2;
}
.prod-header-sub {
  font-size: .72rem; color: var(--muted);
  font-family: 'Outfit', sans-serif; font-weight: 400; margin-top: .1rem;
}
.count-badge {
  background: linear-gradient(135deg, var(--g-dd), var(--g));
  color: #fff; font-size: .65rem; padding: 3px 10px;
  border-radius: 20px; font-weight: 700; letter-spacing: .06em;
  font-family: 'Outfit', sans-serif; vertical-align: middle; margin-left: .4rem;
}
.header-actions { display: flex; gap: .6rem; align-items: center; }

/* ══ SEARCH / FILTER BAR ══ */
.filter-bar {
  display: flex; gap: .75rem; align-items: center;
  margin-bottom: 1.25rem; flex-wrap: wrap;
}
.search-wrap {
  position: relative; flex: 1; min-width: 200px;
}
.search-wrap svg {
  position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
  width: 15px; height: 15px; stroke: var(--muted); pointer-events: none;
}
.search-input {
  width: 100%; padding: .58rem .9rem .58rem 2.4rem;
  border: 1.5px solid var(--border); border-radius: 10px;
  font-size: .84rem; font-family: 'Outfit', sans-serif;
  color: var(--ink); background: var(--white);
  outline: none; transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.search-input:focus { border-color: var(--g-d); box-shadow: 0 0 0 3px rgba(28,61,36,.1); }
.filter-select {
  padding: .58rem .9rem;
  border: 1.5px solid var(--border); border-radius: 10px;
  font-size: .82rem; font-family: 'Outfit', sans-serif;
  color: var(--ink); background: var(--white);
  outline: none; cursor: pointer;
  transition: border-color .2s;
}
.filter-select:focus { border-color: var(--g-d); }

/* ══ ADD BUTTON ══ */
.btn-add-product {
  display: inline-flex; align-items: center; gap: .55rem;
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g) 60%, var(--g-l) 100%);
  color: #fff; border: none; border-radius: 10px;
  padding: .65rem 1.35rem; cursor: pointer;
  font-family: 'Outfit', sans-serif; font-size: .82rem; font-weight: 700;
  letter-spacing: .04em; text-transform: uppercase;
  box-shadow: 0 4px 16px rgba(14,36,20,.28);
  transition: all .25s cubic-bezier(.34,1.3,.64,1);
  position: relative; overflow: hidden; white-space: nowrap;
}
.btn-add-product::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,.12));
  pointer-events: none;
}
.btn-add-product:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 8px 24px rgba(14,36,20,.35); }
.btn-add-product .btn-icon {
  width: 20px; height: 20px;
  background: rgba(255,255,255,.18); border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: .9rem; font-weight: 900;
}

/* ══ TABLE CARD ══ */
.tcard {
  border-radius: 16px; overflow: hidden;
  box-shadow: 0 4px 24px rgba(14,36,20,.08), 0 1px 4px rgba(14,36,20,.04);
  background: var(--white);
  border: 1.5px solid var(--border);
}
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .875rem; font-family: 'Outfit', sans-serif; }

thead tr {
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 50%, var(--g) 100%) !important;
  border-bottom: 2px solid var(--gold) !important;
}
thead th {
  color: rgba(255,255,255,.85) !important;
  -webkit-text-fill-color: rgba(255,255,255,.85) !important;
  padding: .9rem 1rem; text-align: left;
  font-family: 'Outfit', sans-serif;
  font-weight: 700; font-size: .65rem;
  letter-spacing: .1em; text-transform: uppercase;
  white-space: nowrap; background: transparent !important; opacity: 1 !important;
}
thead th:first-child { padding-left: 1.25rem; }
thead th.th-center { text-align: center; }

tbody tr { border-bottom: 1px solid var(--cream-d); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--cream); }
tbody td { padding: .88rem 1rem; vertical-align: middle; color: var(--ink); }
tbody td:first-child { padding-left: 1.25rem; }

/* ══ ROW NUMBER ══ */
.row-num { font-size: .72rem; color: var(--muted); font-weight: 500; }

/* ══ PRODUCT CELL ══ */
.prod-cell { display: flex; align-items: center; gap: .85rem; }
.prod-thumb {
  width: 52px; height: 52px; object-fit: cover;
  border-radius: 10px; border: 2px solid var(--border);
  flex-shrink: 0; transition: transform .2s;
}
tbody tr:hover .prod-thumb { transform: scale(1.08); }
.prod-thumb-placeholder {
  width: 52px; height: 52px; border-radius: 10px;
  background: linear-gradient(135deg, #dceede, #c8e0cc);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; border: 2px solid var(--border);
}
.prod-name { font-weight: 700; color: var(--g-d); font-size: .88rem; }
.prod-desc { font-size: .7rem; color: var(--muted); margin-top: 2px; }
.prod-cat {
  display: inline-block; margin-top: 3px;
  font-size: .6rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; padding: 1px 7px; border-radius: 8px;
  background: rgba(28,61,36,.08); color: var(--g-d);
}

/* ══ PRICE + BV CELL ══ */
.price-val { font-family: 'Cinzel', serif; font-weight: 700; color: var(--gold-d); font-size: .95rem; }
.bv-cell { display: flex; align-items: center; gap: .3rem; }
.bv-val { font-weight: 700; color: var(--g-d); font-size: .88rem; }
.bv-pill {
  display: inline-flex; align-items: center; gap: .2rem;
  font-size: .68rem; font-weight: 700; color: var(--g-l);
  background: rgba(58,138,74,.1); border-radius: 8px;
  padding: 1px 6px; border: 1px solid rgba(58,138,74,.2);
}
.bv-warn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  background: rgba(200,140,0,.15); color: #a07000;
  font-size: .62rem; font-weight: 800; flex-shrink: 0; cursor: default;
}

/* ══ DISCOUNT ══ */
.disc-pill {
  display: inline-flex; align-items: center; gap: .25rem;
  background: rgba(15,123,92,.1); color: #0a6644;
  font-weight: 800; font-size: .72rem;
  padding: .22rem .65rem; border-radius: 20px;
  border: 1px solid rgba(15,123,92,.2);
}
.disc-none { color: var(--border); font-size: .8rem; }

/* ══ STOCK CELL ══ */
.stock-val { font-weight: 700; font-size: .85rem; }
.stock-ok   { color: var(--g-d); }
.stock-low  { color: #b8600a; }
.stock-zero { color: var(--danger); }

/* ══ STATUS TOGGLE ══ */
.status-toggle {
  display: inline-flex; align-items: center; gap: .4rem;
  border: none; border-radius: 20px; cursor: pointer;
  font-family: 'Outfit', sans-serif;
  font-size: .68rem; font-weight: 700;
  letter-spacing: .05em; text-transform: uppercase;
  padding: .3rem .8rem; transition: all .2s;
}
.status-active   { background: rgba(28,61,36,.1);  color: var(--g-d);  border: 1.5px solid rgba(28,61,36,.2); }
.status-active:hover { background: rgba(28,61,36,.18); }
.status-inactive { background: rgba(192,57,43,.08); color: var(--danger); border: 1.5px solid rgba(192,57,43,.2); }
.status-inactive:hover { background: rgba(192,57,43,.15); }
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.status-active   .status-dot { background: var(--g-l);     box-shadow: 0 0 0 2px rgba(90,184,112,.3); }
.status-inactive .status-dot { background: var(--danger-l); }

/* ══ DATE ══ */
.date-val { font-size: .75rem; color: var(--muted); white-space: nowrap; }

/* ══ ACTIONS ══ */
.action-wrap { display: flex; gap: .4rem; align-items: center; }
.btn-icon-action {
  width: 34px; height: 34px; border-radius: 9px; border: none;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .22s cubic-bezier(.34,1.3,.64,1); flex-shrink: 0;
}
.btn-edit   { background: rgba(200,146,42,.1);  color: var(--gold-d); border: 1.5px solid rgba(200,146,42,.25); }
.btn-edit:hover   { background: var(--gold);   color: #fff; transform: scale(1.12) rotate(-5deg); box-shadow: 0 4px 12px rgba(200,146,42,.4); border-color: var(--gold); }
.btn-delete { background: rgba(192,57,43,.08); color: var(--danger);  border: 1.5px solid rgba(192,57,43,.2); }
.btn-delete:hover { background: var(--danger); color: #fff; transform: scale(1.12) rotate(5deg);  box-shadow: 0 4px 12px rgba(192,57,43,.4);  border-color: var(--danger); }

/* ══ EMPTY STATE ══ */
.empty-state-row td { text-align: center; padding: 4rem 2rem !important; color: var(--muted); }
.empty-icon {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #dceede, #c8e0cc);
  display: flex; align-items: center; justify-content: center; margin: 0 auto .85rem;
}
.empty-state-row h3 { font-family: 'Cinzel', serif; font-size: 1rem; color: var(--g-d); margin-bottom: .3rem; }
.empty-state-row p  { font-size: .82rem; }

/* ══ MSHOP PLUS BADGE ══ */
.plus-badge {
  display: inline-flex; align-items: center; gap: .25rem;
  background: linear-gradient(135deg, #f5d98c, #e0b050);
  color: #7a4e00; font-size: .58rem; font-weight: 800;
  padding: 2px 7px; border-radius: 20px; letter-spacing: .06em;
  text-transform: uppercase; white-space: nowrap;
}

/* ══ MODALS ══ */
.modal-bg {
  display: none; position: fixed; inset: 0;
  background: rgba(14,36,20,.55); backdrop-filter: blur(6px);
  z-index: 1000; align-items: center; justify-content: center; padding: 1rem;
}
.modal-bg.open { display: flex; animation: fadeInBg .2s ease; }
@keyframes fadeInBg { from{opacity:0} to{opacity:1} }

.modal-box {
  background: var(--white); border-radius: 20px; width: 100%; max-width: 640px;
  box-shadow: 0 32px 80px rgba(14,36,20,.22), 0 4px 20px rgba(14,36,20,.1);
  overflow: hidden; animation: slideUp .3s cubic-bezier(.34,1.4,.64,1);
  max-height: 92vh; overflow-y: auto; border: 1.5px solid var(--border);
}
@keyframes slideUp { from{transform:translateY(32px) scale(.96);opacity:0} to{transform:translateY(0) scale(1);opacity:1} }

.modal-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.35rem 1.6rem 1.1rem;
  background: linear-gradient(135deg, var(--g-dd) 0%, var(--g-d) 60%, var(--g) 100%);
  border-bottom: 2px solid var(--gold);
  position: sticky; top: 0; z-index: 2;
}
.modal-head-left { display: flex; align-items: center; gap: .75rem; }
.modal-head-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
}
.modal-head-icon svg { width: 18px; height: 18px; }
.modal-head h3 { font-family: 'Cinzel', serif; font-size: .95rem; font-weight: 700; color: #fff; margin: 0; }
.modal-head-sub { font-size: .65rem; color: rgba(255,255,255,.5); margin-top: .1rem; font-family: 'Outfit', sans-serif; }
.modal-close {
  width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,.25);
  background: rgba(255,255,255,.1); color: rgba(255,255,255,.8);
  font-size: 1rem; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s; line-height: 1;
}
.modal-close:hover { background: rgba(255,255,255,.25); transform: rotate(90deg); color: #fff; }

.modal-body { padding: 1.6rem; }

/* MSHOP PLUS INFO BOX */
.mshop-info-box {
  background: linear-gradient(135deg, #fffbf0, #fff8e7);
  border: 1.5px solid rgba(200,146,42,.3);
  border-radius: 12px; padding: .9rem 1.1rem;
  margin-bottom: 1rem; display: flex; gap: .75rem; align-items: flex-start;
}
.mshop-info-icon {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  background: linear-gradient(135deg, #f5d98c, #e0b050);
  display: flex; align-items: center; justify-content: center;
}
.mshop-info-icon svg { width: 16px; height: 16px; }
.mshop-info-title {
  font-family: 'Cinzel', serif; font-size: .78rem; font-weight: 700;
  color: #7a4e00; margin-bottom: .2rem;
}
.mshop-info-text { font-size: .72rem; color: #9a6800; line-height: 1.55; }

/* FORM SECTIONS */
.form-section {
  background: var(--cream); border-radius: 12px;
  border: 1.5px solid var(--border); padding: 1.1rem 1.2rem;
  margin-bottom: 1rem;
}
.form-section-label {
  font-size: .6rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .12em; color: var(--muted);
  margin-bottom: .85rem; display: flex; align-items: center; gap: .4rem;
}
.form-section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.f-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.f-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
.f-group { display: flex; flex-direction: column; gap: .35rem; }
.f-label {
  font-size: .72rem; font-weight: 700; color: var(--g-d);
  letter-spacing: .04em; text-transform: uppercase;
  display: flex; align-items: center; gap: .4rem;
}
.f-label-badge {
  font-size: .58rem; font-weight: 700; padding: 1px 6px;
  border-radius: 8px; text-transform: uppercase; letter-spacing: .04em;
}
.badge-required { background: rgba(192,57,43,.1); color: var(--danger); }
.badge-optional { background: rgba(14,36,20,.07); color: var(--muted); }
.badge-club     { background: rgba(200,146,42,.12); color: var(--gold-d); }
.badge-bv       { background: rgba(28,61,36,.1); color: var(--g-d); }

.f-control {
  width: 100%; padding: .65rem .95rem;
  border: 1.5px solid var(--border); border-radius: 10px;
  font-size: .875rem; color: var(--ink);
  background: var(--white);
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box; outline: none;
  font-family: 'Outfit', sans-serif;
}
.f-control:focus { border-color: var(--g-d); box-shadow: 0 0 0 3px rgba(28,61,36,.1); }
.f-control::placeholder { color: #b8c8bb; }
textarea.f-control { resize: vertical; min-height: 75px; }
select.f-control { cursor: pointer; }

.f-hint {
  font-size: .68rem; color: var(--muted); line-height: 1.5;
  display: flex; align-items: flex-start; gap: .3rem;
}
.f-hint-icon {
  width: 14px; height: 14px; border-radius: 50%;
  background: rgba(200,146,42,.15); color: var(--gold-d);
  display: flex; align-items: center; justify-content: center;
  font-size: .55rem; font-weight: 800; flex-shrink: 0; margin-top: 1px;
}
.f-hint-g {
  background: rgba(28,61,36,.12); color: var(--g-d);
}

/* BV RATIO INDICATOR */
.bv-ratio-bar {
  height: 4px; border-radius: 4px;
  background: var(--border); margin-top: .4rem; overflow: hidden;
}
.bv-ratio-fill {
  height: 100%; border-radius: 4px;
  background: linear-gradient(90deg, var(--g-l), var(--gold-l));
  width: 0%; transition: width .4s ease;
}
.bv-ratio-label {
  font-size: .62rem; color: var(--muted); margin-top: .2rem;
  font-family: 'Outfit', sans-serif;
}

/* IMAGE TABS */
.img-tabs {
  display: flex; gap: 0; border: 1.5px solid var(--border);
  border-radius: 10px; overflow: hidden; margin-bottom: .75rem;
}
.img-tab {
  flex: 1; padding: .5rem .75rem; font-size: .72rem; font-weight: 700;
  letter-spacing: .05em; text-transform: uppercase; border: none;
  background: var(--cream); color: var(--muted); cursor: pointer;
  transition: all .18s; display: flex; align-items: center; justify-content: center; gap: .4rem;
  font-family: 'Outfit', sans-serif;
}
.img-tab:not(:last-child) { border-right: 1.5px solid var(--border); }
.img-tab.active { background: linear-gradient(135deg, var(--g-dd), var(--g)); color: #fff; }
.img-tab svg { width: 13px; height: 13px; }

.img-panel { display: none; }
.img-panel.active { display: block; }

.file-drop {
  border: 2px dashed var(--border); border-radius: 12px;
  padding: 1.5rem 1rem; text-align: center; cursor: pointer;
  background: var(--cream-d); transition: all .2s; position: relative;
}
.file-drop:hover, .file-drop.drag-over { border-color: var(--g-d); background: #e8f2ea; transform: scale(1.01); }
.file-drop input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.file-drop-icon { margin-bottom: .5rem; }
.file-drop-icon svg { width: 32px; height: 32px; opacity: .45; }
.file-drop-text { font-size: .78rem; color: var(--muted); font-family: 'Outfit', sans-serif; }
.file-drop-text strong { color: var(--g-d); }
.file-drop-name { margin-top: .5rem; font-size: .75rem; color: var(--g-d); font-weight: 600; display: none; }

.img-preview-wrap { margin-top: .6rem; display: none; border-radius: 10px; overflow: hidden; border: 1.5px solid var(--border); }
.img-preview-wrap.show { display: block; }
.img-preview-wrap img { width: 100%; max-height: 130px; object-fit: cover; display: block; }

/* MODAL FOOTER */
.modal-footer {
  display: flex; gap: .75rem; justify-content: flex-end;
  margin-top: 1.25rem; padding-top: 1rem;
  border-top: 1.5px solid var(--cream-d);
}

/* GENERIC BUTTONS */
.btn { display: inline-flex; align-items: center; gap: .4rem; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: .82rem; transition: all .22s; text-decoration: none; font-family: 'Outfit', sans-serif; letter-spacing: .03em; }
.btn-primary { background: linear-gradient(135deg, var(--g-dd), var(--g)); color: #fff; padding: .6rem 1.4rem; box-shadow: 0 4px 14px rgba(14,36,20,.25); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(14,36,20,.32); }
.btn-gold { background: linear-gradient(135deg, var(--gold-d), var(--gold-l)); color: #fff; padding: .6rem 1.4rem; box-shadow: 0 4px 14px rgba(200,146,42,.3); }
.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(200,146,42,.4); }
.btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--muted); padding: .58rem 1.2rem; }
.btn-outline:hover { border-color: var(--g-d); color: var(--g-d); background: var(--cream); }
.btn svg { width: 15px; height: 15px; }

/* TOOLTIP */
[data-tip] { position: relative; cursor: default; }
[data-tip]:hover::after {
  content: attr(data-tip);
  position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%);
  background: var(--g-dd); color: #fff; font-size: .65rem; font-family: 'Outfit', sans-serif;
  padding: 4px 9px; border-radius: 7px; white-space: nowrap; z-index: 99;
  pointer-events: none; font-weight: 500;
}

@media (max-width: 600px) {
  .f-row, .f-row-3 { grid-template-columns: 1fr; }
  .modal-body { padding: 1.1rem 1rem; }
  .prod-header { flex-direction: column; align-items: flex-start; gap: .75rem; }
  .mshop-stats { grid-template-columns: repeat(2, 1fr); }
  .header-actions { flex-wrap: wrap; }
}
</style>

<!-- ══ MSHOP STATS STRIP ══ -->
<div class="mshop-stats">
  <div class="stat-card">
    <div class="stat-icon si-green">
      <svg viewBox="0 0 24 24" fill="none" stroke="#1C3D24" stroke-width="2" stroke-linecap="round"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
    </div>
    <div>
      <div class="stat-val"><?= $activeCount ?> <span style="font-size:.7rem;font-family:'Outfit';color:var(--muted);font-weight:500">live</span></div>
      <div class="stat-label">Active Products</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-gold">
      <svg viewBox="0 0 24 24" fill="none" stroke="#7a4e00" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div>
      <div class="stat-val"><?= number_format($totalBv, 0) ?></div>
      <div class="stat-label">Total BV Generated</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="#1a6fa8" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div>
      <div class="stat-val"><?= inr($totalRevenue) ?></div>
      <div class="stat-label">Total Revenue</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-purple">
      <svg viewBox="0 0 24 24" fill="none" stroke="#6c3fa8" stroke-width="2" stroke-linecap="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
    <div>
      <div class="stat-val"><?= $plusCount ?></div>
      <div class="stat-label">MShop Plus Products</div>
    </div>
  </div>
</div>

<!-- ══ PAGE HEADER ══ -->
<div class="prod-header">
  <div class="prod-header-left">
    <div class="prod-header-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="#C8922A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 0 1-8 0"/>
      </svg>
    </div>
    <div>
      <h2>MShop Products <span class="count-badge"><?= count($products) ?></span></h2>
      <div class="prod-header-sub">Official Mfills product catalog · BV drives partner earnings across 7 levels</div>
    </div>
  </div>
  <div class="header-actions">
    <button onclick="document.getElementById('addModal').classList.add('open')" class="btn-add-product">
      <span class="btn-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      </span>
      Add Product
    </button>
  </div>
</div>

<!-- ══ FILTER BAR ══ -->
<div class="filter-bar">
  <div class="search-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="search-input" id="prodSearch" placeholder="Search products by name, category…" oninput="filterTable()">
  </div>
  <select class="filter-select" id="filterStatus" onchange="filterTable()">
    <option value="">All Status</option>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
  </select>
  <select class="filter-select" id="filterPlus" onchange="filterTable()">
    <option value="">All Types</option>
    <option value="plus">MShop Plus</option>
    <option value="regular">Regular</option>
  </select>
</div>

<!-- ══ TABLE ══ -->
<div class="tcard">
  <div class="table-wrap">
    <table id="prodTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Price</th>
          <th>BV <span data-tip="Business Volume — base for 7-level PSB" style="cursor:help;opacity:.6">ⓘ</span></th>
          <th>MShop Plus</th>
          <th>Stock</th>
          <th class="th-center">Orders</th>
          <th>Revenue</th>
          <th class="th-center">Visible</th>
          <th>Added</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($products)): ?>
        <tr class="empty-state-row">
          <td colspan="11">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
              </svg>
            </div>
            <h3>MShop is Empty</h3>
            <p>Add your first product to start generating BV for partners</p>
          </td>
        </tr>
      <?php else: foreach ($products as $i => $p):
          $bv   = (float)($p['bv'] ?? $p['price']);
          $disc = (float)($p['discount_pct'] ?? 0);
          $stk  = isset($p['stock']) && $p['stock'] !== null ? (int)$p['stock'] : -1;
          $ratio = $p['price'] > 0 ? min(100, round(($bv / $p['price']) * 100)) : 0;
          // -1 = column missing / not tracked = unlimited; 0 = explicitly set to 0 = out of stock
          $stockClass = ($stk === 0) ? 'stock-zero' : (($stk > 0 && $stk <= 10) ? 'stock-low' : 'stock-ok');
      ?>
      <tr
        data-name="<?= strtolower(e($p['name'])) ?> <?= strtolower(e($p['category'] ?? '')) ?>"
        data-status="<?= $p['is_active'] ? 'active' : 'inactive' ?>"
        data-plus="<?= $disc > 0 ? 'plus' : 'regular' ?>"
      >
        <td><span class="row-num"><?= $i + 1 ?></span></td>
        <td>
          <div class="prod-cell">
            <?php if ($p['image_url']): ?>
              <img src="<?= e($p['image_url']) ?>" class="prod-thumb"
                   onerror="this.outerHTML='<div class=\'prod-thumb-placeholder\'><svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#3a8a4a\' stroke-width=\'1.5\' style=\'width:22px;height:22px\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'3\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><path d=\'M21 15l-5-5L5 21\'/></svg></div>'">
            <?php else: ?>
              <div class="prod-thumb-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" style="width:22px;height:22px"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
            <?php endif; ?>
            <div>
              <div class="prod-name"><?= e($p['name']) ?></div>
              <?php if ($p['description']): ?>
                <div class="prod-desc"><?= e(substr($p['description'],0,46)) ?>…</div>
              <?php endif; ?>
              <?php if (!empty($p['category'])): ?>
                <span class="prod-cat"><?= e($p['category']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td><span class="price-val"><?= inr($p['price']) ?></span></td>
        <td>
          <div class="bv-cell">
            <div>
              <span class="bv-val"><?= number_format($bv, 2) ?></span>
              <div class="bv-ratio-bar" title="BV/Price ratio: <?= $ratio ?>%">
                <div class="bv-ratio-fill" style="width:<?= $ratio ?>%"></div>
              </div>
              <div class="bv-ratio-label"><?= $ratio ?>% of price</div>
            </div>
            <?php if ($bv == 0): ?>
              <span class="bv-warn" title="BV not set — price used as fallback">!</span>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <?php if ($disc > 0): ?>
            <div>
              <span class="disc-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:10px;height:10px"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                <?= $disc ?>% off
              </span>
              <div style="margin-top:3px"><span class="plus-badge">★ MShop Plus</span></div>
            </div>
          <?php else: ?>
            <span class="disc-none">—</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="stock-val <?= $stockClass ?>">
            <?php if ($stk < 0): ?>
              <span style="color:var(--muted)">—</span>
            <?php elseif ($stk === 0): ?>
              Out of Stock
            <?php elseif ($stk <= 10): ?>
              <?= $stk ?> left ⚠
            <?php else: ?>
              <?= number_format($stk) ?> units
            <?php endif; ?>
          </span>
        </td>
        <td style="text-align:center">
          <span style="font-weight:700;color:var(--g-d)"><?= number_format($p['order_count']) ?></span>
        </td>
        <td>
          <div>
            <span style="font-weight:700;color:#0a6644;font-size:.88rem"><?= inr($p['revenue']) ?></span>
            <?php if ($p['total_bv'] > 0): ?>
              <div style="font-size:.65rem;color:var(--muted);margin-top:2px">
                <?= number_format($p['total_bv'],0) ?> BV distributed
              </div>
            <?php endif; ?>
          </div>
        </td>
        <td style="text-align:center">
          <!-- Toggle form rendered outside table via id -->
          <button
            type="submit"
            form="toggleForm<?= $p['id'] ?>"
            class="status-toggle <?= $p['is_active'] ? 'status-active' : 'status-inactive' ?>"
            title="Click to toggle MShop visibility">
            <span class="status-dot"></span>
            <?= $p['is_active'] ? 'Visible' : 'Hidden' ?>
          </button>
        </td>
        <td><span class="date-val"><?= date('d M Y', strtotime($p['created_at'])) ?></span></td>
        <td>
          <div class="action-wrap">
            <button onclick='openEditModal(<?= json_encode($p) ?>)' class="btn-icon-action btn-edit" title="Edit product">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
            <button
              type="submit"
              form="deleteForm<?= $p['id'] ?>"
              class="btn-icon-action btn-delete"
              title="Delete product"
              onclick="return confirm('Remove this product from MShop permanently?')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
              </svg>
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ HIDDEN FORMS (outside table — avoids browser nested-form bugs) ══ -->
<?php foreach ($products as $p): ?>
  <form id="toggleForm<?= $p['id'] ?>" method="POST" style="display:none">
    <input type="hidden" name="action" value="toggle">
    <input type="hidden" name="pid"    value="<?= $p['id'] ?>">
  </form>
  <form id="deleteForm<?= $p['id'] ?>" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="pid"    value="<?= $p['id'] ?>">
  </form>
<?php endforeach; ?>

<!-- ════════════════════════════════════════
     ADD PRODUCT MODAL
════════════════════════════════════════ -->
<div class="modal-bg" id="addModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-head-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#C8922A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
          </svg>
        </div>
        <div>
          <h3>Add MShop Product</h3>
          <div class="modal-head-sub">New product · BV enables partner earnings across 7 network levels</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('addModal')" title="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">

      <!-- MShop Info Box -->
      <div class="mshop-info-box">
        <div class="mshop-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#7a4e00" stroke-width="2" stroke-linecap="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div>
          <div class="mshop-info-title">About MShop Products</div>
          <div class="mshop-info-text">
            Every purchase by a partner on MShop contributes Business Volume (BV) which drives PSB earnings across all 7 network levels. Set BV carefully — it directly impacts your partners' income potential. MShop Plus discounts are exclusive to active club members (2,500+ BV/month).
          </div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <!-- Basic Info -->
        <div class="form-section">
          <div class="form-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            Basic Info
          </div>
          <div class="f-group" style="margin-bottom:.85rem">
            <label class="f-label">Product Name <span class="f-label-badge badge-required">Required</span></label>
            <input type="text" name="name" class="f-control" placeholder="e.g. Ashwagandha 500mg" required>
          </div>
          <div class="f-group">
            <label class="f-label">Description <span class="f-label-badge badge-optional">Optional</span></label>
            <textarea name="description" class="f-control" placeholder="Brief product benefits for partners…"></textarea>
          </div>
        </div>

        <!-- Pricing & BV -->
        <div class="form-section">
          <div class="form-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Pricing, BV &amp; Stock
          </div>
          <div class="f-row" style="margin-bottom:.85rem">
            <div class="f-group">
              <label class="f-label">MRP (₹) <span class="f-label-badge badge-required">Required</span></label>
              <input type="number" name="price" id="addPrice" class="f-control" step="0.01" min="1" placeholder="0.00" required oninput="syncBv('add')">
            </div>
            <div class="f-group">
              <label class="f-label">Business Volume <span class="f-label-badge badge-bv">BV</span></label>
              <input type="number" name="bv" id="addBv" class="f-control" step="0.01" min="0" placeholder="Auto = same as MRP" oninput="this.dataset.autoSync='0'">
              <div class="f-hint"><span class="f-hint-icon f-hint-g">BV</span> PSB for all 7 levels calculated on this value.</div>
            </div>
          </div>
          <div class="f-group">
            <label class="f-label">MShop Plus Discount % <span class="f-label-badge badge-club">Club Only</span></label>
            <input type="number" name="discount_pct" class="f-control" step="0.1" min="0" max="99" placeholder="0 = no discount — only for active club members (2,500+ BV/month)">
            <div class="f-hint"><span class="f-hint-icon">★</span> Partners with 2,500+ monthly BV qualify for MShop Plus. Leaving at 0 shows regular price to everyone.</div>
          </div>
        </div>

        <!-- Image -->
        <div class="form-section">
          <div class="form-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            Product Image
          </div>
          <div class="img-tabs">
            <button type="button" class="img-tab active" onclick="switchImgTab('add','url',this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              URL Link
            </button>
            <button type="button" class="img-tab" onclick="switchImgTab('add','file',this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Upload File
            </button>
          </div>
          <div class="img-panel active" id="add-panel-url">
            <input type="url" name="image_url" id="addImgUrl" class="f-control" placeholder="https://example.com/product.jpg" oninput="previewImg(this,'addImgPreview')">
            <div class="img-preview-wrap" id="addImgPreview"><img src="" alt="Preview" onerror="this.parentElement.classList.remove('show')"></div>
          </div>
          <div class="img-panel" id="add-panel-file">
            <div class="file-drop" id="addFileDrop" ondragover="fileDragOver(event,this)" ondragleave="fileDragLeave(this)" ondrop="fileDrop(event,'addFileInput','addDropName','addFilePreview')">
              <input type="file" name="image_file" id="addFileInput" accept="image/*" onchange="fileChosen(this,'addDropName','addFilePreview')">
              <div class="file-drop-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
              <div class="file-drop-text"><strong>Click to browse</strong> or drag &amp; drop<br><span style="font-size:.68rem">JPG · PNG · GIF · WEBP · AVIF · max 2 MB</span></div>
              <div class="file-drop-name" id="addDropName"></div>
            </div>
            <div class="img-preview-wrap" id="addFilePreview"><img src="" alt="Preview" onerror="this.parentElement.classList.remove('show')"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" onclick="closeModal('addModal')" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add to MShop
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     EDIT PRODUCT MODAL
════════════════════════════════════════ -->
<div class="modal-bg" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-left">
        <div class="modal-head-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#C8922A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </div>
        <div>
          <h3>Edit MShop Product</h3>
          <div class="modal-head-sub">Update product · BV changes affect ongoing partner earnings</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('editModal')" title="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data" id="editForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="pid" id="editPid">

        <div class="form-section">
          <div class="form-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            Basic Info
          </div>
          <div class="f-group" style="margin-bottom:.85rem">
            <label class="f-label">Product Name <span class="f-label-badge badge-required">Required</span></label>
            <input type="text" name="name" id="editName" class="f-control" required>
          </div>
          <div class="f-group">
            <label class="f-label">Description <span class="f-label-badge badge-optional">Optional</span></label>
            <textarea name="description" id="editDesc" class="f-control"></textarea>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Pricing, BV &amp; Stock
          </div>
          <div class="f-row" style="margin-bottom:.85rem">
            <div class="f-group">
              <label class="f-label">MRP (₹) <span class="f-label-badge badge-required">Required</span></label>
              <input type="number" name="price" id="editPrice" class="f-control" step="0.01" min="1" required oninput="syncBv('edit')">
            </div>
            <div class="f-group">
              <label class="f-label">Business Volume <span class="f-label-badge badge-bv">BV</span></label>
              <input type="number" name="bv" id="editBv" class="f-control" step="0.01" min="0" placeholder="Same as price if blank" oninput="this.dataset.autoSync='0'">
              <div class="f-hint"><span class="f-hint-icon f-hint-g">BV</span> Base for 7-level PSB calculation.</div>
            </div>
          </div>
          <div class="f-group">
            <label class="f-label">MShop Plus Discount % <span class="f-label-badge badge-club">Club Only</span></label>
            <input type="number" name="discount_pct" id="editDiscount" class="f-control" step="0.1" min="0" max="99" placeholder="0 = no discount">
            <div class="f-hint"><span class="f-hint-icon">★</span> Only visible to active club members (2,500+ BV/month).</div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-label">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            Product Image
          </div>
          <div class="img-tabs">
            <button type="button" class="img-tab active" onclick="switchImgTab('edit','url',this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              URL Link
            </button>
            <button type="button" class="img-tab" onclick="switchImgTab('edit','file',this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Upload File
            </button>
          </div>
          <div class="img-panel active" id="edit-panel-url">
            <input type="url" name="image_url" id="editImg" class="f-control" oninput="previewImg(this,'editImgPreview')">
            <div class="img-preview-wrap" id="editImgPreview"><img src="" alt="Preview" onerror="this.parentElement.classList.remove('show')"></div>
          </div>
          <div class="img-panel" id="edit-panel-file">
            <div class="file-drop" ondragover="fileDragOver(event,this)" ondragleave="fileDragLeave(this)" ondrop="fileDrop(event,'editFileInput','editDropName','editFilePreview')">
              <input type="file" name="image_file" id="editFileInput" accept="image/*" onchange="fileChosen(this,'editDropName','editFilePreview')">
              <div class="file-drop-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#3a8a4a" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
              <div class="file-drop-text"><strong>Click to browse</strong> or drag &amp; drop<br><span style="font-size:.68rem">JPG · PNG · GIF · WEBP · AVIF · max 2 MB</span></div>
              <div class="file-drop-name" id="editDropName"></div>
            </div>
            <div class="img-preview-wrap" id="editFilePreview"><img src="" alt="Preview" onerror="this.parentElement.classList.remove('show')"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" onclick="closeModal('editModal')" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancel
          </button>
          <button type="submit" class="btn btn-gold">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v13a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/* ── Auto-sync BV = Price ── */
function syncBv(prefix) {
  var priceEl = document.getElementById(prefix + 'Price');
  var bvEl    = document.getElementById(prefix + 'Bv');
  if (bvEl && (!bvEl.value || bvEl.dataset.autoSync === '1')) {
    bvEl.value = priceEl.value;
    bvEl.dataset.autoSync = '1';
  }
  updateRatio(prefix);
}

function updateRatio(prefix) {
  // no DOM ratio bar in modal, handled in table
}

document.addEventListener('DOMContentLoaded', function() {
  ['addBv','editBv'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { this.dataset.autoSync = '0'; });
  });
});

/* ── Live search / filter ── */
function filterTable() {
  var q      = document.getElementById('prodSearch').value.toLowerCase();
  var status = document.getElementById('filterStatus').value;
  var plus   = document.getElementById('filterPlus').value;
  var rows   = document.querySelectorAll('#prodTable tbody tr:not(.empty-state-row)');
  rows.forEach(function(row) {
    var nameMatch   = !q      || row.dataset.name.includes(q);
    var statusMatch = !status || row.dataset.status === status;
    var plusMatch   = !plus   || row.dataset.plus === plus;
    row.style.display = (nameMatch && statusMatch && plusMatch) ? '' : 'none';
  });
}

/* ── Modal open/close ── */
function closeModal(id) {
  var m = document.getElementById(id);
  m.style.animation = 'fadeInBg .15s ease reverse';
  setTimeout(function(){ m.classList.remove('open'); m.style.animation = ''; }, 140);
}
document.querySelectorAll('.modal-bg').forEach(function(el) {
  el.addEventListener('click', function(e) { if (e.target === el) closeModal(el.id); });
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') document.querySelectorAll('.modal-bg.open').forEach(function(el){ closeModal(el.id); });
});

/* ── URL live preview ── */
function previewImg(input, previewId) {
  var wrap = document.getElementById(previewId);
  var img  = wrap.querySelector('img');
  var url  = input.value.trim();
  if (url) { img.src = url; wrap.classList.add('show'); }
  else     { wrap.classList.remove('show'); }
}

/* ── Tab switcher ── */
function switchImgTab(prefix, panel, btn) {
  btn.closest('.img-tabs').querySelectorAll('.img-tab').forEach(function(t){ t.classList.remove('active'); });
  btn.classList.add('active');
  ['url','file'].forEach(function(p){
    var el = document.getElementById(prefix + '-panel-' + p);
    if (el) el.classList.toggle('active', p === panel);
  });
}

/* ── File chosen ── */
function fileChosen(input, nameId, previewId) {
  var file = input.files[0]; if (!file) return;
  var nameEl = document.getElementById(nameId);
  nameEl.textContent = '📎 ' + file.name; nameEl.style.display = 'block';
  var wrap = document.getElementById(previewId);
  var reader = new FileReader();
  reader.onload = function(e){ wrap.querySelector('img').src = e.target.result; wrap.classList.add('show'); };
  reader.readAsDataURL(file);
}

/* ── Drag & drop ── */
function fileDragOver(e, drop) { e.preventDefault(); drop.classList.add('drag-over'); }
function fileDragLeave(drop)   { drop.classList.remove('drag-over'); }
function fileDrop(e, inputId, nameId, previewId) {
  e.preventDefault(); e.currentTarget.classList.remove('drag-over');
  var file = e.dataTransfer.files[0];
  if (!file || !file.type.startsWith('image/')) return;
  var input = document.getElementById(inputId);
  var dt2 = new DataTransfer(); dt2.items.add(file); input.files = dt2.files;
  fileChosen(input, nameId, previewId);
}

/* ── Open edit modal ── */
function openEditModal(p) {
  document.getElementById('editPid').value      = p.id;
  document.getElementById('editName').value     = p.name;
  document.getElementById('editPrice').value    = p.price;
  document.getElementById('editBv').value       = p.bv || p.price;
  document.getElementById('editDiscount').value = p.discount_pct || 0;
  document.getElementById('editDesc').value     = p.description || '';

  /* Reset image tabs */
  document.querySelectorAll('#editModal .img-tab').forEach(function(t,i){ t.classList.toggle('active', i===0); });
  document.getElementById('edit-panel-url').classList.add('active');
  document.getElementById('edit-panel-file').classList.remove('active');
  document.getElementById('editFileInput').value = '';
  var dn = document.getElementById('editDropName'); dn.textContent = ''; dn.style.display = 'none';
  document.getElementById('editFilePreview').classList.remove('show');

  var imgInput = document.getElementById('editImg');
  var imgPrev  = document.getElementById('editImgPreview');
  imgInput.value = p.image_url || '';
  if (p.image_url) { imgPrev.querySelector('img').src = p.image_url; imgPrev.classList.add('show'); }
  else             { imgPrev.classList.remove('show'); }

  document.getElementById('editModal').classList.add('open');
}
</script>

<?php require_once '_footer.php'; ?>