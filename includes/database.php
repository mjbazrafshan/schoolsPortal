<?php
/**
 * اتصال به دیتابیس با استفاده از تنظیمات config/database.php
 */

// بارگذاری تنظیمات دیتابیس
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} else {
    throw new Exception('❌ فایل تنظیمات دیتابیس یافت نشد. لطفاً ابتدا سیستم را نصب کنید.');
}

// بررسی وجود ثابت‌های مورد نیاز
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    throw new Exception('❌ تنظیمات دیتابیس کامل نیست. لطفاً فایل config/database.php را بررسی کنید یا سیستم را مجدداً نصب کنید.');
}

/**
 * دریافت اتصال PDO به دیتابیس
 */
function getDBConnection() {
    try {
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . $charset;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('❌ خطا در اتصال به دیتابیس: ' . $e->getMessage());
    }
}
?>