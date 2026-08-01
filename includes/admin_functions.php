<?php
session_start();

// ====== بررسی احراز هویت مدیر ======
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin/login.php');
        exit;
    }
}

// ====== دریافت آمار کلی ======
function getDashboardStats($pdo) {
    $stats = [];
    
    // تعداد کل دانش‌آموزان
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $stats['total'] = $stmt->fetch()['total'];
    
    // تفکیک رشته
    $stmt = $pdo->query("SELECT first_priority, COUNT(*) as count FROM field_choices GROUP BY first_priority");
    $stats['fields'] = $stmt->fetchAll();
    
    // تفکیک وضعیت سلامت
    $stmt = $pdo->query("SELECT health_status, COUNT(*) as count FROM students GROUP BY health_status");
    $stats['health'] = $stmt->fetchAll();
    
    return $stats;
}

// ====== دریافت لیست دانش‌آموزان با جستجو (اصلاح شده) ======
function getStudents($pdo, $search = '', $page = 1, $perPage = 20) {
    $offset = ($page - 1) * $perPage;
    $where = '';
    $params = [];
    
    if (!empty($search)) {
        $where = "WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.national_code LIKE ? OR s.mobile LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // کوئری اصلی با LIMIT و OFFSET (با استفاده از bindValue و PARAM_INT)
    $sql = "SELECT s.*, f.first_priority 
            FROM students s 
            LEFT JOIN field_choices f ON s.id = f.student_id 
            $where 
            ORDER BY s.id DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind کردن پارامترهای جستجو (رشته‌ای)
    foreach ($params as $idx => $value) {
        $stmt->bindValue($idx + 1, $value, PDO::PARAM_STR);
    }
    
    // Bind کردن LIMIT و OFFSET با نوع INTEGER
    $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    // ====== کوئری شمارش کل ======
    $countSql = "SELECT COUNT(*) as total FROM students s $where";
    $stmt = $pdo->prepare($countSql);
    
    // Bind کردن پارامترهای جستجو برای کوئری شمارش
    foreach ($params as $idx => $value) {
        $stmt->bindValue($idx + 1, $value, PDO::PARAM_STR);
    }
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    
    return [
        'students' => $students,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage
    ];
}

// ====== دریافت اطلاعات یک دانش‌آموز ======
function getStudentDetail($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if (!$student) return null;
    
    // والدین
    $stmt = $pdo->prepare("SELECT * FROM parents WHERE student_id = ?");
    $stmt->execute([$id]);
    $parents = $stmt->fetchAll();
    
    // امکانات
    $stmt = $pdo->prepare("SELECT * FROM student_devices WHERE student_id = ?");
    $stmt->execute([$id]);
    $devices = $stmt->fetch();
    
    // رشته
    $stmt = $pdo->prepare("SELECT * FROM field_choices WHERE student_id = ?");
    $stmt->execute([$id]);
    $fields = $stmt->fetch();
    
    // سلامت ورزشی
    $stmt = $pdo->prepare("SELECT * FROM health_declarations WHERE student_id = ?");
    $stmt->execute([$id]);
    $health = $stmt->fetch();
    
    return [
        'student' => $student,
        'parents' => $parents,
        'devices' => $devices,
        'fields' => $fields,
        'health' => $health
    ];
}

// ====== تغییر وضعیت ثبت‌نام ======
function updateStudentStatus($pdo, $id, $status) {
    $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}




?>