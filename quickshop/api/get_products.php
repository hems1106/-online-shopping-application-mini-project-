<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode($pdo->query("SELECT id, product_code AS code, name, price, mrp, rating, category, image_url AS image FROM products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
