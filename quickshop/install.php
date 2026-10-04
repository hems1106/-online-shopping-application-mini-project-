<?php
// Run once: http://localhost/quickshop/install.php  then DELETE this file. Safe to re-run (users/orders are kept).
require_once 'db.php';
foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/database.sql')))) as $sql) { $pdo->exec($sql); }
foreach (["category VARCHAR(60) NOT NULL DEFAULT 'General'", "mrp DECIMAL(10,2) NOT NULL DEFAULT 0", "rating DECIMAL(2,1) NOT NULL DEFAULT 4.0"] as $c) {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS $c");
}
$pdo->exec("DELETE FROM products WHERE product_code IN ('P001','P002','P003')");

$exists = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$exists->execute(['admin@quickshop.test']);
if (!$exists->fetch()) {
    $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,'admin')")
        ->execute(['admin', 'admin@quickshop.test', password_hash('admin123', PASSWORD_BCRYPT)]);
}

$seed = <<<'SEED'
# Mobiles & Accessories
MOB01|5G Smartphone 8GB RAM 128GB Storage|10999|14999|4.2|📱
MOB02|5G Smartphone 6GB RAM 128GB, 50MP Camera|12490|16990|4.3|📱
MOB03|Bluetooth Neckband Earphones with 40H Playback|999|2990|4.1|🎧
MOB04|20000mAh Fast Charging Power Bank|1499|3499|4.2|🔋
MOB05|65W GaN Fast Charger with Type-C Cable|1199|2499|4.4|🔌
MOB06|Tempered Glass Screen Guard (Pack of 2)|199|799|4.0|🛡️
# Electronics
ELE01|True Wireless Bluetooth Earbuds with Charging Case|1299|4490|4.2|🎧
ELE02|Smart Watch with Bluetooth Calling, 1.83" Display|1799|6999|4.1|⌚
ELE03|32 inch HD Ready Smart LED TV|11999|21999|4.3|📺
ELE04|Portable Bluetooth Speaker 20W, Waterproof|1499|3999|4.3|🔊
ELE05|Wireless Keyboard and Mouse Combo|899|1999|4.0|⌨️
ELE06|1080p Full HD Webcam with Microphone|1299|2999|4.1|📷
# Men's Fashion
MEN01|Men's Cotton Round Neck T-Shirt (Pack of 3)|599|1499|4.1|👕
MEN02|Men's Slim Fit Stretch Denim Jeans|899|2199|4.2|👖
MEN03|Men's Formal Full Sleeve Cotton Shirt|699|1799|4.0|👔
MEN04|Men's Lightweight Running Sports Shoes|1099|2999|4.3|👟
MEN05|Men's Fleece Hooded Sweatshirt|799|1999|4.2|🧥
MEN06|Genuine Leather Bifold Wallet|349|999|4.4|👛
# Women's Fashion
WOM01|Women's Printed Cotton Kurti|449|1199|4.0|👗
WOM02|Banarasi Silk Saree with Blouse Piece|1299|3999|4.3|🥻
WOM03|Anarkali Kurta Set with Dupatta|999|2799|4.2|👗
WOM04|Women's Stylish Handbag with Sling|599|1799|4.1|👜
WOM05|Women's Cotton Palazzo Pants|349|899|4.2|👖
WOM06|Oxidised Silver Jhumka Earrings|199|699|4.5|💍
# Home & Kitchen
HOM01|Non-Stick Cookware Set, 3 Pieces|1099|2999|4.2|🍳
HOM02|1.5 Litre Electric Kettle, Stainless Steel|599|1495|4.3|☕
HOM03|750W Mixer Grinder with 3 Jars|2199|4999|4.4|🥤
HOM04|Cotton Double Bedsheet with 2 Pillow Covers|499|1499|4.1|🛏️
HOM05|9W LED Bulb (Pack of 4)|599|1599|4.2|💡
HOM06|Stainless Steel Water Bottle 1 Litre|349|799|4.4|🧴
# Beauty & Care
BEA01|Vitamin C Face Serum 30ml|349|799|4.2|✨
BEA02|Matte Lipstick Combo (Set of 4)|399|1200|4.0|💄
BEA03|Onion Hair Oil 250ml for Hair Growth|299|599|4.1|🧴
BEA04|Cordless Beard Trimmer for Men|899|1999|4.3|🪒
BEA05|Sunscreen SPF 50 PA+++ 50g|299|650|4.4|☀️
BEA06|Herbal Neem Face Wash 150ml|199|399|4.2|🧼
# Grocery
GRO01|Premium Basmati Rice 5kg|599|899|4.4|🍚
GRO02|Cold Pressed Groundnut Oil 1 Litre|249|330|4.3|🫒
GRO03|Assorted Dry Fruits Gift Pack 500g|549|899|4.5|🥜
GRO04|Masala Chai Tea Powder 500g|199|299|4.3|🍵
GRO05|Whole Wheat Atta 10kg|429|560|4.2|🌾
GRO06|Natural Honey 500g|249|399|4.4|🍯
# Sports & Fitness
SPO01|Anti-Skid Yoga Mat 6mm with Strap|399|999|4.2|🧘
SPO02|Adjustable Dumbbell Set 10kg|1299|2999|4.1|🏋️
SPO03|Badminton Racquet Set with 2 Shuttles|699|1799|4.2|🏸
SPO04|Kashmir Willow Cricket Bat, Full Size|899|2199|4.3|🏏
SPO05|Speed Skipping Rope with Counter|199|499|4.0|🤸
SPO06|Protein Shaker Bottle 700ml|249|599|4.3|🥛
SEED;

$ins = $pdo->prepare("INSERT IGNORE INTO products (product_code,name,price,mrp,rating,category,image_url) VALUES (?,?,?,?,?,?,?)");
$cat = ''; $n = 0;
foreach (explode("\n", trim($seed)) as $l) {
    $l = trim($l);
    if ($l === '') continue;
    if ($l[0] === '#') { $cat = trim(substr($l, 1)); continue; }
    [$code, $name, $price, $mrp, $rating, $emoji] = explode('|', $l);
    $ins->execute([$code, $name, $price, $mrp, $rating, $cat, 'emoji:' . $emoji]);
    $n++;
}
echo "<p>Install complete: $n products loaded. Admin: <b>admin@quickshop.test</b> / <b>admin123</b> (change it!)</p>
      <p><b>Now delete install.php.</b> <a href='index.html'>Open store</a></p>";
