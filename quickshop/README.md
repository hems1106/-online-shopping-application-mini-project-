# QuickShop – PHP/MySQL online store

## Setup (XAMPP/WAMP/MAMP or any PHP 7.4+ with MySQL)
1. Copy this folder to `htdocs/quickshop`. Start Apache + MySQL.
2. Edit DB credentials in `db.php` if needed (default: root / no password).
3. Open `http://localhost/quickshop/install.php` once (creates tables, sample products, admin user), then **delete install.php**. Safe to re-run.
   (Or import `database.sql` in phpMyAdmin and set a user's `role` to `admin`.)
4. Open `http://localhost/quickshop/index.html`.

Admin login: `admin@quickshop.test` / `admin123` — change it right away.

## Features
48 products in 8 categories (prices in ₹ with discounts), separate Home and Shop pages with search, category filters and sorting, slide-out cart, register/login, order saving, and an admin panel.
checkout saves orders (prices re-read from DB), admin panel (add/delete products, validated image uploads, order list), responsive layout.
