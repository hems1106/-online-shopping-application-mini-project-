<?php
require_once 'db.php';
session_start();

$error = '';
$info = '';
if (isset($_GET['registered'])) $info = 'Account created! Please sign in.';
if (($_GET['need'] ?? '') === 'checkout') $info = 'Please sign in to complete your order. Your cart is saved.';
if (($_GET['need'] ?? '') === 'admin') $info = 'Admin login required.';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.html");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QuickShop | Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container { max-width: 400px; margin: 60px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .auth-container h2 { margin-bottom: 20px; color: #1a1a1a; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .auth-btn { width: 100%; background: #1a1a1a; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .auth-error { color: #ff4757; font-size: 0.85rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Sign In</h2>
        <?php if($info): ?><div style="color:#166534;font-size:.85rem;margin-bottom:15px"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>
        <?php if($error): ?><div class="auth-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="auth-btn">Login</button>
        </form>
        <p style="margin-top: 15px; font-size: 0.9rem; text-align: center;">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>