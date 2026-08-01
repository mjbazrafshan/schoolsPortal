<?php
require_once '../includes/admin_functions.php';
require_once '../includes/database.php';
requireAdminLogin();

$pdo = getDBConnection();
$stats = getDashboardStats($pdo);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 1100px; margin: 0 auto; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 30px; }
        .admin-header .logout { color: #E53E3E; text-decoration: none; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--box-white); padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card .number { font-size: 32px; font-weight: 700; color: var(--primary-blue); }
        .stat-card .label { font-size: 14px; color: var(--text-gray); }
        .section-title { font-size: 20px; font-weight: 700; color: var(--primary-dark); margin: 30px 0 15px; }
        .field-list { display: flex; gap: 15px; flex-wrap: wrap; }
        .field-item { background: #f7fafc; padding: 10px 20px; border-radius: 8px; }
        .field-item strong { color: var(--primary-dark); }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>📊 <span>داشبورد</span> مدیریت</h1>
            <a href="login.php?logout=true" class="logout" onclick="event.preventDefault(); if(confirm('خروج از سیستم؟')){ window.location='logout.php'; }">🚪 خروج</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="number"><?php echo $stats['total']; ?></div><div class="label">کل ثبت‌نام‌ها</div></div>
            <div class="stat-card"><div class="number"><?php echo count($stats['fields']); ?></div><div class="label">رشته‌های انتخاب‌شده</div></div>
            <div class="stat-card"><div class="number"><?php echo $stats['total'] > 0 ? round(($stats['health'][0]['count'] ?? 0) / $stats['total'] * 100) : 0; ?>%</div><div class="label">سالم</div></div>
            <div class="stat-card"><div class="number"><?php echo $stats['total']; ?></div><div class="label">در انتظار تأیید</div></div>
        </div>

        <div class="section-title">📚 تفکیک رشته‌ها</div>
        <div class="field-list">
            <?php foreach ($stats['fields'] as $field): ?>
                <div class="field-item">
                    <strong><?php 
                        $map = ['computer' => 'کامپیوتر', 'mechanics' => 'مکانیک', 'electronics' => 'الکترونیک'];
                        echo $map[$field['first_priority']] ?? $field['first_priority'];
                    ?></strong>
                    <span><?php echo $field['count']; ?> نفر</span>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="students.php" class="btn btn-primary" style="text-decoration:none; padding:12px 25px; border-radius:10px;">👥 مشاهده دانش‌آموزان</a>
            <a href="export.php" class="btn btn-success" style="text-decoration:none; padding:12px 25px; border-radius:10px;">📥 خروجی اکسل</a>
        </div>

        <div class="footer-art" style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e2e8f0; text-align: center;">
            <p style="font-size: 14px; color: var(--text-gray);">🏫 هنرستان فرصت شیرازی - پنل مدیریت</p>
        </div>
    </div>
</body>
</html>