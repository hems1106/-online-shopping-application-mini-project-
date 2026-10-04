<?php
require_once 'db.php';
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?need=admin");
    exit();
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { die("Invalid request."); }

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)$_POST['id'];
        $st = $pdo->prepare("SELECT image_url FROM products WHERE id=?");
        $st->execute([$id]);
        $img = $st->fetchColumn();
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        if ($img && strpos($img, 'uploads/') === 0 && is_file($img)) { @unlink($img); }
        $message = "Product deleted.";
    } else {
        $code = trim($_POST['product_code']);
        $name = trim($_POST['name']);
        $price = (float)$_POST['price'];
        $mrp = (float)($_POST['mrp'] ?? 0); if ($mrp < $price) $mrp = $price;
        $cat = trim($_POST['category'] ?? 'General');

        if ($price <= 0 || $code === '' || $name === '') {
            $message = "Please fill all fields with valid values.";
        } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['product_image']['tmp_name']);
            if (!isset($allowed[$mime])) {
                $message = "Only JPG, PNG, GIF or WEBP images are allowed.";
            } elseif ($_FILES['product_image']['size'] > 3 * 1024 * 1024) {
                $message = "Image must be under 3 MB.";
            } else {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }
                $path = $targetDir . bin2hex(random_bytes(8)) . "." . $allowed[$mime];
                if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $path)) {
                    try {
                        $pdo->prepare("INSERT INTO products (product_code, name, price, mrp, category, image_url) VALUES (?, ?, ?, ?, ?, ?)")
                            ->execute([$code, $name, $price, $mrp, $cat, $path]);
                        $message = "Product added successfully!";
                    } catch (PDOException $e) {
                        @unlink($path);
                        $message = "Product code already exists.";
                    }
                } else { $message = "Error uploading the file."; }
            }
        } else {
            $message = "Please upload a valid product image.";
        }
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$orders = $pdo->query("SELECT o.id, o.total, o.created_at, u.username FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickShop | Admin Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-wrapper { max-width: 900px; margin: 40px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .admin-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .admin-form .full { grid-column: span 2; }
        .admin-form input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; }
        .admin-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .admin-table th, .admin-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .admin-table img { width: 50px; height: 50px; object-fit: contain; }
        .del-btn { background:#ff4757; color:#fff; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; }
        @media (max-width:600px){ .admin-form{grid-template-columns:1fr} .admin-form .full{grid-column:span 1} .admin-wrapper{overflow-x:auto} }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <h2>Product Administration Panel</h2>
        <?php if($message): ?><p style="color:#2ed573;margin:10px 0;"><?= $h($message) ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
            <div class="full"><h3>Add New Product</h3></div>
            <div><label>Product Code</label><input type="text" name="product_code" maxlength="20" required></div>
            <div><label>Product Name</label><input type="text" name="name" maxlength="150" required></div>
            <div><label>Price (₹)</label><input type="number" step="0.01" min="0.01" name="price" required></div>
            <div><label>MRP / Original Price (₹)</label><input type="number" step="0.01" min="0" name="mrp"></div>
            <div><label>Category</label><input type="text" name="category" list="cats" required><datalist id="cats"><option>Mobiles &amp; Accessories<option>Electronics<option>Men's Fashion<option>Women's Fashion<option>Home &amp; Kitchen<option>Beauty &amp; Care<option>Grocery<option>Sports &amp; Fitness</datalist></div>
            <div><label>Product Image</label><input type="file" name="product_image" accept="image/*" required></div>
            <div class="full"><button type="submit" class="add-btn">Upload Product</button></div>
        </form>

        <h3>Existing Catalog Inventory</h3>
        <table class="admin-table">
            <thead><tr><th>Image</th><th>Code</th><th>Category</th><th>Name</th><th>Price</th><th></th></tr></thead>
            <tbody>
            <?php foreach($products as $p): ?>
                <tr>
                    <td><?php if (strpos($p['image_url'],'emoji:')===0): ?><span style="font-size:2rem"><?= $h(substr($p['image_url'],6)) ?></span><?php else: ?><img src="<?= $h($p['image_url']) ?>" alt=""><?php endif; ?></td>
                    <td><?= $h($p['product_code']) ?></td><td><?= $h($p['category']) ?></td>
                    <td><?= $h($p['name']) ?></td>
                    <td>₹<?= number_format($p['price'], 2) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this product?')">
                            <input type="hidden" name="csrf" value="<?= $h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="del-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Recent Orders</h3>
        <table class="admin-table">
            <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach($orders as $o): ?>
                <tr><td><?= (int)$o['id'] ?></td><td><?= $h($o['username']) ?></td><td>₹<?= number_format($o['total'],2) ?></td><td><?= $h($o['created_at']) ?></td></tr>
            <?php endforeach; if(!$orders): ?><tr><td colspan="4">No orders yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <p><a href="index.html" style="color:#1a1a1a;font-weight:bold;text-decoration:none;">← Back to Storefront</a></p>
    </div>
</body>
</html>
