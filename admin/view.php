<?php
require_once '../includes/admin_functions.php';
require_once '../includes/database.php';
requireAdminLogin();

if (!isset($_GET['id'])) {
    header('Location: students.php');
    exit;
}

$pdo = getDBConnection();
$data = getStudentDetail($pdo, intval($_GET['id']));
if (!$data) {
    header('Location: students.php');
    exit;
}

$student = $data['student'];
$parents = $data['parents'];
$devices = $data['devices'];
$fields = $data['fields'];
$health = $data['health'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشاهده جزئیات دانش‌آموز</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 900px; margin: 0 auto; }
        .detail-section { background: var(--box-white); border-radius: 12px; padding: 20px; margin-bottom: 20px; border-right: 4px solid var(--primary-blue); }
        .detail-section h3 { margin-bottom: 15px; color: var(--primary-dark); }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 25px; }
        .detail-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f7fafc; }
        .detail-item .label { font-weight: 600; color: var(--text-gray); }
        .detail-item .value { font-weight: 500; }
        .btn-back { display: inline-block; padding: 10px 25px; background: #e2e8f0; color: var(--text-dark); border-radius: 10px; text-decoration: none; }
        @media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container admin-container">
        <h1>📋 <span>جزئیات</span> دانش‌آموز</h1>
        <a href="students.php" class="btn-back" style="margin-bottom:20px; display:inline-block;">← بازگشت به لیست</a>

        <!-- اطلاعات فردی -->
        <div class="detail-section">
            <h3>👤 اطلاعات فردی</h3>
            <div class="detail-grid">
                <div class="detail-item"><span class="label">نام</span><span class="value"><?php echo htmlspecialchars($student['first_name']); ?></span></div>
                <div class="detail-item"><span class="label">نام خانوادگی</span><span class="value"><?php echo htmlspecialchars($student['last_name']); ?></span></div>
                <div class="detail-item"><span class="label">کد ملی</span><span class="value"><?php echo htmlspecialchars($student['national_code']); ?></span></div>
                <div class="detail-item"><span class="label">شماره موبایل</span><span class="value"><?php echo htmlspecialchars($student['mobile']); ?></span></div>
                <div class="detail-item"><span class="label">تاریخ تولد</span><span class="value"><?php echo htmlspecialchars($student['birth_date']); ?></span></div>
                <div class="detail-item"><span class="label">محل تولد</span><span class="value"><?php echo htmlspecialchars($student['birth_place']); ?></span></div>
                <div class="detail-item"><span class="label">ملیت</span><span class="value"><?php echo htmlspecialchars($student['nationality']); ?></span></div>
                <div class="detail-item"><span class="label">وضعیت سلامت</span><span class="value"><?php echo ($student['health_status'] === 'sane') ? 'سالم' : 'معلول'; ?></span></div>
            </div>
        </div>

        <!-- والدین -->
        <div class="detail-section">
            <h3>👨‍👩‍👦 والدین</h3>
            <?php foreach ($parents as $parent): ?>
                <div style="background: <?php echo ($parent['parent_type'] === 'father') ? '#f0f7ff' : '#fff5f0'; ?>; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px;">
                    <strong><?php echo ($parent['parent_type'] === 'father') ? 'پدر' : 'مادر'; ?></strong>
                    <div class="detail-grid" style="margin-top:5px;">
                        <div class="detail-item"><span class="label">نام کامل</span><span class="value"><?php echo htmlspecialchars($parent['full_name']); ?></span></div>
                        <div class="detail-item"><span class="label">کد ملی</span><span class="value"><?php echo htmlspecialchars($parent['national_code']); ?></span></div>
                        <div class="detail-item"><span class="label">تحصیلات</span><span class="value"><?php echo htmlspecialchars($parent['education'] ?? '—'); ?></span></div>
                        <div class="detail-item"><span class="label">شغل</span><span class="value"><?php echo htmlspecialchars($parent['job'] ?? '—'); ?></span></div>
                        <div class="detail-item"><span class="label">شماره تماس</span><span class="value"><?php echo htmlspecialchars($parent['phone'] ?? '—'); ?></span></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- امکانات -->
        <div class="detail-section">
            <h3>💻 امکانات سخت‌افزاری</h3>
            <div class="detail-grid">
                <div class="detail-item"><span class="label">رایانه شخصی</span><span class="value"><?php echo ($devices['has_pc'] ?? 0) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                <div class="detail-item"><span class="label">لپ‌تاپ</span><span class="value"><?php echo ($devices['has_laptop'] ?? 0) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                <div class="detail-item"><span class="label">اینترنت پرسرعت</span><span class="value"><?php echo ($devices['has_internet'] ?? 0) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                <div class="detail-item"><span class="label">هیچ‌کدام</span><span class="value"><?php echo ($devices['has_none'] ?? 0) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
            </div>
        </div>

        <!-- رشته انتخاب‌شده -->
        <div class="detail-section">
            <h3>🎯 انتخاب رشته</h3>
            <div class="detail-grid">
                <div class="detail-item"><span class="label">اولویت اول</span><span class="value"><?php 
                    $map = ['computer' => 'کامپیوتر (شبکه و نرم‌افزار)', 'mechanics' => 'مکانیک (خودرو)', 'electronics' => 'الکترونیک'];
                    echo $map[$fields['first_priority'] ?? ''] ?? '—';
                ?></span></div>
                <div class="detail-item"><span class="label">اولویت دوم</span><span class="value"><?php echo $map[$fields['second_priority'] ?? ''] ?? '—'; ?></span></div>
                <div class="detail-item"><span class="label">اولویت سوم</span><span class="value"><?php echo $map[$fields['third_priority'] ?? ''] ?? '—'; ?></span></div>
            </div>
        </div>

        <!-- آدرس -->
        <div class="detail-section">
            <h3>📍 آدرس</h3>
            <div class="detail-grid">
                <div class="detail-item"><span class="label">آدرس</span><span class="value"><?php echo htmlspecialchars($student['address_text']); ?></span></div>
                <div class="detail-item"><span class="label">کد پستی</span><span class="value"><?php echo htmlspecialchars($student['postal_code'] ?? '—'); ?></span></div>
                <div class="detail-item"><span class="label">تلفن ثابت</span><span class="value"><?php echo htmlspecialchars($student['phone_number'] ?? '—'); ?></span></div>
                <div class="detail-item"><span class="label">وسیله رفت‌وآمد</span><span class="value"><?php 
                    $map = ['school_service' => 'سرویس مدرسه', 'personal_car' => 'خودروی شخصی', 'taxi' => 'تاکسی', 'bus' => 'اتوبوس', 'walk' => 'پیاده'];
                    echo $map[$student['transportation']] ?? '—';
                ?></span></div>
            </div>
        </div>

        <a href="students.php" class="btn-back" style="display:inline-block;">← بازگشت به لیست</a>
    </div>
</body>
</html>