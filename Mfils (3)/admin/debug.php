<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "Step 1: PHP OK\n";

require_once __DIR__ . '/config.php';
echo "Step 2: admin/config.php OK\n";

echo "Step 3: Functions\n";
echo "  requireAdmin: ".(function_exists('requireAdmin')?'YES':'NO')."\n";
echo "  db: ".(function_exists('db')?'YES':'NO')."\n";
echo "  inr: ".(function_exists('inr')?'YES':'NO')."\n";
echo "  e: ".(function_exists('e')?'YES':'NO')."\n";

echo "Step 4: DB Tables\n";
$pdo = db();
echo "  users: ".$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()."\n";
echo "  orders: ".$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn()."\n";
echo "  products: ".$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()."\n";

try { echo "  withdrawals: ".$pdo->query("SELECT COUNT(*) FROM withdrawals")->fetchColumn()."\n"; }
catch(Exception $e) { echo "  withdrawals ERROR: ".$e->getMessage()."\n"; }

try { echo "  commissions: ".$pdo->query("SELECT COUNT(*) FROM commissions")->fetchColumn()."\n"; }
catch(Exception $e) { echo "  commissions ERROR: ".$e->getMessage()."\n"; }

echo "Step 5: Files\n";
echo "  _layout.php: ".(file_exists(__DIR__.'/_layout.php')?'EXISTS':'MISSING')."\n";
echo "  _footer.php: ".(file_exists(__DIR__.'/_footer.php')?'EXISTS':'MISSING')."\n";

echo "Step 6: Session\n";
adminStartSession();
echo "  admin_logged_in: ".($_SESSION['admin_logged_in']??'NOT SET')."\n";

echo "\nALL DONE!\n</pre>";