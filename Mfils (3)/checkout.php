<?php
/**
 * checkout.php — Mfills MShop · Full Checkout Page + Razorpay Test Mode
 */

$pageTitle = 'Checkout — MShop · Mfills';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/config.php';
startSession();

$loggedIn = isLoggedIn();
if (!$loggedIn) {
    header('Location: ' . APP_URL . '/login.php?redirect=checkout');
    exit;
}

$userId = currentUserId();
$user   = getUser($userId);

// ── Razorpay helpers ──────────────────────────────────────────
function rzpCreateOrder(int $amount): array {
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'amount'   => $amount * 100,
            'currency' => 'INR',
            'receipt'  => 'rcpt_' . time(),
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD    => RZP_KEY_ID . ':' . RZP_KEY_SECRET,
        CURLOPT_TIMEOUT    => 15,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res ?? [];
}

function rzpVerify(string $orderId, string $paymentId, string $signature): bool {
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, RZP_KEY_SECRET);
    return hash_equals($expected, $signature);
}
// ─────────────────────────────────────────────────────────────

$cartItems = [];
if (!empty($_POST['cart_data'])) {
    $cartItems = json_decode($_POST['cart_data'], true) ?: [];
    $_SESSION['checkout_cart'] = $cartItems;
} elseif (!empty($_SESSION['checkout_cart'])) {
    $cartItems = $_SESSION['checkout_cart'];
}

if (empty($cartItems)) {
    header('Location: ' . APP_URL . '/cart.php');
    exit;
}

$shopSource = $_POST['shop_source'] ?? $_SESSION['checkout_shop_source'] ?? 'mshop';
$_SESSION['checkout_shop_source'] = $shopSource;

$commRates = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];

$subtotal = 0; $totalBv = 0; $totalQty = 0;
foreach ($cartItems as $item) {
    $subtotal += ($item['price'] ?? 0) * ($item['qty'] ?? 1);
    $totalBv  += ($item['bv']   ?? 0) * ($item['qty'] ?? 1);
    $totalQty += ($item['qty']  ?? 1);
}
$delivery   = $subtotal >= 1299 ? 0 : 99;
$grandTotal = $subtotal + $delivery;

$savedAddresses = [];
if (function_exists('getUserAddresses')) {
    $savedAddresses = getUserAddresses($userId) ?: [];
}

$orderError   = '';
$orderSuccess = false;
$orderId      = null;
$placedOrders = [];
$rzpOrderId   = null;
$showRzpModal = false;

// ── Address data (reuse across both POST passes) ──────────────
$addressData = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'phone'     => trim($_POST['phone']     ?? ''),
    'address1'  => trim($_POST['address1']  ?? ''),
    'address2'  => trim($_POST['address2']  ?? ''),
    'city'      => trim($_POST['city']      ?? ''),
    'state'     => trim($_POST['state']     ?? ''),
    'pincode'   => trim($_POST['pincode']   ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    // ── Validate address ──────────────────────────────────────
    foreach (['full_name','phone','address1','city','state','pincode'] as $f) {
        if (empty($addressData[$f])) { $orderError = 'Please fill all required fields.'; break; }
    }
    if (!$orderError && strlen($addressData['pincode']) !== 6)
        $orderError = 'Please enter a valid 6-digit pincode.';
    if (!$orderError && !preg_match('/^[6-9]\d{9}$/', $addressData['phone']))
        $orderError = 'Please enter a valid 10-digit mobile number.';

    if (!$orderError) {

        // ── STEP A: Online payment — create Razorpay order first ──
        if ($paymentMethod !== 'cod' && empty($_POST['rzp_paid'])) {
            $rzp = rzpCreateOrder($grandTotal);
            if (!empty($rzp['id'])) {
                $rzpOrderId   = $rzp['id'];
                $showRzpModal = true;
                // Do NOT place order yet — wait for payment callback
            } else {
                $orderError = 'Payment gateway error. Please use COD or try again.';
            }

        // ── STEP B: Payment done — verify signature then place order ──
        } elseif (!empty($_POST['rzp_paid'])) {
            $verified = rzpVerify(
                $_POST['rzp_order_id']   ?? '',
                $_POST['rzp_payment_id'] ?? '',
                $_POST['rzp_signature']  ?? ''
            );
            if (!$verified) {
                $orderError = '⚠️ Payment verification failed. Contact support.';
            }
        }

        // ── STEP C: COD or verified online — place order ──────
        if (!$orderError && !$showRzpModal) {
            $pdo = db();
            $deliveryAddress = implode(', ', array_filter([
                $addressData['address1'], $addressData['address2'],
                $addressData['city'], $addressData['state'], $addressData['pincode'],
            ])) . ' | ' . $addressData['full_name'] . ' | ' . $addressData['phone'];

            foreach ($cartItems as $item) {
                $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
                $qty       = max(1, (int)($item['qty'] ?? 1));
                if ($productId <= 0) continue;

                try {
                    $result = purchaseProduct($userId, $productId, $qty, $shopSource);
                } catch (\Throwable $e) {
                    $result = ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
                }

                if ($result['success']) {
                    $newOrderId = $result['order_id'];
                    try {
                        $pdo->prepare(
                            'UPDATE orders SET delivery_address=?, payment_id=?, payment_method=? WHERE id=?'
                        )->execute([
                            $deliveryAddress,
                            $_POST['rzp_payment_id'] ?? null,
                            $paymentMethod,
                            $newOrderId,
                        ]);
                    } catch (\Throwable $e) {}
                    $placedOrders[] = $newOrderId;
                    if (!$orderId) $orderId = $newOrderId;
                } else {
                    $orderError .= ($orderError ? ' | ' : '') . ($result['message'] ?? "Failed #$productId");
                }
            }

            if (!empty($placedOrders)) {
                $orderSuccess = true;
                unset($_SESSION['checkout_cart']);
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<style>
:root{--gdd:#060f08;--gd:#0e2414;--gm:#1a3b22;--gl:#2a6336;--gll:#3a8a4a;--gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;--jade:#0F7B5C;--jade-l:#14A376;--coral:#E8534A;--ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;--ink:#0f1a10;--muted:#5a7a60;--cs:0 2px 18px rgba(14,36,20,.08);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Nunito',sans-serif;background:var(--ivory);color:var(--ink);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;z-index:-1;pointer-events:none;background:radial-gradient(ellipse 65% 40% at 0% 0%,rgba(26,59,34,.08) 0%,transparent 55%),radial-gradient(ellipse 50% 35% at 100% 100%,rgba(200,146,42,.05) 0%,transparent 55%),var(--ivory);}
html.no-scroll,body.no-scroll{overflow:hidden!important;height:100vh!important;}
.chk-nav{background:linear-gradient(135deg,var(--gdd) 0%,var(--gd) 50%,var(--gm) 100%);padding:.9rem 0;border-bottom:2.5px solid var(--gold);position:sticky;top:0;z-index:200;}
.chk-nav::after{content:'';position:absolute;inset:0;pointer-events:none;background-image:radial-gradient(circle,rgba(200,146,42,.06) 1px,transparent 1px);background-size:22px 22px;}
.nav-inner{max-width:1100px;margin:0 auto;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1;gap:1rem;flex-wrap:wrap;}
.nav-brand{font-family:'Cinzel',serif;font-size:1.1rem;font-weight:900;color:#fff;text-decoration:none;display:flex;align-items:center;gap:.5rem;}
.nav-brand em{color:var(--gold-l);font-style:italic;}
.nav-steps{display:flex;align-items:center;gap:.3rem;font-family:'Cinzel',serif;font-size:.68rem;font-weight:700;}
.ns{color:rgba(255,255,255,.3);display:flex;align-items:center;gap:.28rem;}
.ns.done{color:rgba(255,255,255,.55);}
.ns.active{color:var(--gold-l);}
.ns-dot{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:900;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.12);}
.ns.done .ns-dot{background:var(--jade);border-color:var(--jade-l);color:#fff;}
.ns.active .ns-dot{background:var(--gold);border-color:var(--gold-l);color:var(--gdd);}
.nd{color:rgba(255,255,255,.15);font-size:.7rem;margin:0 .1rem;}
.nav-secure{display:flex;align-items:center;gap:.4rem;font-size:.7rem;font-weight:700;font-family:'Cinzel',serif;color:rgba(200,146,42,.7);background:rgba(200,146,42,.08);border:1px solid rgba(200,146,42,.2);border-radius:20px;padding:.28rem .8rem;}
@media(max-width:640px){.nav-steps,.nav-secure{display:none;}}
.page{max-width:1100px;margin:0 auto;padding:2rem 1.5rem 6rem;}
.page-title{font-family:'DM Serif Display',serif;font-size:1.85rem;font-weight:700;color:var(--gd);margin-bottom:.3rem;display:flex;align-items:center;gap:.6rem;}
.page-sub{font-size:.8rem;color:var(--muted);margin-bottom:1.75rem;}
.back-link{display:inline-flex;align-items:center;gap:.3rem;color:var(--jade);font-size:.76rem;font-weight:800;text-decoration:none;font-family:'Cinzel',serif;background:rgba(15,123,92,.06);border:1px solid rgba(15,123,92,.15);border-radius:20px;padding:.22rem .65rem;transition:all .2s;margin-bottom:1rem;}
.back-link:hover{background:rgba(15,123,92,.12);transform:translateX(-2px);}
.two-col{display:grid;grid-template-columns:1fr 360px;gap:2rem;align-items:start;}
@media(max-width:900px){.two-col{grid-template-columns:1fr;}}
.sec-card{background:#fff;border:1.5px solid var(--ivory-dd);border-radius:18px;box-shadow:var(--cs);overflow:hidden;margin-bottom:1.25rem;}
.sec-head{padding:1rem 1.4rem .85rem;border-bottom:1.5px solid var(--ivory-dd);background:linear-gradient(90deg,rgba(26,59,34,.03),transparent);display:flex;align-items:center;justify-content:space-between;}
.sec-title{font-family:'Cinzel',serif;font-size:.82rem;font-weight:900;color:var(--gd);display:flex;align-items:center;gap:.5rem;}
.sec-badge{font-size:.62rem;font-weight:700;background:rgba(26,59,34,.08);color:var(--gd);padding:.1rem .45rem;border-radius:20px;font-family:'Nunito',sans-serif;}
.sec-body{padding:1.3rem 1.4rem;}
.saved-addr-row{display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.1rem;}
.saved-addr-card{border:1.5px solid var(--ivory-dd);border-radius:12px;padding:.8rem 1rem;cursor:pointer;transition:all .22s;flex:1;min-width:200px;background:var(--ivory);font-family:'Nunito',sans-serif;}
.saved-addr-card:hover{border-color:var(--gl);}
.saved-addr-card.selected{border-color:var(--gd);background:rgba(26,59,34,.04);box-shadow:0 0 0 2px rgba(26,59,34,.12);}
.saved-addr-name{font-size:.8rem;font-weight:800;color:var(--ink);margin-bottom:.2rem;}
.saved-addr-text{font-size:.72rem;color:var(--muted);line-height:1.5;}
.add-new-addr{border:1.5px dashed var(--ivory-dd);border-radius:12px;padding:.8rem 1rem;cursor:pointer;transition:all .22s;display:flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:700;color:var(--muted);font-family:'Cinzel',serif;background:transparent;}
.add-new-addr:hover{border-color:var(--gl);color:var(--gd);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
@media(max-width:560px){.form-grid{grid-template-columns:1fr;}}
.fg{display:flex;flex-direction:column;gap:.35rem;}
.fg.span2{grid-column:1/-1;}
label{font-size:.72rem;font-weight:800;color:var(--gd);font-family:'Cinzel',serif;text-transform:uppercase;letter-spacing:.06em;}
label .req{color:var(--coral);margin-left:.15rem;}
.fi{width:100%;padding:.65rem .9rem;border:1.5px solid var(--ivory-dd);border-radius:10px;background:#fff;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--ink);outline:none;transition:border-color .2s,box-shadow .2s;}
.fi:focus{border-color:var(--gll);box-shadow:0 0 0 3px rgba(58,138,74,.1);}
.fi.err{border-color:var(--coral);box-shadow:0 0 0 3px rgba(232,83,74,.1);}
.fi-hint{font-size:.65rem;color:var(--muted);font-family:'Nunito',sans-serif;}
select.fi{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%235a7a60' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .9rem center;padding-right:2.5rem;}
.pay-options{display:flex;flex-direction:column;gap:.65rem;}
.pay-opt{border:1.5px solid var(--ivory-dd);border-radius:12px;padding:.9rem 1.1rem;cursor:pointer;display:flex;align-items:center;gap:.85rem;transition:all .22s;background:var(--ivory);position:relative;overflow:hidden;}
.pay-opt:hover{border-color:var(--gl);}
.pay-opt.selected{border-color:var(--gd);background:rgba(26,59,34,.04);box-shadow:0 0 0 2px rgba(26,59,34,.12);}
.pay-radio-dot{width:18px;height:18px;border-radius:50%;border:2px solid var(--ivory-dd);flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.pay-opt.selected .pay-radio-dot{border-color:var(--gd);background:var(--gd);}
.pay-opt.selected .pay-radio-dot::after{content:'';width:7px;height:7px;border-radius:50%;background:#fff;display:block;}
.pay-icon{font-size:1.4rem;flex-shrink:0;width:36px;text-align:center;}
.pay-info{flex:1;}
.pay-name{font-family:'Cinzel',serif;font-size:.82rem;font-weight:800;color:var(--ink);}
.pay-desc{font-size:.68rem;color:var(--muted);font-family:'Nunito',sans-serif;margin-top:.1rem;}
.pay-badge{font-size:.6rem;font-weight:800;font-family:'Cinzel',serif;padding:.12rem .45rem;border-radius:20px;margin-left:auto;flex-shrink:0;}
.pb-pop{background:rgba(15,123,92,.1);color:var(--jade);}
.pb-fast{background:rgba(200,146,42,.1);color:var(--gold-d);}
.upi-expand{display:none;margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--ivory-dd);}
.upi-expand.show{display:block;}
.summary{background:linear-gradient(160deg,#0c1e11 0%,#091508 65%,#040e07 100%);border-radius:20px;border:1px solid rgba(200,146,42,.14);box-shadow:0 20px 60px rgba(0,0,0,.28);position:sticky;top:80px;overflow:hidden;}
.sum-head{padding:1.3rem 1.5rem 1rem;border-bottom:1px solid rgba(200,146,42,.1);background:rgba(200,146,42,.04);}
.sum-title{font-family:'Cinzel',serif;font-size:.95rem;font-weight:900;color:#fff;display:flex;align-items:center;gap:.45rem;}
.sum-subtitle{font-size:.67rem;color:rgba(200,146,42,.5);font-family:'Nunito',sans-serif;margin-top:.18rem;}
.sum-body{padding:1.2rem 1.5rem;}
.mini-cart{margin-bottom:.9rem;display:flex;flex-direction:column;gap:.5rem;max-height:200px;overflow-y:auto;padding-right:.2rem;}
.mini-cart::-webkit-scrollbar{width:3px;}
.mini-cart::-webkit-scrollbar-thumb{background:rgba(200,146,42,.2);border-radius:10px;}
.mini-item{display:flex;align-items:center;gap:.65rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:.55rem .7rem;}
.mini-img{width:38px;height:38px;border-radius:8px;object-fit:cover;background:rgba(255,255,255,.05);flex-shrink:0;}
.mini-info{flex:1;min-width:0;}
.mini-name{font-size:.72rem;font-weight:700;color:rgba(255,255,255,.8);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:'Nunito',sans-serif;}
.mini-meta{font-size:.62rem;color:rgba(200,146,42,.55);font-family:'Nunito',sans-serif;}
.mini-price{font-size:.78rem;font-weight:800;color:var(--gold-l);font-family:'Cinzel',serif;white-space:nowrap;flex-shrink:0;}
.sum-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(200,146,42,.22),transparent);margin:.65rem 0;}
.sum-row{display:flex;justify-content:space-between;align-items:center;padding:.38rem 0;border-bottom:1px solid rgba(255,255,255,.04);}
.sum-row:last-of-type{border-bottom:none;}
.sl{font-size:.72rem;color:rgba(255,255,255,.38);font-weight:600;}
.sv{font-size:.78rem;font-weight:800;color:rgba(255,255,255,.72);font-family:'Cinzel',serif;}
.sum-row.free .sv{color:var(--jade-l);}
.sum-total-row{display:flex;justify-content:space-between;align-items:baseline;margin:.5rem 0 1.1rem;}
.stl{font-size:.67rem;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.1em;font-weight:700;}
.stv{font-family:'DM Serif Display',serif;font-size:1.7rem;font-weight:800;color:#fff;}
.stv span{color:var(--gold-l);}
.place-btn{width:100%;padding:1rem 1.5rem;background:linear-gradient(135deg,#B88018 0%,#e0aa40 50%,#B88018 100%);background-size:200% 100%;border:none;border-radius:13px;font-family:'Cinzel',serif;font-size:.9rem;font-weight:900;color:#0a1a0f;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background-position .4s,transform .2s,box-shadow .2s;box-shadow:0 5px 22px rgba(200,146,42,.35);letter-spacing:.03em;margin-bottom:.55rem;}
.place-btn:hover{background-position:100% 50%;transform:translateY(-2px);box-shadow:0 10px 36px rgba(200,146,42,.5);}
.place-btn:active{transform:translateY(0);}
.place-btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.trust{display:flex;flex-wrap:wrap;gap:.38rem;margin-top:.85rem;padding-top:.85rem;border-top:1px solid rgba(255,255,255,.05);}
.tb{display:flex;align-items:center;gap:.22rem;font-size:.6rem;font-weight:700;color:rgba(255,255,255,.28);font-family:'Nunito',sans-serif;flex:1;min-width:70px;}
.pmts{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.75rem;justify-content:center;}
.pmt{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:.22rem .5rem;font-size:.58rem;font-weight:800;color:rgba(255,255,255,.3);font-family:'Cinzel',serif;letter-spacing:.04em;}
.alert{padding:.9rem 1.1rem;border-radius:12px;font-size:.84rem;font-weight:700;margin-bottom:1.25rem;display:flex;align-items:center;gap:.6rem;}
.alert-danger{background:rgba(232,83,74,.1);color:#9A1A09;border:1px solid rgba(232,83,74,.25);}
.success-overlay{position:fixed;inset:0;z-index:9999;background:rgba(4,14,7,.95);backdrop-filter:blur(20px);display:flex;align-items:center;justify-content:center;padding:1rem;width:100vw;height:100vh;}
.success-card{background:linear-gradient(160deg,#0d2016,#060f08);border:1.5px solid rgba(200,146,42,.25);border-radius:24px;padding:2.5rem 2rem;text-align:center;max-width:440px;width:100%;box-shadow:0 40px 100px rgba(0,0,0,.7);animation:cardIn .5s cubic-bezier(.22,.68,0,1.3) both;max-height:90vh;overflow-y:auto;margin:auto;flex-shrink:0;}
@keyframes cardIn{from{opacity:0;transform:scale(.88) translateY(20px);}to{opacity:1;transform:scale(1) translateY(0);}}
.success-ring{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--jade),var(--jade-l));display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1rem;animation:ringPop .6s cubic-bezier(.34,1.6,.64,1) .1s both;}
@keyframes ringPop{0%{transform:scale(0);}70%{transform:scale(1.12);}100%{transform:scale(1);}}
.success-title{font-family:'Cinzel',serif;font-size:1.25rem;font-weight:900;color:#fff;margin-bottom:.35rem;}
.success-sub{font-size:.8rem;color:rgba(255,255,255,.45);font-family:'Nunito',sans-serif;line-height:1.6;margin-bottom:1rem;}
.order-id-box{background:rgba(200,146,42,.08);border:1px solid rgba(200,146,42,.2);border-radius:12px;padding:.65rem 1rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;}
.oid-label{font-size:.62rem;font-weight:700;color:rgba(200,146,42,.6);font-family:'Cinzel',serif;text-transform:uppercase;letter-spacing:.08em;}
.oid-val{font-family:'Cinzel',serif;font-size:.9rem;font-weight:900;color:var(--gold-l);}
.success-steps{text-align:left;margin-bottom:1.1rem;}
.ss-item{display:flex;align-items:center;gap:.65rem;padding:.38rem 0;border-bottom:1px solid rgba(255,255,255,.05);}
.ss-item:last-child{border-bottom:none;}
.ss-icon{font-size:1rem;flex-shrink:0;width:24px;text-align:center;}
.ss-text{font-size:.72rem;color:rgba(255,255,255,.45);font-family:'Nunito',sans-serif;line-height:1.4;}
.ss-text strong{color:rgba(255,255,255,.7);display:block;font-weight:800;}
.success-btn{width:100%;padding:.8rem;background:linear-gradient(135deg,#B88018,#e0aa40,#B88018);background-size:200% 100%;border:none;border-radius:12px;font-family:'Cinzel',serif;font-size:.85rem;font-weight:900;color:#0a1a0f;cursor:pointer;transition:all .3s;text-decoration:none;display:block;margin-bottom:.4rem;}
.success-btn:hover{background-position:100% 50%;}
.success-btn-sec{width:100%;padding:.65rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;font-family:'Cinzel',serif;font-size:.78rem;font-weight:700;color:rgba(255,255,255,.5);cursor:pointer;text-decoration:none;display:block;transition:all .2s;}
.success-btn-sec:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);}
.spinner{display:none;width:20px;height:20px;border:2.5px solid rgba(10,26,15,.3);border-top-color:var(--gd);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:640px){.page-title{font-size:1.5rem;}.sec-body{padding:1rem;}.form-grid{grid-template-columns:1fr;}}

/* ── Razorpay loading overlay ── */
.rzp-loading{position:fixed;inset:0;z-index:8888;background:rgba(4,14,7,.85);backdrop-filter:blur(12px);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1rem;}
.rzp-loading-ring{width:52px;height:52px;border:3px solid rgba(200,146,42,.2);border-top-color:var(--gold-l);border-radius:50%;animation:spin .8s linear infinite;}
.rzp-loading-text{font-family:'Cinzel',serif;font-size:.82rem;font-weight:700;color:rgba(200,146,42,.8);}
</style>

<?php if ($orderSuccess): ?>
<script>
document.documentElement.classList.add('no-scroll');
document.body.classList.add('no-scroll');
window.scrollTo(0,0);
try{localStorage.removeItem('mfills_cart_auth');localStorage.removeItem('mfills_gc');}catch(e){}
</script>
<div class="success-overlay">
  <div class="success-card">
    <div class="success-ring">✓</div>
    <div class="success-title">Order Placed! 🎉</div>
    <div class="success-sub">Your order has been confirmed and is being processed.<br>You'll receive updates on your registered mobile.</div>
    <div class="order-id-box">
      <span class="oid-label"><?= count($placedOrders)>1?count($placedOrders).' Orders Placed':'Order ID' ?></span>
      <span class="oid-val"><?= count($placedOrders)>1?'#'.implode(', #',$placedOrders):'#'.htmlspecialchars($orderId) ?></span>
    </div>
    <div class="success-steps">
      <div class="ss-item"><span class="ss-icon">📦</span><div class="ss-text"><strong>Order Confirmed</strong>Processing will begin shortly.</div></div>
      <div class="ss-item"><span class="ss-icon">🚚</span><div class="ss-text"><strong>Delivery in 3–7 days</strong>Tracking details via SMS/WhatsApp.</div></div>
      <div class="ss-item"><span class="ss-icon">📊</span><div class="ss-text"><strong>BV Credited</strong>Business Volume reflected in your dashboard.</div></div>
    </div>
    <a href="<?= APP_URL ?>/dashboard.php" class="success-btn">📊 Go to Dashboard</a>
    <a href="<?= APP_URL ?>/shop.php" class="success-btn-sec">🛍️ Continue Shopping</a>
  </div>
</div>
<?php endif; ?>

<!-- Razorpay loading screen (shown while modal opens) -->
<?php if ($showRzpModal): ?>
<div class="rzp-loading" id="rzpLoading">
  <div class="rzp-loading-ring"></div>
  <div class="rzp-loading-text">Opening Payment Gateway…</div>
</div>
<?php endif; ?>

<div class="chk-nav">
  <div class="nav-inner">
    <a href="shop.php" class="nav-brand">🛒 <em>MShop</em></a>
    <div class="nav-steps">
      <div class="ns done"><div class="ns-dot">✓</div>Shop</div><div class="nd">›</div>
      <div class="ns done"><div class="ns-dot">✓</div>Cart</div><div class="nd">›</div>
      <div class="ns active"><div class="ns-dot">3</div>Checkout</div><div class="nd">›</div>
      <div class="ns"><div class="ns-dot">4</div>Confirm</div>
    </div>
    <div class="nav-secure">🔒 SSL Secured</div>
  </div>
</div>

<div class="page">
  <a href="cart.php" class="back-link">← Back to Cart</a>
  <h1 class="page-title">🧾 Checkout</h1>
  <div class="page-sub">Review your order and complete payment · All fields marked <span style="color:var(--coral)">*</span> are required</div>

  <?php if ($orderError): ?>
  <div class="alert alert-danger">⚠️ <?= htmlspecialchars($orderError) ?></div>
  <?php endif; ?>

  <form method="POST" id="chkForm" onsubmit="return validateForm()">
    <input type="hidden" name="place_order"   value="1">
    <input type="hidden" name="shop_source"   value="<?= htmlspecialchars($shopSource) ?>">
    <input type="hidden" name="cart_data"     value="<?= htmlspecialchars(json_encode($cartItems)) ?>">
    <!-- Razorpay hidden fields — filled by JS after payment -->
    <input type="hidden" name="rzp_paid"       id="rzpPaid"      value="">
    <input type="hidden" name="rzp_payment_id" id="rzpPaymentId" value="">
    <input type="hidden" name="rzp_order_id"   id="rzpOrderIdF"  value="">
    <input type="hidden" name="rzp_signature"  id="rzpSignature" value="">

    <div class="two-col">
      <div>
        <!-- DELIVERY ADDRESS -->
        <div class="sec-card">
          <div class="sec-head"><div class="sec-title">📍 Delivery Address <span class="sec-badge">Step 1 of 2</span></div></div>
          <div class="sec-body">
            <?php if (!empty($savedAddresses)): ?>
            <div class="saved-addr-row" id="savedAddrRow">
              <?php foreach ($savedAddresses as $i=>$addr): ?>
              <div class="saved-addr-card <?= $i===0?'selected':'' ?>" onclick="selectAddr(this,<?= $i ?>)">
                <div class="saved-addr-name"><?= htmlspecialchars($addr['full_name']??'') ?></div>
                <div class="saved-addr-text"><?= htmlspecialchars($addr['address1']??'') ?><?= !empty($addr['address2'])?', '.htmlspecialchars($addr['address2']):'' ?><br><?= htmlspecialchars($addr['city']??'') ?>, <?= htmlspecialchars($addr['state']??'') ?> — <?= htmlspecialchars($addr['pincode']??'') ?></div>
              </div>
              <?php endforeach; ?>
              <button type="button" class="add-new-addr" onclick="showNewAddrForm()">➕ Add New Address</button>
            </div>
            <?php endif; ?>
            <div id="addrFormWrap" <?= !empty($savedAddresses)?'style="display:none"':'' ?>>
              <div class="form-grid" style="margin-bottom:.9rem">
                <div class="fg"><label>Full Name <span class="req">*</span></label><input class="fi" type="text" name="full_name" id="f_name" value="<?= htmlspecialchars($addressData['full_name'] ?: ($user['full_name']??'')) ?>" placeholder="As on ID / delivery"></div>
                <div class="fg"><label>Mobile Number <span class="req">*</span></label><input class="fi" type="tel" name="phone" id="f_phone" value="<?= htmlspecialchars($addressData['phone'] ?: ($user['phone']??'')) ?>" placeholder="10-digit mobile" maxlength="10"></div>
              </div>
              <div class="form-grid" style="margin-bottom:.9rem">
                <div class="fg span2"><label>Address Line 1 <span class="req">*</span></label><input class="fi" type="text" name="address1" id="f_addr1" value="<?= htmlspecialchars($addressData['address1']) ?>" placeholder="House / Flat No., Street, Colony"></div>
                <div class="fg span2"><label>Address Line 2</label><input class="fi" type="text" name="address2" id="f_addr2" value="<?= htmlspecialchars($addressData['address2']) ?>" placeholder="Landmark, Area (optional)"></div>
              </div>
              <div class="form-grid" style="margin-bottom:.9rem">
                <div class="fg"><label>City / District <span class="req">*</span></label><input class="fi" type="text" name="city" id="f_city" value="<?= htmlspecialchars($addressData['city']) ?>" placeholder="e.g. Mumbai"></div>
                <div class="fg"><label>Pincode <span class="req">*</span></label><input class="fi" type="text" name="pincode" id="f_pin" value="<?= htmlspecialchars($addressData['pincode']) ?>" placeholder="6-digit PIN" maxlength="6" oninput="fetchPincode(this.value)"><span class="fi-hint" id="pinHint"></span></div>
              </div>
              <div class="form-grid">
                <div class="fg"><label>State <span class="req">*</span></label>
                  <select class="fi" name="state" id="f_state">
                    <option value="">Select State</option>
                    <?php
                    $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi','Jammu & Kashmir','Ladakh','Puducherry','Chandigarh'];
                    foreach ($states as $st) {
                        $sel = $addressData['state'] === $st ? ' selected' : '';
                        echo '<option'.$sel.'>'.htmlspecialchars($st).'</option>';
                    }
                    ?>
                  </select>
                </div>
                <div class="fg"><label>Delivery Note</label><input class="fi" type="text" name="delivery_note" id="f_note" placeholder="e.g. Leave at door"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- PAYMENT METHOD -->
        <div class="sec-card">
          <div class="sec-head"><div class="sec-title">💳 Payment Method <span class="sec-badge">Step 2 of 2</span></div></div>
          <div class="sec-body">
            <input type="hidden" name="payment_method" id="paymentInput" value="<?= htmlspecialchars($_POST['payment_method'] ?? 'cod') ?>">
            <div class="pay-options">
              <div class="pay-opt <?= ($_POST['payment_method']??'cod')==='upi'?'selected':'' ?>" onclick="selectPay('upi',this)">
                <div class="pay-radio-dot"></div>
                <div class="pay-icon">📱</div>
                <div class="pay-info">
                  <div class="pay-name">UPI / QR Code</div>
                  <div class="pay-desc">GPay, PhonePe, Paytm, BHIM</div>
                  <div class="upi-expand <?= ($_POST['payment_method']??'')==='upi'?'show':'' ?>" id="upiExpand">
                    <input class="fi" type="text" name="upi_id" placeholder="yourname@upi" style="margin-top:.1rem">
                  </div>
                </div>
                <span class="pay-badge pb-pop">Instant</span>
              </div>
              <div class="pay-opt <?= ($_POST['payment_method']??'cod')==='netbank'?'selected':'' ?>" onclick="selectPay('netbank',this)">
                <div class="pay-radio-dot"></div><div class="pay-icon">🏦</div>
                <div class="pay-info"><div class="pay-name">Net Banking</div><div class="pay-desc">All major Indian banks</div></div>
                <span class="pay-badge pb-fast">Secure</span>
              </div>
              <div class="pay-opt <?= ($_POST['payment_method']??'cod')==='card'?'selected':'' ?>" onclick="selectPay('card',this)">
                <div class="pay-radio-dot"></div><div class="pay-icon">💳</div>
                <div class="pay-info"><div class="pay-name">Credit / Debit Card</div><div class="pay-desc">Visa, Mastercard, RuPay — 0% surcharge</div></div>
              </div>
              <div class="pay-opt <?= ($_POST['payment_method']??'cod')==='emi'?'selected':'' ?>" onclick="selectPay('emi',this)">
                <div class="pay-radio-dot"></div><div class="pay-icon">📅</div>
                <div class="pay-info"><div class="pay-name">EMI</div><div class="pay-desc">No-cost EMI — 3 / 6 / 12 months</div></div>
              </div>
              <div class="pay-opt <?= ($_POST['payment_method']??'cod')==='cod'?'selected':'' ?>" onclick="selectPay('cod',this)">
                <div class="pay-radio-dot"></div><div class="pay-icon">💵</div>
                <div class="pay-info"><div class="pay-name">Cash on Delivery</div><div class="pay-desc">Pay when order arrives · ₹0 extra</div></div>
                <span class="pay-badge pb-pop">Popular</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ORDER SUMMARY -->
      <div class="summary">
        <div class="sum-head">
          <div class="sum-title">📋 Order Summary</div>
          <div class="sum-subtitle"><?= $totalQty ?> item<?= $totalQty!==1?'s':'' ?></div>
        </div>
        <div class="sum-body">
          <div class="mini-cart">
            <?php foreach($cartItems as $item): ?>
            <div class="mini-item">
              <img class="mini-img" src="<?= htmlspecialchars($item['image_url']??'') ?>" onerror="this.style.opacity='.3'">
              <div class="mini-info">
                <div class="mini-name"><?= htmlspecialchars($item['name']??'') ?></div>
                <div class="mini-meta">Qty <?= (int)($item['qty']??1) ?></div>
              </div>
              <div class="mini-price">₹<?= number_format(($item['price']??0)*($item['qty']??1),0) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="sum-divider"></div>
          <div class="sum-row"><span class="sl">Subtotal</span><span class="sv">₹<?= number_format($subtotal,0) ?></span></div>
          <div class="sum-row <?= $delivery===0?'free':'' ?>"><span class="sl">Delivery</span><span class="sv"><?= $delivery===0?'🎉 FREE':'₹'.number_format($delivery,0) ?></span></div>
          <div class="sum-divider"></div>
          <div class="sum-total-row"><span class="stl">Total Payable</span><div class="stv"><span>₹</span><?= number_format($grandTotal,0) ?></div></div>

          <button type="submit" class="place-btn" id="placeBtn">
            <span id="placeBtnTxt">🔒 Place Order — ₹<?= number_format($grandTotal,0) ?></span>
            <div class="spinner" id="placeSpinner"></div>
          </button>
          <div class="trust">
            <div class="tb">🔒 SSL Encrypted</div><div class="tb">🚚 3–7 Day Delivery</div>
            <div class="tb">↩️ Easy Returns</div><div class="tb">✅ 100% Genuine</div>
          </div>
          <div class="pmts">
            <span class="pmt">UPI</span><span class="pmt">VISA</span><span class="pmt">MASTER</span>
            <span class="pmt">RUPAY</span><span class="pmt">NETBANK</span><span class="pmt">COD</span>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- ── Razorpay checkout.js + auto-open modal ── -->
<?php if ($showRzpModal && $rzpOrderId): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
window.addEventListener('load', function() {
    // Hide loading overlay once Razorpay script is ready
    setTimeout(function(){
        var lo = document.getElementById('rzpLoading');
        if(lo) lo.style.display='none';
    }, 800);

    var options = {
        key:         '<?= defined('RZP_KEY_ID') ? RZP_KEY_ID : '' ?>',
        amount:      <?= (int)($grandTotal * 100) ?>,
        currency:    'INR',
        name:        'Mfills MShop',
        description: 'Order Payment — <?= $totalQty ?> item<?= $totalQty!==1?"s":"" ?>',
        image:       '<?= APP_URL ?>/assets/logo.png',
        order_id:    '<?= htmlspecialchars($rzpOrderId) ?>',
        prefill: {
            name:    '<?= htmlspecialchars($addressData['full_name']) ?>',
            contact: '<?= htmlspecialchars($addressData['phone']) ?>',
            email:   '<?= htmlspecialchars($user['email'] ?? '') ?>',
        },
        notes: {
            address: '<?= htmlspecialchars($addressData['address1'].', '.$addressData['city']) ?>',
        },
        theme: {
            color: '#c8922a',
            hide_topbar: false,
        },
        handler: function(response) {
            // Fill hidden fields and auto-submit
            document.getElementById('rzpPaid').value       = '1';
            document.getElementById('rzpPaymentId').value  = response.razorpay_payment_id;
            document.getElementById('rzpOrderIdF').value   = response.razorpay_order_id;
            document.getElementById('rzpSignature').value  = response.razorpay_signature;
            // Show loading while form submits
            var btn = document.getElementById('placeBtn');
            var txt = document.getElementById('placeBtnTxt');
            var sp  = document.getElementById('placeSpinner');
            if(btn) btn.disabled = true;
            if(txt) txt.textContent = 'Confirming Order…';
            if(sp)  sp.style.display = 'block';
            document.getElementById('chkForm').submit();
        },
        modal: {
            backdropclose: false,
            escape:        false,
            ondismiss: function() {
                var lo = document.getElementById('rzpLoading');
                if(lo) lo.style.display = 'none';
                alert('Payment cancelled. Please try again or choose Cash on Delivery.');
            }
        }
    };

    var rzp = new Razorpay(options);
    rzp.on('payment.failed', function(resp){
        alert('Payment failed: ' + (resp.error.description || 'Unknown error') + '\n\nTry again or use COD.');
    });
    rzp.open();
});
</script>
<?php endif; ?>

<script>
function selectPay(val,el){
    document.querySelectorAll('.pay-opt').forEach(function(o){o.classList.remove('selected');});
    el.classList.add('selected');
    document.getElementById('paymentInput').value=val;
    var ue=document.getElementById('upiExpand');
    if(ue) ue.classList.toggle('show',val==='upi');
}

var savedAddrs=<?= json_encode($savedAddresses) ?>;

function selectAddr(el,idx){
    document.querySelectorAll('.saved-addr-card').forEach(function(c){c.classList.remove('selected');});
    el.classList.add('selected');
    var a=savedAddrs[idx]; if(!a) return;
    var map={f_name:'full_name',f_phone:'phone',f_addr1:'address1',f_addr2:'address2',f_city:'city',f_pin:'pincode',f_state:'state'};
    Object.keys(map).forEach(function(id){
        var el2=document.getElementById(id);
        if(el2) el2.value=a[map[id]]||'';
    });
    var fw=document.getElementById('addrFormWrap');
    if(fw) fw.style.display='block';
}

function showNewAddrForm(){
    var fw=document.getElementById('addrFormWrap');
    if(fw){fw.style.display='block';fw.scrollIntoView({behavior:'smooth',block:'start'});}
    document.querySelectorAll('.saved-addr-card').forEach(function(c){c.classList.remove('selected');});
    ['f_name','f_phone','f_addr1','f_addr2','f_city','f_pin','f_note'].forEach(function(id){
        var el=document.getElementById(id); if(el) el.value='';
    });
    var st=document.getElementById('f_state'); if(st) st.value='';
}

var pincodeTimer=null;
function fetchPincode(val){
    var hint=document.getElementById('pinHint');
    if(val.length!==6||!/^\d{6}$/.test(val)){if(hint)hint.textContent='';return;}
    if(pincodeTimer)clearTimeout(pincodeTimer);
    if(hint)hint.textContent='🔍 Looking up…';
    pincodeTimer=setTimeout(function(){
        fetch('https://api.postalpincode.in/pincode/'+val)
        .then(function(r){return r.json();})
        .then(function(data){
            if(data&&data[0]&&data[0].Status==='Success'){
                var po=data[0].PostOffice[0];
                var cityEl=document.getElementById('f_city');
                var stateEl=document.getElementById('f_state');
                if(cityEl&&!cityEl.value) cityEl.value=po.District||po.Name||'';
                if(stateEl){
                    for(var i=0;i<stateEl.options.length;i++){
                        if(stateEl.options[i].value.toLowerCase()===po.State.toLowerCase()){
                            stateEl.value=stateEl.options[i].value; break;
                        }
                    }
                }
                if(hint) hint.textContent='✅ '+(po.Name||'')+', '+(po.District||'')+', '+(po.State||'');
            } else {
                if(hint) hint.textContent='⚠️ Pincode not found';
            }
        }).catch(function(){if(hint)hint.textContent='';});
    },500);
}

function validateForm(){
    var required=[
        {id:'f_name', msg:'Please enter your full name'},
        {id:'f_phone',msg:'Please enter your mobile number'},
        {id:'f_addr1',msg:'Please enter your address'},
        {id:'f_city', msg:'Please enter your city'},
        {id:'f_pin',  msg:'Please enter your pincode'},
        {id:'f_state',msg:'Please select your state'},
    ];
    var ok=true;
    required.forEach(function(r){
        var el=document.getElementById(r.id);
        if(!el||!el.value.trim()){
            if(el) el.classList.add('err');
            if(ok){alert(r.msg); if(el) el.focus();}
            ok=false;
        } else {
            if(el) el.classList.remove('err');
        }
    });
    if(!ok) return false;
    var ph=document.getElementById('f_phone');
    if(ph&&!/^[6-9]\d{9}$/.test(ph.value.trim())){
        ph.classList.add('err'); ph.focus();
        alert('Please enter a valid 10-digit mobile number starting with 6–9');
        return false;
    }
    var pin=document.getElementById('f_pin');
    if(pin&&!/^\d{6}$/.test(pin.value.trim())){
        pin.classList.add('err'); pin.focus();
        alert('Please enter a valid 6-digit pincode');
        return false;
    }
    // Show spinner
    var btn=document.getElementById('placeBtn');
    var txt=document.getElementById('placeBtnTxt');
    var sp=document.getElementById('placeSpinner');
    var pm=document.getElementById('paymentInput').value;
    if(btn) btn.disabled=true;
    if(txt) txt.textContent = pm==='cod' ? 'Placing Order…' : 'Opening Payment…';
    if(sp)  sp.style.display='block';
    return true;
}

document.querySelectorAll('.fi').forEach(function(el){
    el.addEventListener('input',function(){el.classList.remove('err');});
});

document.addEventListener('DOMContentLoaded',function(){
    if(savedAddrs&&savedAddrs.length>0){
        var firstCard=document.querySelector('.saved-addr-card');
        if(firstCard) selectAddr(firstCard,0);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>