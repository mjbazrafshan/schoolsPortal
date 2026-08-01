<?php
require_once '../includes/admin_functions.php';
require_once '../includes/database.php';
requireAdminLogin();

$pdo = getDBConnection();
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = $_GET['search'] ?? '';
$result = getStudents($pdo, $search, $page, 20);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست دانش‌آموزان</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 0 auto; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 30px; flex-wrap: wrap; gap: 10px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: right; border-bottom: 1px solid #edf2f7; }
        th { background: #f7fafc; font-weight: 700; color: var(--text-dark); }
        tr:hover { background: #f7fafc; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .search-box { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-box input { padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-size: 14px; min-width: 250px; }
        .search-box button { padding: 10px 20px; background: var(--primary-blue); color: #fff; border: none; border-radius: 10px; cursor: pointer; }
        .pagination { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .pagination a { padding: 8px 16px; background: #f7fafc; border-radius: 8px; text-decoration: none; color: var(--text-dark); }
        .pagination a.active { background: var(--primary-blue); color: #fff; }
        @media (max-width: 768px) { .search-box input { min-width: 100%; } }
    </style>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>👥 <span>لیست</span> دانش‌آموزان</h1>
            <a href="dashboard.php" style="text-decoration:none; color:var(--primary-blue);">← بازگشت</a>
        </div>

        <form method="GET" action="students.php" class="search-box" style="margin-bottom: 20px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="جستجو بر اساس نام، کد ملی یا شماره موبایل...">
            <button type="submit">🔍 جستجو</button>
            <?php if (!empty($search)): ?>
                <a href="students.php" style="color:#E53E3E; text-decoration:none;">✖️ پاک کردن</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ردیف</th>
                        <th>نام و نام خانوادگی</th>
                        <th>کد ملی</th>
                        <th>شماره موبایل</th>
                        <th>رشته انتخابی</th>
                        <th>تاریخ ثبت</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['students'])): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px;">هیچ دانش‌آموزی ثبت‌نام نشده است.</td></tr>
                    <?php else: ?>
                        <?php foreach ($result['students'] as $index => $student): ?>
                            <tr>
                                <td><?php echo ($page - 1) * 20 + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['national_code']); ?></td>
                                <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                                <td><?php 
                                    $map = ['computer' => 'کامپیوتر', 'mechanics' => 'مکانیک', 'electronics' => 'الکترونیک'];
                                    echo $map[$student['first_priority']] ?? '—';
                                ?></td>
                                <td><?php echo date('Y/m/d', strtotime($student['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-pending">در انتظار تأیید</span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $student['id']; ?>" style="color:var(--primary-blue); text-decoration:none;">🔍 مشاهده</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['total'] > $result['perPage']): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= ceil($result['total'] / $result['perPage']); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>