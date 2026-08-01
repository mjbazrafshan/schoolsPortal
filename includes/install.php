<?php
/**
 * توابع نصب و راه‌اندازی سیستم
 * (ایجاد جداول، تست اتصال و ...)
 */

/**
 * تست اتصال به دیتابیس
 */
function testDatabaseConnection($host, $db, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['status' => 'success', 'message' => '✅ اتصال به دیتابیس با موفقیت برقرار شد!'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => '❌ خطا در اتصال به دیتابیس: ' . $e->getMessage()];
    }
}

/**
 * ایجاد تمام جداول مورد نیاز سیستم
 */

/*


function createTables($host, $db, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $results = [];
        
        // ====== جدول ۱: students ======
        $sql = "CREATE TABLE IF NOT EXISTS `students` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `mobile` VARCHAR(15) NOT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `father_name` VARCHAR(100) NOT NULL,
            `national_code` VARCHAR(10) NOT NULL UNIQUE,
            `birth_date` VARCHAR(10) NOT NULL,
            `birth_place` VARCHAR(100) NOT NULL,
            `nationality` VARCHAR(50) DEFAULT 'ایرانی',
            `religion` VARCHAR(50) DEFAULT NULL,
            `denomination` VARCHAR(50) DEFAULT NULL,
            `health_status` ENUM('sane', 'disabled') DEFAULT 'sane',
            `disability_description` TEXT DEFAULT NULL,
            `martyr_status` ENUM('yes', 'no') DEFAULT 'no',
            `martyr_relation` VARCHAR(100) DEFAULT NULL,
            `live_with` ENUM('parents', 'father', 'mother', 'other') DEFAULT 'parents',
            `live_with_other_desc` VARCHAR(255) DEFAULT NULL,
            `previous_school` VARCHAR(200) NOT NULL,
            `final_gpa` DECIMAL(4,2) NOT NULL,
            `is_employed` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
            `employment_description` TEXT DEFAULT NULL,
            `total_children` INT(2) NOT NULL,
            `child_order` INT(2) NOT NULL,
            `bale_id` VARCHAR(20) DEFAULT NULL,
            `eita_id` VARCHAR(20) DEFAULT NULL,
            `shad_id` VARCHAR(20) DEFAULT NULL,
            `latitude` DECIMAL(12,9) DEFAULT NULL,
            `longitude` DECIMAL(12,9) DEFAULT NULL,
            `address_text` TEXT NOT NULL,
            `postal_code` VARCHAR(10) DEFAULT NULL,
            `phone_number` VARCHAR(15) DEFAULT NULL,
            `emergency_phone` VARCHAR(15) DEFAULT NULL,
            `transportation` ENUM('school_service', 'personal_car', 'taxi', 'bus', 'walk') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_national_code` (`national_code`),
            INDEX `idx_mobile` (`mobile`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `students` با موفقیت ایجاد شد.';
        
        // ====== جدول ۲: parents ======
        $sql = "CREATE TABLE IF NOT EXISTS `parents` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `parent_type` ENUM('father', 'mother') NOT NULL,
            `full_name` VARCHAR(200) NOT NULL,
            `national_code` VARCHAR(10) NOT NULL,
            `id_card_number` VARCHAR(10) DEFAULT NULL,
            `birth_date` VARCHAR(10) DEFAULT NULL,
            `education` VARCHAR(100) DEFAULT NULL,
            `job` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(15) DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
            INDEX `idx_parent_national` (`national_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `parents` با موفقیت ایجاد شد.';
        
        // ====== جدول ۳: student_devices ======
        $sql = "CREATE TABLE IF NOT EXISTS `student_devices` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `has_pc` TINYINT(1) DEFAULT 0,
            `has_laptop` TINYINT(1) DEFAULT 0,
            `has_internet` TINYINT(1) DEFAULT 0,
            `has_none` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `student_devices` با موفقیت ایجاد شد.';
        
        // ====== جدول ۴: field_choices ======
        $sql = "CREATE TABLE IF NOT EXISTS `field_choices` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `first_priority` ENUM('computer', 'mechanics', 'electronics') NOT NULL,
            `second_priority` ENUM('computer', 'mechanics', 'electronics') DEFAULT NULL,
            `third_priority` ENUM('computer', 'mechanics', 'electronics') DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `field_choices` با موفقیت ایجاد شد.';
        
        // ====== جدول ۵: health_declarations ======
        $sql = "CREATE TABLE IF NOT EXISTS `health_declarations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `heart_disease` TINYINT(1) DEFAULT 0,
            `asthma` TINYINT(1) DEFAULT 0,
            `hypertension` TINYINT(1) DEFAULT 0,
            `thalassemia` TINYINT(1) DEFAULT 0,
            `fracture_history` TINYINT(1) DEFAULT 0,
            `joint_disorders` TINYINT(1) DEFAULT 0,
            `diabetes` TINYINT(1) DEFAULT 0,
            `cancer` TINYINT(1) DEFAULT 0,
            `vision_hearing_disorders` TINYINT(1) DEFAULT 0,
            `epilepsy` TINYINT(1) DEFAULT 0,
            `surgery_history` TINYINT(1) DEFAULT 0,
            `coagulation_disorders` TINYINT(1) DEFAULT 0,
            `genetic_disorders` TINYINT(1) DEFAULT 0,
            `balance_disorders` TINYINT(1) DEFAULT 0,
            `other_illness` TINYINT(1) DEFAULT 0,
            `other_illness_desc` TEXT DEFAULT NULL,
            `medications` TEXT DEFAULT NULL,
            `doctor_approval` ENUM('pending', 'approved', 'restricted') DEFAULT 'pending',
            `doctor_notes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `health_declarations` با موفقیت ایجاد شد.';
        
        // ====== جدول ۶: otp_sessions ======
        $sql = "CREATE TABLE IF NOT EXISTS `otp_sessions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `mobile` VARCHAR(15) NOT NULL,
            `otp_code` VARCHAR(6) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `is_verified` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_mobile_otp` (`mobile`, `otp_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `otp_sessions` با موفقیت ایجاد شد.';
        
        return ['status' => 'success', 'message' => 'تمامی جداول با موفقیت ایجاد شدند.', 'details' => $results];
        
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => '❌ خطا در ایجاد جداول: ' . $e->getMessage()];
    }
}
*/
/**
 * نوشتن فایل کانفیگ دیتابیس با کلید API
 */


function createTables($host, $db, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $results = [];
        
        // ====== جدول ۱: students (با اضافه شدن live_with_desc) ======
 // ====== جدول ۱: students (با فیلدهای جدید) ======
$sql = "CREATE TABLE IF NOT EXISTS `students` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `mobile` VARCHAR(15) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `national_code` VARCHAR(10) NOT NULL UNIQUE,
    `birth_date` VARCHAR(10) NOT NULL,
    `birth_place` VARCHAR(100) NOT NULL,
    `nationality` VARCHAR(50) DEFAULT 'ایرانی',
    `religion` VARCHAR(50) DEFAULT NULL,
    `denomination` VARCHAR(50) DEFAULT NULL,
    `health_status` ENUM('sane', 'disabled') DEFAULT 'sane',
    `disability_description` TEXT DEFAULT NULL,
    `martyr_status` ENUM('yes', 'no') DEFAULT 'no',
    `martyr_relation` VARCHAR(100) DEFAULT NULL,
    `live_with` ENUM('parents', 'father', 'mother', 'other') DEFAULT 'parents',
    `live_with_other_desc` VARCHAR(255) DEFAULT NULL,
    `live_with_desc` VARCHAR(255) DEFAULT NULL,
    `previous_school` VARCHAR(200) NOT NULL,
    `final_gpa` DECIMAL(4,2) NOT NULL,
    `is_employed` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    `employment_description` TEXT DEFAULT NULL,
    `total_children` INT(2) NOT NULL,
    `child_order` INT(2) NOT NULL,
    `emergency_phone` VARCHAR(15) DEFAULT NULL,
    `bale_id` VARCHAR(20) DEFAULT NULL,
    `eita_id` VARCHAR(20) DEFAULT NULL,
    `shad_id` VARCHAR(20) DEFAULT NULL,
    `address_text` TEXT NOT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `phone_number` VARCHAR(15) DEFAULT NULL,
    `transportation` ENUM('school_service', 'personal_car', 'taxi', 'bus', 'walk') NOT NULL,
    `latitude` DECIMAL(12,9) DEFAULT NULL,
    `longitude` DECIMAL(12,9) DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `status_updated_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_national_code` (`national_code`),
    INDEX `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `students` با موفقیت ایجاد شد (با فیلد live_with_desc).';
        
        // ====== جدول ۲: parents (بدون تغییر) ======
        $sql = "CREATE TABLE IF NOT EXISTS `parents` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `parent_type` ENUM('father', 'mother') NOT NULL,
            `full_name` VARCHAR(200) NOT NULL,
            `national_code` VARCHAR(10) NOT NULL,
            `id_card_number` VARCHAR(10) DEFAULT NULL,
            `birth_date` VARCHAR(10) DEFAULT NULL,
            `education` VARCHAR(100) DEFAULT NULL,
            `job` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(15) DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
            INDEX `idx_parent_national` (`national_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `parents` با موفقیت ایجاد شد.';
        
        // ====== جدول ۳: student_devices (بدون تغییر) ======
        $sql = "CREATE TABLE IF NOT EXISTS `student_devices` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `has_pc` TINYINT(1) DEFAULT 0,
            `has_laptop` TINYINT(1) DEFAULT 0,
            `has_internet` TINYINT(1) DEFAULT 0,
            `has_none` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `student_devices` با موفقیت ایجاد شد.';
        
        // ====== جدول ۴: field_choices (بدون تغییر) ======
        $sql = "CREATE TABLE IF NOT EXISTS `field_choices` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `first_priority` ENUM('computer', 'mechanics', 'electronics') NOT NULL,
            `second_priority` ENUM('computer', 'mechanics', 'electronics') DEFAULT NULL,
            `third_priority` ENUM('computer', 'mechanics', 'electronics') DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `field_choices` با موفقیت ایجاد شد.';
        
        // ====== جدول ۵: health_declarations (بدون تغییر) ======
        $sql = "CREATE TABLE IF NOT EXISTS `health_declarations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `student_id` INT(11) NOT NULL,
            `heart_disease` TINYINT(1) DEFAULT 0,
            `asthma` TINYINT(1) DEFAULT 0,
            `hypertension` TINYINT(1) DEFAULT 0,
            `thalassemia` TINYINT(1) DEFAULT 0,
            `fracture_history` TINYINT(1) DEFAULT 0,
            `joint_disorders` TINYINT(1) DEFAULT 0,
            `diabetes` TINYINT(1) DEFAULT 0,
            `cancer` TINYINT(1) DEFAULT 0,
            `vision_hearing_disorders` TINYINT(1) DEFAULT 0,
            `epilepsy` TINYINT(1) DEFAULT 0,
            `surgery_history` TINYINT(1) DEFAULT 0,
            `coagulation_disorders` TINYINT(1) DEFAULT 0,
            `genetic_disorders` TINYINT(1) DEFAULT 0,
            `balance_disorders` TINYINT(1) DEFAULT 0,
            `other_illness` TINYINT(1) DEFAULT 0,
            `other_illness_desc` TEXT DEFAULT NULL,
            `medications` TEXT DEFAULT NULL,
            `doctor_approval` ENUM('pending', 'approved', 'restricted') DEFAULT 'pending',
            `doctor_notes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `health_declarations` با موفقیت ایجاد شد.';
        
        // ====== جدول ۶: otp_sessions (بدون تغییر) ======
        $sql = "CREATE TABLE IF NOT EXISTS `otp_sessions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `mobile` VARCHAR(15) NOT NULL,
            `otp_code` VARCHAR(6) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `is_verified` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_mobile_otp` (`mobile`, `otp_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        $results[] = '✅ جدول `otp_sessions` با موفقیت ایجاد شد.';
        
        return ['status' => 'success', 'message' => 'تمامی جداول با موفقیت ایجاد شدند.', 'details' => $results];
        
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => '❌ خطا در ایجاد جداول: ' . $e->getMessage()];
    }
}



function writeConfigFile($host, $db, $user, $pass, $api_key = '') {
    $config_content = "<?php\n";
    $config_content .= "// ====== تنظیمات دیتابیس (ایجاد شده توسط Setup) ======\n";
    $config_content .= "define('DB_HOST', '" . addslashes($host) . "');\n";
    $config_content .= "define('DB_NAME', '" . addslashes($db) . "');\n";
    $config_content .= "define('DB_USER', '" . addslashes($user) . "');\n";
    $config_content .= "define('DB_PASS', '" . addslashes($pass) . "');\n";
    $config_content .= "define('DB_CHARSET', 'utf8mb4');\n";
    $config_content .= "\n// ====== تنظیمات سامانه پیامکی (لیمو اس ام اس) ======\n";
    $config_content .= "define('SMS_API_KEY', '" . addslashes($api_key) . "');\n";
    $config_content .= "define('SMS_API_URL', 'https://api.limosms.com/api/');\n";
    $config_content .= "define('OTP_EXPIRE_MINUTES', 2);\n";
    $config_content .= "?>";
    
    $file_path = 'config/database.php';
    if (file_put_contents($file_path, $config_content)) {
        return ['status' => 'success', 'message' => '✅ فایل تنظیمات دیتابیس با موفقیت ایجاد شد.'];
    } else {
        return ['status' => 'error', 'message' => '❌ خطا در نوشتن فایل تنظیمات. لطفاً پوشه config را قابل نوشتن (writable) کنید.'];
    }
}
?>