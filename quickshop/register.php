<?php
require_once 'db.php';
session_start();

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (strlen($_POST['password']) < 6) { $error = 'Password must be at least 6 characters.'; }
    else try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$user, $email, $pass]);
        header("Location: login.php?registered=success");
        exit();
    } catch (PDOException $e) {
        $error = "Registration failed. Username or email may already exist.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QuickShop | Register</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container { max-width: 400px; margin: 60px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .auth-container h2 { margin-bottom: 20px; color: #1a1a1a; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .auth-btn { width: 100%; background: #2ed573; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .auth-error { color: #ff4757; font-size: 0.85rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Create an Account</h2>
        <?php if($error): ?><div class="auth-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="auth-btn">Register</button>
        </form>
        <p style="margin-top: 15px; font-size: 0.9rem; text-align: center;">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>