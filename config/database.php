<?php
// ====== تنظیمات دیتابیس (ایجاد شده توسط Setup) ======
define('DB_HOST', 'localhost');
define('DB_NAME', 'school');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ====== تنظیمات سامانه پیامکی (لیمو اس ام اس) ======
define('SMS_API_KEY', '39f501cd-4316-43d4-ad32-5227338efcbe');
define('SMS_API_URL', 'https://api.limosms.com/api/');
define('OTP_EXPIRE_MINUTES', 2);// ====== تنظیمات پترن‌های لیمو اس ام اس ======
define('SMS_PATTERN_REGISTER', 2862); // ثبت‌نام موفق
define('SMS_PATTERN_APPROVE', 2863);  // تایید مدیر
define('SMS_PATTERN_REJECT', 2864);   // رد ثبت نام


// ====== تنظیمات مدیریت ======
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('123456', PASSWORD_DEFAULT)); // رمز پیش‌فرض: 123456
?>