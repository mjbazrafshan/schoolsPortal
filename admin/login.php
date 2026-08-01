<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = '❌ نام کاربری یا رمز عبور اشتباه است.';
    }
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به مدیریت</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-box { max-width: 400px; margin: 100px auto; }
        .login-box h1 { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container login-box">
        <h1>🔐 <span>ورود</span> به مدیریت</h1>
        <?php if (isset($error)): ?>
            <div style="background:#fff5f5; border:2px solid #E53E3E; border-radius:12px; padding:15px; margin-bottom:20px; color:#E53E3E;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label>نام کاربری</label>
                <input type="text" name="username" required placeholder="نام کاربری خود را وارد کنید">
            </div>
            <div class="form-group">
                <label>رمز عبور</label>
                <input type="password" name="password" required placeholder="رمز عبور خود را وارد کنید">
            </div>
            <button type="submit" class="btn btn-success btn-block">ورود به پنل مدیریت</button>
        </form>
        <p style="text-align:center; margin-top:20px; font-size:14px; color:var(--text-gray);">رمز پیش‌فرض: 123456</p>
    </div>
</body>
</html>