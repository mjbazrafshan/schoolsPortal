<?php
/**
 * توابع احراز هویت و ارتباط با لیمو اس ام اس
 */

// بارگذاری فایل دیتابیس
if (file_exists(__DIR__ . '/database.php')) {
    require_once __DIR__ . '/database.php';
} else {
    throw new Exception('❌ فایل includes/database.php یافت نشد.');
}

// بررسی وجود تابع getDBConnection
if (!function_exists('getDBConnection')) {
    throw new Exception('❌ تابع getDBConnection در includes/database.php تعریف نشده است.');
}

// تعریف مقدار پیش‌فرض برای OTP_EXPIRE_MINUTES
if (!defined('OTP_EXPIRE_MINUTES')) {
    define('OTP_EXPIRE_MINUTES', 2);
}

/**
 * تولید کد ۴ رقمی تصادفی
 */
function generateOtpCode() {
    return str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * ارسال کد تأیید از طریق متد sendpatternmessage لیمو اس ام اس
 */
function sendOtpCode($mobile) {
    // اعتبارسنجی شماره موبایل
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'شماره موبایل نامعتبر است.'];
    }
    
    // ====== بررسی تنظیمات API ======
    if (!defined('SMS_API_KEY') || empty(SMS_API_KEY)) {
        return ['status' => 'error', 'message' => '❌ کلید API لیمو اس ام اس تنظیم نشده است.'];
    }
    
    if (!defined('SMS_API_URL') || empty(SMS_API_URL)) {
        return ['status' => 'error', 'message' => '❌ آدرس API لیمو اس ام اس تنظیم نشده است.'];
    }
    
    // تولید کد جدید (۴ رقمی)
    $otp_code = generateOtpCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRE_MINUTES . ' minutes'));
    
    // ذخیره در دیتابیس
    try {
        $pdo = getDBConnection();
        
        // حذف کدهای قبلی منقضی نشده برای این شماره
        $stmt = $pdo->prepare("DELETE FROM otp_sessions WHERE mobile = ? AND expires_at > NOW()");
        $stmt->execute([$mobile]);
        
        // درج کد جدید
        $stmt = $pdo->prepare("INSERT INTO otp_sessions (mobile, otp_code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$mobile, $otp_code, $expires_at]);
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => '❌ خطا در ذخیره کد در دیتابیس: ' . $e->getMessage()];
    }
    
    // ====== ارسال از طریق متد sendpatternmessage لیمو اس ام اس ======
    $api_key = SMS_API_KEY;
    $url = rtrim(SMS_API_URL, '/') . '/sendpatternmessage';
    
    // ====== ساخت داده‌های ارسالی طبق مستندات (با OtpId به صورت رشته) ======
    $post_data = json_encode([
        'OtpId' => '2840',                    // شناسه پترن (به صورت رشته)
        'ReplaceToken' => [$otp_code],       // کد ۴ رقمی برای جایگزینی #{0}
        'MobileNumber' => $mobile            // شماره موبایل گیرنده
    ]);
    
    // ارسال درخواست با cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'ApiKey: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // بررسی خطاهای cURL
    if ($curl_error) {
        return ['status' => 'error', 'message' => '❌ خطا در اتصال به سامانه پیامکی: ' . $curl_error];
    }
    
    // بررسی پاسخ HTTP
    if ($http_code != 200) {
        return ['status' => 'error', 'message' => '❌ خطا در اتصال به سامانه پیامکی (کد خطای HTTP: ' . $http_code . ')'];
    }
    
    // پردازش پاسخ API
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['status' => 'error', 'message' => '❌ پاسخ نامعتبر از سامانه پیامکی: ' . $response];
    }
    
    // ====== بررسی وضعیت پاسخ با کلید Success (حرف بزرگ) ======
    if (isset($result['Success']) && $result['Success'] == true) {
        return ['status' => 'success', 'message' => 'کد تأیید با موفقیت ارسال شد.'];
    } else {
        // ====== اگر خطا بود، پیام خطا را نمایش بده ======
        $error_msg = $result['Message'] ?? 'خطای ناشناخته در سامانه پیامکی';
        
        // ====== اگر پیام خطا خالی بود، پاسخ خام را نشان بده (برای دیباگ) ======
        if (empty($error_msg) || $error_msg == 'خطای ناشناخته در سامانه پیامکی') {
            $error_msg = 'پاسخ سرور: ' . $response;
        }
        
        return ['status' => 'error', 'message' => '❌ ' . $error_msg];
    }
}

/**
 * تابع بررسی موفقیت پاسخ از لیمو اس ام اس (بر اساس محتوای پیام)
 */
function isLimoSuccess($data) {
    if (isset($data['status']) && $data['status'] == 'success') {
        return true;
    }
    $msg = $data['message'] ?? '';
    $successKeywords = ['تایید', 'درست', 'موفق', 'صحیح', 'ارسال شد', 'تأیید'];
    foreach ($successKeywords as $keyword) {
        if (strpos($msg, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * تأیید کد ارسال شده (با ارسال به API لیمو اس ام اس)
 */
function verifyOtpCode($mobile, $code) {
    // اعتبارسنجی شماره موبایل
    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        return ['status' => 'error', 'message' => 'شماره موبایل نامعتبر است.'];
    }
    
    // ====== بررسی در دیتابیس ======
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, otp_code, expires_at, is_verified FROM otp_sessions 
                               WHERE mobile = ? AND otp_code = ? AND is_verified = 0 
                               ORDER BY id DESC LIMIT 1");
        $stmt->execute([$mobile, $code]);
        $record = $stmt->fetch();
        
        if (!$record) {
            return ['status' => 'error', 'message' => 'کد وارد شده صحیح نیست.'];
        }
        
        // بررسی انقضا
        if (strtotime($record['expires_at']) < time()) {
            return ['status' => 'error', 'message' => '⏰ کد منقضی شده است. لطفاً دوباره درخواست کنید.'];
        }
        
        // تأیید کد در دیتابیس
        $stmt = $pdo->prepare("UPDATE otp_sessions SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$record['id']]);
        
        // ذخیره شماره در جلسه
        $_SESSION['verified_mobile'] = $mobile;
        $_SESSION['verified_at'] = time();
        
        return ['status' => 'success', 'message' => '✅ کد با موفقیت تأیید شد.'];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => '❌ خطا در تأیید کد: ' . $e->getMessage()];
    }
}

/**
 * بررسی وضعیت احراز هویت کاربر
 */
function isAuthenticated() {
    if (isset($_SESSION['verified_mobile']) && isset($_SESSION['verified_at'])) {
        if (time() - $_SESSION['verified_at'] < 1800) {
            return true;
        }
    }
    return false;
}

/**
 * خروج از سیستم (لغو احراز هویت)
 */
function logout() {
    unset($_SESSION['verified_mobile']);
    unset($_SESSION['verified_at']);
    session_destroy();
}
?>