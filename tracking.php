<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

$code = $_GET['code'] ?? '';
$student = null;
$error = '';

if (!empty($code)) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT s.*, f.first_priority 
                               FROM students s 
                               LEFT JOIN field_choices f ON s.id = f.student_id 
                               WHERE s.national_code = ?");
        $stmt->execute([$code]);
        $student = $stmt->fetch();
        
        if (!$student) {
            $error = '❌ کد پیگیری نامعتبر است.';
        }
    } catch (Exception $e) {
        $error = '❌ خطا در ارتباط با دیتابیس.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیگیری وضعیت ثبت‌نام</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tracking-container { max-width: 600px; margin: 50px auto; }
        .status-card {
            background: var(--box-white);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border-right: 6px solid var(--primary-blue);
            text-align: center;
        }
        .status-card .icon { font-size: 60px; margin-bottom: 15px; display: block; }
        .status-card .status-badge {
            display: inline-block;
            padding: 8px 25px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 18px;
            margin: 10px 0;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
            margin: 20px 0;
            text-align: right;
        }
        .info-grid .label { font-weight: 600; color: var(--text-gray); }
        .info-grid .value { font-weight: 500; }
        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary-blue);
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 15px;
        }
        .btn-home:hover { background: var(--primary-dark); }
        @media (max-width: 480px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container tracking-container">
        <h1>📋 <span>پیگیری</span> وضعیت ثبت‌نام</h1>

        <?php if ($error): ?>
            <div class="error-box" style="background:#fff5f5; border:2px solid #E53E3E; border-radius:12px; padding:20px; text-align:center;">
                <p style="color:#E53E3E; font-size:18px;"><?php echo $error; ?></p>
                <a href="index.php" class="btn-home">🏠 بازگشت به صفحه اصلی</a>
            </div>
        <?php elseif ($student): ?>
            <div class="status-card">
                <?php 
                $status_map = [
                    'pending' => ['icon' => '⏳', 'label' => 'در انتظار تأیید', 'class' => 'status-pending'],
                    'approved' => ['icon' => '✅', 'label' => 'تأیید شده', 'class' => 'status-approved'],
                    'rejected' => ['icon' => '❌', 'label' => 'رد شده', 'class' => 'status-rejected']
                ];
                $status = $student['status'] ?? 'pending';
                $status_info = $status_map[$status];
                ?>
                <span class="icon"><?php echo $status_info['icon']; ?></span>
                <span class="status-badge <?php echo $status_info['class']; ?>">
                    <?php echo $status_info['label']; ?>
                </span>

                <div class="info-grid">
                    <div><span class="label">نام و نام خانوادگی:</span> <span class="value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span></div>
                    <div><span class="label">کد ملی (کد پیگیری):</span> <span class="value"><?php echo htmlspecialchars($student['national_code']); ?></span></div>
                    <div><span class="label">رشته انتخابی:</span> <span class="value"><?php 
                        $map = ['computer'=>'کامپیوتر', 'mechanics'=>'مکانیک', 'electronics'=>'الکترونیک'];
                        echo $map[$student['first_priority']] ?? '—';
                    ?></span></div>
                    <div><span class="label">تاریخ ثبت‌نام:</span> <span class="value"><?php echo date('Y/m/d', strtotime($student['created_at'])); ?></span></div>
                </div>

                <?php if ($status === 'approved'): ?>
                    <div style="background: #ebf8ff; padding: 15px; border-radius: 10px; margin-top: 15px; border-right: 4px solid var(--primary-blue);">
                        <p style="color: var(--primary-dark); font-weight: 600;">📌 لطفاً برای تکمیل مراحل ثبت‌نام به مدرسه مراجعه کنید.</p>
                    </div>
                <?php elseif ($status === 'rejected'): ?>
                    <div style="background: #fff5f5; padding: 15px; border-radius: 10px; margin-top: 15px; border-right: 4px solid #E53E3E;">
                        <p style="color: #E53E3E; font-weight: 600;">❌ ثبت‌نام شما تایید نشده است. برای اطلاعات بیشتر با مدرسه تماس بگیرید.</p>
                    </div>
                <?php else: ?>
                    <div style="background: #fffbeb; padding: 15px; border-radius: 10px; margin-top: 15px; border-right: 4px solid #f6ad55;">
                        <p style="color: #744210; font-weight: 600;">⏳ ثبت‌نام شما در انتظار بررسی است. به زودی نتیجه اعلام می‌شود.</p>
                    </div>
                <?php endif; ?>

                <a href="index.php" class="btn-home">🏠 بازگشت به صفحه اصلی</a>
            </div>
        <?php else: ?>
            <div class="status-card">
                <p style="font-size:18px;">🔍 برای مشاهده وضعیت، کد پیگیری خود را وارد کنید.</p>
                <form method="GET" action="tracking.php" style="margin-top:20px;">
                    <div class="form-group">
                        <label>کد پیگیری (کد ملی)</label>
                        <input type="text" name="code" placeholder="کد ملی خود را وارد کنید" maxlength="10" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">🔍 بررسی وضعیت</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="footer-art" style="margin-top:40px; padding-top:20px; border-top:2px solid #e2e8f0; text-align:center;">
            <p style="font-size:14px; color:var(--text-gray);">🏫 هنرستان فرصت شیرازی</p>
        </div>
    </div>
</body>
</html>