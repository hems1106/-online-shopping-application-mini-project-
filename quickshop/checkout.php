<?php
require_once 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['cart_data'])) { header("Location: index.html"); exit(); }
if (empty($_SESSION['user_id'])) { header("Location: login.php?need=checkout"); exit(); }

$cart = json_decode($_POST['cart_data'], true);
if (!is_array($cart) || !$cart) { header("Location: index.html"); exit(); }

// Never trust prices from the browser: look everything up in the database.
$lines = []; $total = 0;
$find = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
foreach ($cart as $item) {
    $qty = (int)($item['quantity'] ?? 0);
    if ($qty < 1 || $qty > 99) continue;
    $find->execute([(int)($item['id'] ?? 0)]);
    if ($p = $find->fetch(PDO::FETCH_ASSOC)) {
        $sub = $p['price'] * $qty;
        $total += $sub;
        $lines[] = ['id'=>$p['id'], 'name'=>$p['name'], 'price'=>$p['price'], 'quantity'=>$qty, 'subtotal'=>$sub];
    }
}
if (!$lines) { header("Location: index.html"); exit(); }

$pdo->beginTransaction();
$pdo->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)")->execute([$_SESSION['user_id'], $total]);
$orderId = $pdo->lastInsertId();
$ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?,?,?,?,?)");
foreach ($lines as $l) { $ins->execute([$orderId, $l['id'], $l['name'], $l['price'], $l['quantity']]); }
$pdo->commit();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        * { box-sizing:border-box; font-family:'Segoe UI',system-ui,sans-serif; }
        body { background:#f8fafc; color:#1e293b; padding:40px 16px; }
        .receipt { background:white; max-width:600px; margin:0 auto; padding:35px; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 10px 30px rgba(0,0,0,.04); }
        .badge { background:#dcfce7; color:#166534; padding:6px 16px; border-radius:30px; display:inline-block; font-weight:600; font-size:.85rem; margin-bottom:15px; }
        h1 { margin-bottom:6px; } p.sub { color:#64748b; margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        th { text-align:left; padding-bottom:10px; border-bottom:2px solid #e2e8f0; color:#64748b; font-size:.8rem; text-transform:uppercase; }
        td { padding:12px 0; border-bottom:1px solid #f1f5f9; }
        .total { display:flex; justify-content:space-between; border-top:2px dashed #cbd5e1; padding-top:18px; font-weight:700; }
        .total div { font-size:1.6rem; color:#ff4757; }
        a { color:#0f172a; font-weight:600; text-decoration:none; display:inline-block; margin-top:25px; }
    </style>
</head>
<body>
<div class="receipt">
    <div class="badge">✓ Order Placed</div>
    <h1>Order #<?= (int)$orderId ?></h1>
    <p class="sub">Thank you, <?= $h($_SESSION['username']) ?>. Here is your summary:</p>
    <table>
        <tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Subtotal</th></tr>
        <?php foreach ($lines as $l): ?>
        <tr><td><?= $h($l['name']) ?></td><td style="text-align:center"><?= (int)$l['quantity'] ?></td><td style="text-align:right">₹<?= number_format($l['subtotal'],2) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <div class="total"><span>Grand Total</span><div>₹<?= number_format($total,2) ?></div></div>
    <a href="index.html" onclick="localStorage.removeItem('quickshop_cart')">&larr; Back to Store</a>
</div>
</body>
</html>
