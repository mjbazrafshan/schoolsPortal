<?php
session_start();

$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;

require_once 'includes/install.php';

// AJAX تست اتصال
if (isset($_GET['ajax']) && $_GET['ajax'] == 'test_db') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $host = $_POST['host'] ?? '';
        $db = $_POST['db'] ?? '';
        $user = $_POST['user'] ?? '';
        $pass = $_POST['pass'] ?? '';
        $result = testDatabaseConnection($host, $db, $user, $pass);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// پردازش مرحله ۲
if ($current_step == 2 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'] ?? '';
    $db = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    
    if (empty($host) || empty($db) || empty($user)) {
        $error = '❌ لطفاً همه فیلدهای هاست، نام دیتابیس و نام کاربری را پر کنید.';
    } else {
        try {
            $test = testDatabaseConnection($host, $db, $user, $pass);
            if ($test['status'] == 'error') {
                $error = '❌ اتصال به دیتابیس ناموفق: ' . $test['message'];
            } else {
                $_SESSION['db_host'] = $host;
                $_SESSION['db_name'] = $db;
                $_SESSION['db_user'] = $user;
                $_SESSION['db_pass'] = $pass;
                header('Location: ?step=3');
                exit;
            }
        } catch (Throwable $e) {
            $error = '❌ خطا در پردازش: ' . $e->getMessage();
        }
    }
}

// پردازش مرحله ۳
if (isset($_POST['install_tables'])) {
    if (empty($_SESSION['db_host']) || empty($_SESSION['db_name']) || empty($_SESSION['db_user'])) {
        $error = '❌ اطلاعات دیتابیس یافت نشد. لطفاً دوباره از مرحله ۲ شروع کنید.';
        header('Location: ?step=2');
        exit;
    }
    
    $host = $_SESSION['db_host'];
    $db = $_SESSION['db_name'];
    $user = $_SESSION['db_user'];
    $pass = $_SESSION['db_pass'];
    
    try {
        // نوشتن فایل کانفیگ با کلید API
        $config_result = writeConfigFile($host, $db, $user, $pass, '39f501cd-4316-43d4-ad32-5227338efcbe');
        if ($config_result['status'] === 'error') {
            $error = $config_result['message'];
        } else {
            $tables_result = createTables($host, $db, $user, $pass);
            if ($tables_result['status'] === 'error') {
                $error = $tables_result['message'];
            } else {
                $success = true;
                $details = $tables_result['details'];
            }
        }
    } catch (Throwable $e) {
        $error = '❌ خطا در نصب جداول: ' . $e->getMessage();
    }
}

// پردازش مرحله ۴
if (isset($_POST['finish_install'])) {
    try {
        if (file_put_contents('install.lock', date('Y-m-d H:i:s') . ' - نصب با موفقیت انجام شد.')) {
            $install_completed = true;
        } else {
            $error = '❌ خطا در ایجاد فایل قفل نصب. لطفاً پوشه ریشه را قابل نوشتن (writable) کنید.';
        }
    } catch (Throwable $e) {
        $error = '❌ خطا: ' . $e->getMessage();
    }
}

if (file_exists('install.lock') && !isset($install_completed)) {
    die('<div style="text-align:center; padding:50px; font-family:Vazirmatn, sans-serif; direction:rtl;">
        <h2 style="color:#E53E3E;">سیستم قبلاً نصب شده است!</h2>
        <p style="margin-top:15px;">برای نصب مجدد، فایل <code style="background:#edf2f7; padding:4px 12px; border-radius:6px;">install.lock</code> را از ریشه پروژه حذف کنید.</p>
        <a href="index.php" style="display:inline-block; margin-top:20px; padding:12px 30px; background:#2B6CB0; color:#fff; border-radius:8px; text-decoration:none;">ورود به سامانه</a>
    </div>');
}

if ($current_step == 3 && empty($_SESSION['db_host']) && !isset($error) && !isset($success)) {
    header('Location: ?step=2');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سامانه ثبت‌نام هنرستان</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">

        <!-- نمایشگر مرحله‌ها (Step Indicator) -->
        <div class="step-indicator">
            <div class="step-item <?php echo ($current_step == 1) ? 'active' : ''; ?>">
                <span class="num">1</span> خوش‌آمدگویی
            </div>
            <div class="step-item <?php echo ($current_step == 2) ? 'active' : ''; ?>">
                <span class="num">2</span> اطلاعات دیتابیس
            </div>
            <div class="step-item <?php echo ($current_step == 3) ? 'active' : ''; ?>">
                <span class="num">3</span> نصب جداول
            </div>
            <div class="step-item <?php echo ($current_step == 4) ? 'active' : ''; ?>">
                <span class="num">4</span> اتمام نصب
            </div>
        </div>

        <?php if ($current_step == 1): ?>
            <!-- ====== مرحله ۱: خوش‌آمدگویی ====== -->
            <h1>به سامانه <span>ثبت‌نام هنرستان</span> خوش آمدید</h1>
            <p class="subtitle">این نصب‌گر به شما کمک می‌کند تا سیستم را در کمتر از ۵ دقیقه راه‌اندازی کنید.</p>
            
            <div class="info-box">
                <p style="font-weight:700;">📋 قبل از شروع، موارد زیر را آماده داشته باشید:</p>
                <ul>
                    <li>📍 نام هاست دیتابیس (معمولاً <code>localhost</code>)</li>
                    <li>🗄️ نام دیتابیس (که قبلاً در هاست خود ایجاد کرده‌اید)</li>
                    <li>👤 نام کاربری دیتابیس</li>
                    <li>🔑 رمز عبور دیتابیس</li>
                </ul>
            </div>

            <a href="?step=2" class="btn btn-success btn-block" style="text-align:center; text-decoration:none;">شروع نصب ➡</a>

        <?php elseif ($current_step == 2): ?>
            <!-- ====== مرحله ۲: اطلاعات دیتابیس ====== -->
            <h1>اطلاعات اتصال به <span>دیتابیس</span></h1>
            <p class="subtitle">لطفاً مشخصات دیتابیس MySQL خود را وارد کنید.</p>

            <?php if (isset($error)): ?>
                <div style="background:#fff5f5; border:2px solid #E53E3E; border-radius:12px; padding:15px; margin-bottom:20px;">
                    <p style="color:#E53E3E;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form id="db-form" method="POST" action="?step=2">
                <div class="form-group">
                    <label>Hostname (هاست)</label>
                    <input type="text" id="db_host" name="host" value="localhost" placeholder="مثلاً localhost" required>
                </div>
                <div class="form-group">
                    <label>نام دیتابیس</label>
                    <input type="text" id="db_name" name="db_name" placeholder="نام دیتابیس را وارد کنید" required>
                </div>
                <div class="form-group">
                    <label>نام کاربری</label>
                    <input type="text" id="db_user" name="db_user" placeholder="نام کاربری دیتابیس" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور</label>
                    <input type="password" id="db_pass" name="db_pass" placeholder="رمز عبور (در صورت وجود)">
                </div>

                <button type="button" id="test-btn" class="btn btn-primary" style="width:100%; margin-bottom:10px;">🔍 بررسی اتصال</button>
                
                <div id="test-result"></div>

                <button type="submit" id="next-btn" class="btn btn-success btn-block" style="margin-top:15px;">مرحله بعد (نصب جداول) ➡</button>
            </form>

            <p class="footer-note">⚠️ اطلاعات وارد شده، فقط در فایل <code>config/database.php</code> ذخیره می‌شوند.</p>

        <?php elseif ($current_step == 3): ?>
            <!-- ====== مرحله ۳: نصب جداول ====== -->
            <h1>در حال نصب <span>جداول دیتابیس</span></h1>
            <p class="subtitle">لطفاً صبر کنید تا سیستم جداول مورد نیاز را ایجاد کند.</p>

            <?php if (isset($error)): ?>
                <div style="background:#fff5f5; border:2px solid #E53E3E; border-radius:12px; padding:20px; margin:20px 0;">
                    <p style="color:#E53E3E; font-weight:700;">❌ خطا</p>
                    <p><?php echo htmlspecialchars($error); ?></p>
                    <a href="?step=2" class="btn btn-primary" style="margin-top:10px;">بازگشت و تصحیح اطلاعات</a>
                </div>
            <?php elseif (isset($success)): ?>
                <div style="background:#f0fff4; border:2px solid #38A169; border-radius:12px; padding:20px; margin:20px 0;">
                    <p style="color:#38A169; font-weight:700;">✅ نصب جداول با موفقیت انجام شد</p>
                    <ul style="margin-top:10px; padding-right:20px; line-height:2;">
                        <?php foreach ($details as $item): ?>
                            <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <form method="POST" action="?step=4">
                    <button type="submit" name="finish_install" class="btn btn-success btn-block">اتمام نصب و ورود به سامانه 🎉</button>
                </form>
            <?php else: ?>
                <form method="POST" action="?step=3">
                    <div style="background:#EBF8FF; border-radius:12px; padding:20px; margin:20px 0; text-align:center;">
                        <p>🔄 در حال ایجاد جداول با اطلاعات زیر:</p>
                        <p style="font-size:14px; color:#2D3748; margin-top:5px;">
                            هاست: <strong><?php echo htmlspecialchars($_SESSION['db_host'] ?? ''); ?></strong> | 
                            دیتابیس: <strong><?php echo htmlspecialchars($_SESSION['db_name'] ?? ''); ?></strong> | 
                            کاربر: <strong><?php echo htmlspecialchars($_SESSION['db_user'] ?? ''); ?></strong>
                        </p>
                        <p style="font-size:14px; color:#718096; margin-top:10px;">این عملیات چند ثانیه طول می‌کشد.</p>
                    </div>
                    
                    <button type="submit" name="install_tables" class="btn btn-primary btn-block">شروع نصب جداول</button>
                </form>
            <?php endif; ?>

        <?php elseif ($current_step == 4): ?>
            <!-- ====== مرحله ۴: اتمام نصب ====== -->
            <h1>🎉 نصب با <span>موفقیت</span> انجام شد</h1>
            
            <div style="background:#f0fff4; border:2px solid #38A169; border-radius:12px; padding:25px; margin:25px 0; text-align:center;">
                <p style="font-size:20px; color:#38A169; font-weight:700;">سیستم ثبت‌نام هنرستان آماده استفاده است!</p>
                <p style="margin-top:10px; color:#2D3748;">اکنون می‌توانید فرم ثبت‌نام دانش‌آموزان را شروع کنید.</p>
            </div>

            <a href="index.php" class="btn btn-success btn-block" style="text-align:center; text-decoration:none; font-size:18px;">🚀 ورود به سامانه</a>
            
            <p class="footer-note" style="margin-top:20px;">🔒 برای نصب مجدد، فایل <code>install.lock</code> را از ریشه پروژه حذف کنید.</p>

        <?php endif; ?>

    </div>

    <script src="assets/js/setup.js"></script>
</body>
</html>