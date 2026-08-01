<?php
session_start();

// ====== بررسی احراز هویت ======
if (!isset($_SESSION['verified_mobile'])) {
    header('Location: index.php');
    exit;
}

// ====== اتصال به دیتابیس ======
require_once 'includes/database.php';

// ====== پردازش ارسال فرم ======
$errors = [];
$form_data = [];

// ====== تولید لیست روز، ماه، سال ======
function generateDayOptions($selected = 0) {
    $options = '';
    for ($i = 1; $i <= 31; $i++) {
        $sel = ($selected == $i) ? 'selected' : '';
        $options .= "<option value=\"$i\" $sel>$i</option>";
    }
    return $options;
}

function generateMonthOptions($selected = 0) {
    $months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
    ];
    $options = '';
    foreach ($months as $num => $name) {
        $sel = ($selected == $num) ? 'selected' : '';
        $options .= "<option value=\"$num\" $sel>$name</option>";
    }
    return $options;
}

function generateYearOptionsStudent($selected = 0) {
    $options = '';
    for ($i = 1406; $i >= 1380; $i--) {
        $sel = ($selected == $i) ? 'selected' : '';
        $options .= "<option value=\"$i\" $sel>$i</option>";
    }
    return $options;
}

function generateYearOptionsParent($selected = 0) {
    $options = '';
    for ($i = 1385; $i >= 1340; $i--) {
        $sel = ($selected == $i) ? 'selected' : '';
        $options .= "<option value=\"$i\" $sel>$i</option>";
    }
    return $options;
}

function combineDate($year, $month, $day) {
    if ($year && $month && $day) {
        return sprintf("%04d/%02d/%02d", $year, $month, $day);
    }
    return '';
}

function parseDate($date_str) {
    if (empty($date_str)) return ['year' => 0, 'month' => 0, 'day' => 0];
    $parts = explode('/', $date_str);
    if (count($parts) == 3) {
        return ['year' => intval($parts[0]), 'month' => intval($parts[1]), 'day' => intval($parts[2])];
    }
    return ['year' => 0, 'month' => 0, 'day' => 0];
}

function validateNationalCode($code) {
    if (!preg_match('/^[0-9]{10}$/', $code)) return false;
    $code = (string)$code;
    $check = (int)substr($code, 9, 1);
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int)substr($code, $i, 1) * (10 - $i);
    }
    $remainder = $sum % 11;
    if ($remainder < 2) {
        return $check == $remainder;
    } else {
        return $check == (11 - $remainder);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_registration'])) {
    $birth_date = combineDate(
        intval($_POST['birth_year'] ?? 0),
        intval($_POST['birth_month'] ?? 0),
        intval($_POST['birth_day'] ?? 0)
    );
    $father_birth_date = combineDate(
        intval($_POST['father_birth_year'] ?? 0),
        intval($_POST['father_birth_month'] ?? 0),
        intval($_POST['father_birth_day'] ?? 0)
    );
    $mother_birth_date = combineDate(
        intval($_POST['mother_birth_year'] ?? 0),
        intval($_POST['mother_birth_month'] ?? 0),
        intval($_POST['mother_birth_day'] ?? 0)
    );

    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'national_code' => trim($_POST['national_code'] ?? ''),
        'birth_date' => $birth_date,
        'birth_place' => trim($_POST['birth_place'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? 'ایرانی'),
        'nationality_other' => trim($_POST['nationality_other'] ?? ''),
        'religion' => trim($_POST['religion'] ?? ''),
        'denomination' => trim($_POST['denomination'] ?? ''),
        'health_status' => $_POST['health_status'] ?? 'sane',
        'disability_desc' => trim($_POST['disability_desc'] ?? ''),
        'martyr_status' => $_POST['martyr_status'] ?? 'no',
        'martyr_relation' => trim($_POST['martyr_relation'] ?? ''),
        'live_with' => $_POST['live_with'] ?? 'parents',
        'live_with_other' => trim($_POST['live_with_other'] ?? ''),
        'live_with_desc' => trim($_POST['live_with_desc'] ?? ''),
        'previous_school' => trim($_POST['previous_school'] ?? ''),
        'final_gpa' => trim($_POST['final_gpa'] ?? ''),
        'is_employed' => $_POST['is_employed'] ?? 'no',
        'employment_desc' => trim($_POST['employment_desc'] ?? ''),
        'total_children' => intval($_POST['total_children'] ?? 0),
        'child_order' => intval($_POST['child_order'] ?? 0),
        'emergency_phone' => trim($_POST['emergency_phone'] ?? ''),
        'bale_id' => trim($_POST['bale_id'] ?? ''),
        'eita_id' => trim($_POST['eita_id'] ?? ''),
        'shad_id' => trim($_POST['shad_id'] ?? ''),
        'address_text' => trim($_POST['address_text'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'transportation' => $_POST['transportation'] ?? '',
        'latitude' => floatval($_POST['latitude'] ?? 0),
        'longitude' => floatval($_POST['longitude'] ?? 0),
        'father_first_name' => trim($_POST['father_first_name'] ?? ''),
        'father_last_name' => trim($_POST['father_last_name'] ?? ''),
        'father_national_code' => trim($_POST['father_national_code'] ?? ''),
        'father_id_card' => trim($_POST['father_id_card'] ?? ''),
        'father_birth_date' => $father_birth_date,
        'father_education' => $_POST['father_education'] ?? '',
        'father_job' => $_POST['father_job'] ?? '',
        'father_job_other' => trim($_POST['father_job_other'] ?? ''),
        'father_phone' => trim($_POST['father_phone'] ?? ''),
        'mother_first_name' => trim($_POST['mother_first_name'] ?? ''),
        'mother_last_name' => trim($_POST['mother_last_name'] ?? ''),
        'mother_national_code' => trim($_POST['mother_national_code'] ?? ''),
        'mother_birth_date' => $mother_birth_date,
        'mother_education' => $_POST['mother_education'] ?? '',
        'mother_job' => $_POST['mother_job'] ?? '',
        'mother_job_other' => trim($_POST['mother_job_other'] ?? ''),
        'mother_phone' => trim($_POST['mother_phone'] ?? ''),
        'has_pc' => isset($_POST['has_pc']) ? 1 : 0,
        'has_laptop' => isset($_POST['has_laptop']) ? 1 : 0,
        'has_internet' => isset($_POST['has_internet']) ? 1 : 0,
        'has_none' => isset($_POST['has_none']) ? 1 : 0,
        'health_sports' => $_POST['health_sports'] ?? [],
        'other_illness_desc' => trim($_POST['other_illness_desc'] ?? ''),
        'medications' => trim($_POST['medications'] ?? ''),
        'first_priority' => $_POST['first_priority'] ?? '',
        'second_priority' => $_POST['second_priority'] ?? '',
        'third_priority' => $_POST['third_priority'] ?? '',
    ];

    // ====== اعتبارسنجی ======
    if (empty($form_data['first_name'])) $errors['first_name'] = 'نام را وارد کنید.';
    if (empty($form_data['last_name'])) $errors['last_name'] = 'نام خانوادگی را وارد کنید.';
    if (empty($form_data['national_code']) || !validateNationalCode($form_data['national_code'])) {
        $errors['national_code'] = 'کد ملی ۱۰ رقمی معتبر وارد کنید.';
    }
    if (empty($form_data['birth_date'])) $errors['birth_date'] = 'تاریخ تولد را به‌درستی انتخاب کنید.';
    if (empty($form_data['birth_place'])) $errors['birth_place'] = 'محل تولد را وارد کنید.';
    if ($form_data['nationality'] === 'other' && empty($form_data['nationality_other'])) {
        $errors['nationality_other'] = 'لطفاً ملیت خود را وارد کنید.';
    }
    if (empty($form_data['father_first_name'])) $errors['father_first_name'] = 'نام پدر را وارد کنید.';
    if (empty($form_data['father_last_name'])) $errors['father_last_name'] = 'نام خانوادگی پدر را وارد کنید.';
    if (empty($form_data['father_national_code']) || !validateNationalCode($form_data['father_national_code'])) {
        $errors['father_national_code'] = 'کد ملی پدر (۱۰ رقمی معتبر) را وارد کنید.';
    }
    if (empty($form_data['mother_first_name'])) $errors['mother_first_name'] = 'نام مادر را وارد کنید.';
    if (empty($form_data['mother_last_name'])) $errors['mother_last_name'] = 'نام خانوادگی مادر را وارد کنید.';
    if (empty($form_data['mother_national_code']) || !validateNationalCode($form_data['mother_national_code'])) {
        $errors['mother_national_code'] = 'کد ملی مادر (۱۰ رقمی معتبر) را وارد کنید.';
    }
    if (empty($form_data['previous_school'])) $errors['previous_school'] = 'مدرسه قبلی را وارد کنید.';
    if (empty($form_data['final_gpa']) || !is_numeric($form_data['final_gpa']) || $form_data['final_gpa'] < 0 || $form_data['final_gpa'] > 20) {
        $errors['final_gpa'] = 'معدل پایه نهم (۰ تا ۲۰) را وارد کنید.';
    }
    if ($form_data['total_children'] < 1 || $form_data['total_children'] > 20) {
        $errors['total_children'] = 'تعداد فرزندان معتبر (۱ تا ۲۰) وارد کنید.';
    }
    if ($form_data['total_children'] > 1 && $form_data['child_order'] < 1) {
        $errors['child_order'] = 'ترتیب فرزند را وارد کنید.';
    }
    if (empty($form_data['address_text'])) $errors['address_text'] = 'آدرس را وارد کنید.';
    if (!empty($form_data['postal_code']) && !preg_match('/^[0-9]{10}$/', $form_data['postal_code'])) {
        $errors['postal_code'] = 'کد پستی باید ۱۰ رقم باشد.';
    }
    if (empty($form_data['transportation'])) $errors['transportation'] = 'وسیله رفت‌وآمد را انتخاب کنید.';
    if (empty($form_data['first_priority'])) $errors['first_priority'] = 'لطفاً رشته اولویت اول را انتخاب کنید.';
    
    if ($form_data['live_with'] === 'father' || $form_data['live_with'] === 'mother') {
        if (empty($form_data['live_with_desc'])) {
            $errors['live_with_desc'] = 'لطفاً توضیح دهید (مثلاً فوت، طلاق یا ...)';
        }
    }

    if (empty($errors)) {
        $_SESSION['preview_data'] = $form_data;
        $_SESSION['preview_data']['mobile'] = $_SESSION['verified_mobile'];
        header('Location: preview.php');
        exit;
    }
}

if (isset($_SESSION['preview_data']) && empty($form_data)) {
    $form_data = $_SESSION['preview_data'];
    unset($_SESSION['preview_data']);
}

$birth_parts = parseDate($form_data['birth_date'] ?? '');
$father_birth_parts = parseDate($form_data['father_birth_date'] ?? '');
$mother_birth_parts = parseDate($form_data['mother_birth_date'] ?? '');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرم ثبت‌نام هنرستان</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .form-container { max-width: 900px; margin: 0 auto; }
        .form-section {
            background: var(--box-white);
            border-radius: 16px;
            padding: 28px 30px;
            margin-bottom: 25px;
            border-right: 5px solid var(--primary-blue);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .form-section:hover { box-shadow: 0 6px 25px rgba(0,0,0,0.08); }
        .form-section .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f4f8;
        }
        .form-section .section-header .badge {
            background: var(--primary-orange);
            color: #fff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            flex-shrink: 0;
        }
        .form-section .section-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }
        .form-section .section-header .subtitle {
            font-size: 13px;
            color: var(--text-gray);
            font-weight: 400;
            margin-right: auto;
        }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        .form-group label .required {
            color: var(--primary-orange);
            margin-right: 2px;
        }
        .form-group .help-text {
            font-size: 12px;
            color: var(--text-gray);
            margin-top: 3px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Vazirmatn', sans-serif;
            transition: 0.25s ease;
            background: #fff;
            color: var(--text-dark);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(43,108,176,0.10);
        }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-group input:disabled { background: #f7fafc; cursor: not-allowed; }

        .date-select-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .date-select-group select {
            flex: 1;
            padding: 11px 12px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Vazirmatn', sans-serif;
            background: #fff;
            color: var(--text-dark);
            transition: 0.25s ease;
            appearance: auto;
            cursor: pointer;
        }
        .date-select-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(43,108,176,0.10);
        }
        .date-select-group .date-sep {
            font-weight: 700;
            color: var(--text-gray);
            font-size: 18px;
            padding: 0 2px;
        }

        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 5px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-weight: 400;
            font-size: 14px;
            cursor: pointer;
        }
        .radio-group input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-blue);
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 5px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-weight: 400;
            font-size: 14px;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-blue);
            cursor: pointer;
        }

        .conditional-field {
            display: none;
            margin-top: 14px;
            padding: 18px 20px;
            background: #f8fafc;
            border-radius: 12px;
            border-right: 4px solid var(--primary-orange);
        }
        .conditional-field.show { display: block; }

        #map {
            height: 280px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            margin-top: 8px;
            z-index: 1;
        }
        #map-coords {
            font-size: 13px;
            color: var(--text-gray);
            margin-top: 8px;
            text-align: center;
        }

        .health-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #edf2f7;
        }
        .health-item:last-child { border-bottom: none; }
        .health-item .health-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--text-dark);
        }
        .health-item .radio-group { gap: 20px; }
        .health-item .radio-group label { font-size: 13px; }

        .btn-submit {
            width: 100%;
            padding: 18px;
            font-size: 20px;
            font-weight: 700;
            background: var(--primary-orange);
            color: #fff;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: 0.3s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        .btn-submit:hover {
            background: #d9772b;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(237,137,54,0.35);
        }
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-box {
            background: #fff5f5;
            border: 2px solid #E53E3E;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 25px;
        }
        .error-box ul {
            margin: 8px 0 0 0;
            padding-right: 20px;
            color: #E53E3E;
        }
        .error-box ul li { margin-bottom: 4px; }
        .error-field {
            border-color: #E53E3E !important;
            background: #fff5f5 !important;
        }
        .success-box {
            background: #f0fff4;
            border: 2px solid #38A169;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
        }
        .success-box h2 { color: #38A169; font-size: 24px; margin-bottom: 10px; }
        .success-box p { color: var(--text-dark); font-size: 16px; }

        @media (max-width: 768px) {
            .form-section { padding: 20px 16px; }
            .row-2, .row-3 { grid-template-columns: 1fr; }
            .form-section .section-header { flex-wrap: wrap; }
            .form-section .section-header .subtitle { width: 100%; margin-right: 0; }
            #map { height: 200px; }
            .date-select-group { flex-wrap: wrap; }
            .date-select-group select { flex: 1 1 30%; min-width: 60px; }
        }
        @media (max-width: 480px) {
            .form-section { padding: 16px 12px; }
            .health-item { flex-wrap: wrap; gap: 8px; }
            .health-item .radio-group { width: 100%; justify-content: flex-start; }
            .btn-submit { font-size: 17px; padding: 15px; }
            .date-select-group select { flex: 1 1 28%; font-size: 14px; padding: 8px 6px; }
        }
        .success-check { font-size: 64px; color: #38A169; display: block; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container form-container">
        <h1>📋 <span>ثبت‌نام</span> هنرستان</h1>
        <p class="subtitle">لطفاً تمام اطلاعات را با دقت و صداقت کامل تکمیل کنید. فیلدهای <span style="color: var(--primary-orange);">*</span> اجباری هستند.</p>

        <?php if (!empty($errors)): ?>
            <div class="error-box" id="error-box">
                <strong>❌ لطفاً خطاهای زیر را اصلاح کنید:</strong>
                <ul>
                    <?php foreach ($errors as $field => $error): ?>
                        <li data-field="<?php echo htmlspecialchars($field); ?>"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="form.php" id="registration-form" novalidate>

            <!-- ====== بخش ۱: اطلاعات فردی دانش‌آموز ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">1</span>
                    <h2>اطلاعات فردی دانش‌آموز</h2>
                    <span class="subtitle">مشخصات هویتی</span>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>نام <span class="required">*</span></label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required placeholder="نام خود را وارد کنید" class="<?php echo isset($errors['first_name']) ? 'error-field' : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>نام خانوادگی <span class="required">*</span></label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required placeholder="نام خانوادگی خود را وارد کنید" class="<?php echo isset($errors['last_name']) ? 'error-field' : ''; ?>">
                    </div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>کد ملی <span class="required">*</span></label>
                        <input type="text" name="national_code" id="national_code" maxlength="10" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['national_code'] ?? ''); ?>" required placeholder="کد ملی خود را وارد کنید" class="<?php echo isset($errors['national_code']) ? 'error-field' : ''; ?>">
                        <span class="help-text">۱۰ رقم معتبر - ساختار کد ملی بررسی می‌شود</span>
                    </div>
                    <div class="form-group">
                        <label>محل تولد <span class="required">*</span></label>
                        <input type="text" name="birth_place" id="birth_place" value="<?php echo htmlspecialchars($form_data['birth_place'] ?? ''); ?>" required placeholder="محل تولد خود را وارد کنید" class="<?php echo isset($errors['birth_place']) ? 'error-field' : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>تاریخ تولد <span class="required">*</span></label>
                    <div class="date-select-group">
                        <select name="birth_year" id="birth_year" class="<?php echo isset($errors['birth_date']) ? 'error-field' : ''; ?>">
                            <option value="">سال</option>
                            <?php echo generateYearOptionsStudent($birth_parts['year']); ?>
                        </select>
                        <span class="date-sep">/</span>
                        <select name="birth_month" id="birth_month" class="<?php echo isset($errors['birth_date']) ? 'error-field' : ''; ?>">
                            <option value="">ماه</option>
                            <?php echo generateMonthOptions($birth_parts['month']); ?>
                        </select>
                        <span class="date-sep">/</span>
                        <select name="birth_day" id="birth_day" class="<?php echo isset($errors['birth_date']) ? 'error-field' : ''; ?>">
                            <option value="">روز</option>
                            <?php echo generateDayOptions($birth_parts['day']); ?>
                        </select>
                    </div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>ملیت <span class="required">*</span></label>
                        <select name="nationality" id="nationality" required class="<?php echo isset($errors['nationality']) ? 'error-field' : ''; ?>">
                            <option value="ایرانی" <?php echo (($form_data['nationality'] ?? 'ایرانی') === 'ایرانی') ? 'selected' : ''; ?>>ایرانی</option>
                            <option value="افغانی" <?php echo (($form_data['nationality'] ?? '') === 'افغانی') ? 'selected' : ''; ?>>افغانی</option>
                            <option value="other" <?php echo (($form_data['nationality'] ?? '') === 'other') ? 'selected' : ''; ?>>سایر</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>دین</label>
                        <input type="text" name="religion" id="religion" value="<?php echo htmlspecialchars($form_data['religion'] ?? ''); ?>" placeholder="دین خود را وارد کنید">
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['nationality'] ?? '') === 'other') ? 'show' : ''; ?>" id="nationality-other-field">
                    <div class="form-group">
                        <label>ملیت خود را وارد کنید <span class="required">*</span></label>
                        <input type="text" name="nationality_other" id="nationality_other" value="<?php echo htmlspecialchars($form_data['nationality_other'] ?? ''); ?>" placeholder="ملیت خود را وارد کنید" class="<?php echo isset($errors['nationality_other']) ? 'error-field' : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>مذهب</label>
                    <input type="text" name="denomination" id="denomination" value="<?php echo htmlspecialchars($form_data['denomination'] ?? ''); ?>" placeholder="مذهب خود را وارد کنید">
                </div>
            </div>

            <!-- ====== بخش ۲: سلامت و ایثارگری ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">2</span>
                    <h2>سلامت و ایثارگری</h2>
                    <span class="subtitle">اطلاعات تکمیلی</span>
                </div>

                <div class="form-group">
                    <label>وضعیت سالمتی <span class="required">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="health_status" value="sane" <?php echo (($form_data['health_status'] ?? 'sane') === 'sane') ? 'checked' : ''; ?>> سالم</label>
                        <label><input type="radio" name="health_status" value="disabled" <?php echo (($form_data['health_status'] ?? '') === 'disabled') ? 'checked' : ''; ?>> معلول</label>
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['health_status'] ?? '') === 'disabled') ? 'show' : ''; ?>" id="disability-field">
                    <div class="form-group">
                        <label>نوع معلولیت</label>
                        <textarea name="disability_desc" id="disability_desc" rows="2" placeholder="نوع معلولیت را توضیح دهید"><?php echo htmlspecialchars($form_data['disability_desc'] ?? ''); ?></textarea>
                    </div>
                </div>

                <hr style="margin: 18px 0; border: none; border-top: 1px solid #edf2f7;">

                <div class="form-group">
                    <label>وضعیت ایثارگری <span class="required">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="martyr_status" value="no" <?php echo (($form_data['martyr_status'] ?? 'no') === 'no') ? 'checked' : ''; ?>> خیر</label>
                        <label><input type="radio" name="martyr_status" value="yes" <?php echo (($form_data['martyr_status'] ?? '') === 'yes') ? 'checked' : ''; ?>> بلی</label>
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['martyr_status'] ?? '') === 'yes') ? 'show' : ''; ?>" id="martyr-field">
                    <div class="form-group">
                        <label>نسبت ایثارگر با دانش‌آموز <span class="required">*</span></label>
                        <input type="text" name="martyr_relation" id="martyr_relation" value="<?php echo htmlspecialchars($form_data['martyr_relation'] ?? ''); ?>" placeholder="نسبت خود را وارد کنید">
                    </div>
                </div>
            </div>

            <!-- ====== بخش ۳: آدرس و سکونت ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">3</span>
                    <h2>آدرس و مکان سکونت</h2>
                    <span class="subtitle">برای تحلیل پراکندگی</span>
                </div>

                <div class="form-group">
                    <label>آدرس پستی <span class="required">*</span></label>
                    <textarea name="address_text" id="address_text" rows="3" required placeholder="آدرس کامل خود را وارد کنید" class="<?php echo isset($errors['address_text']) ? 'error-field' : ''; ?>"><?php echo htmlspecialchars($form_data['address_text'] ?? ''); ?></textarea>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>کد پستی</label>
                        <input type="text" name="postal_code" id="postal_code" maxlength="10" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['postal_code'] ?? ''); ?>" placeholder="۱۰ رقم" class="<?php echo isset($errors['postal_code']) ? 'error-field' : ''; ?>">
                        <span class="help-text">۱۰ رقم</span>
                    </div>
                    <div class="form-group">
                        <label>شماره تلفن ثابت</label>
                        <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>" placeholder="شماره تلفن را وارد کنید">
                    </div>
                </div>

                <div class="form-group">
                    <label>مکان خود را روی نقشه مشخص کنید</label>
                    <div id="map"></div>
                    <div id="map-coords">📍 برای انتخاب مکان، روی نقشه کلیک کنید.</div>
                    <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($form_data['latitude'] ?? ''); ?>">
                    <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($form_data['longitude'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>وسیله رفت‌وآمد به هنرستان <span class="required">*</span></label>
                    <select name="transportation" id="transportation" required class="<?php echo isset($errors['transportation']) ? 'error-field' : ''; ?>">
                        <option value="">انتخاب کنید</option>
                        <option value="school_service" <?php echo (($form_data['transportation'] ?? '') === 'school_service') ? 'selected' : ''; ?>>سرویس مدرسه</option>
                        <option value="personal_car" <?php echo (($form_data['transportation'] ?? '') === 'personal_car') ? 'selected' : ''; ?>>خودروی شخصی</option>
                        <option value="taxi" <?php echo (($form_data['transportation'] ?? '') === 'taxi') ? 'selected' : ''; ?>>تاکسی</option>
                        <option value="bus" <?php echo (($form_data['transportation'] ?? '') === 'bus') ? 'selected' : ''; ?>>اتوبوس</option>
                        <option value="walk" <?php echo (($form_data['transportation'] ?? '') === 'walk') ? 'selected' : ''; ?>>پیاده</option>
                    </select>
                </div>
            </div>

            <!-- ====== بخش ۴: اطلاعات والدین ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">4</span>
                    <h2>اطلاعات والدین</h2>
                    <span class="subtitle">پدر و مادر</span>
                </div>

                <!-- پدر -->
                <div style="background: #f0f7ff; border-radius: 12px; padding: 16px 20px; margin-bottom: 18px; border-right: 4px solid var(--primary-blue);">
                    <h3 style="font-size: 16px; margin: 0; color: var(--primary-dark);">👨 پدر</h3>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>نام <span class="required">*</span></label>
                        <input type="text" name="father_first_name" id="father_first_name" value="<?php echo htmlspecialchars($form_data['father_first_name'] ?? ''); ?>" required placeholder="نام پدر را وارد کنید" class="<?php echo isset($errors['father_first_name']) ? 'error-field' : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>نام خانوادگی <span class="required">*</span></label>
                        <input type="text" name="father_last_name" id="father_last_name" value="<?php echo htmlspecialchars($form_data['father_last_name'] ?? ''); ?>" required placeholder="نام خانوادگی پدر را وارد کنید" class="<?php echo isset($errors['father_last_name']) ? 'error-field' : ''; ?>">
                    </div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>کد ملی <span class="required">*</span></label>
                        <input type="text" name="father_national_code" id="father_national_code" maxlength="10" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['father_national_code'] ?? ''); ?>" required placeholder="کد ملی پدر را وارد کنید" class="<?php echo isset($errors['father_national_code']) ? 'error-field' : ''; ?>">
                        <span class="help-text">۱۰ رقم معتبر</span>
                    </div>
                    <div class="form-group">
                        <label>شماره شناسنامه</label>
                        <input type="text" name="father_id_card" id="father_id_card" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['father_id_card'] ?? ''); ?>" placeholder="شماره شناسنامه را وارد کنید">
                    </div>
                </div>
                <div class="form-group">
                    <label>تاریخ تولد</label>
                    <div class="date-select-group">
                        <select name="father_birth_year" id="father_birth_year">
                            <option value="">سال</option>
                            <?php echo generateYearOptionsParent($father_birth_parts['year']); ?>
                        </select>
                        <span class="date-sep">/</span>
                        <select name="father_birth_month" id="father_birth_month">
                            <option value="">ماه</option>
                            <?php echo generateMonthOptions($father_birth_parts['month']); ?>
                        </select>
                        <span class="date-sep">/</span>
                        <select name="father_birth_day" id="father_birth_day">
                            <option value="">روز</option>
                            <?php echo generateDayOptions($father_birth_parts['day']); ?>
                        </select>
                    </div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>تحصیلات</label>
                        <select name="father_education" id="father_education">
                            <option value="">انتخاب کنید</option>
                            <option value="سیکل" <?php echo (($form_data['father_education'] ?? '') === 'سیکل') ? 'selected' : ''; ?>>سیکل</option>
                            <option value="دیپلم ناقص" <?php echo (($form_data['father_education'] ?? '') === 'دیپلم ناقص') ? 'selected' : ''; ?>>دیپلم ناقص</option>
                            <option value="دیپلم" <?php echo (($form_data['father_education'] ?? '') === 'دیپلم') ? 'selected' : ''; ?>>دیپلم</option>
                            <option value="فوق دیپلم" <?php echo (($form_data['father_education'] ?? '') === 'فوق دیپلم') ? 'selected' : ''; ?>>فوق دیپلم</option>
                            <option value="لیسانس" <?php echo (($form_data['father_education'] ?? '') === 'لیسانس') ? 'selected' : ''; ?>>لیسانس</option>
                            <option value="فوق لیسانس" <?php echo (($form_data['father_education'] ?? '') === 'فوق لیسانس') ? 'selected' : ''; ?>>فوق لیسانس</option>
                            <option value="دکتری" <?php echo (($form_data['father_education'] ?? '') === 'دکتری') ? 'selected' : ''; ?>>دکتری</option>
                            <option value="حوزوی" <?php echo (($form_data['father_education'] ?? '') === 'حوزوی') ? 'selected' : ''; ?>>حوزوی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>شغل</label>
                        <select name="father_job" id="father_job">
                            <option value="">انتخاب کنید</option>
                            <option value="کارمند" <?php echo (($form_data['father_job'] ?? '') === 'کارمند') ? 'selected' : ''; ?>>کارمند</option>
                            <option value="آزاد" <?php echo (($form_data['father_job'] ?? '') === 'آزاد') ? 'selected' : ''; ?>>آزاد</option>
                            <option value="کارگر" <?php echo (($form_data['father_job'] ?? '') === 'کارگر') ? 'selected' : ''; ?>>کارگر</option>
                            <option value="کشاورز" <?php echo (($form_data['father_job'] ?? '') === 'کشاورز') ? 'selected' : ''; ?>>کشاورز</option>
                            <option value="بازنشسته" <?php echo (($form_data['father_job'] ?? '') === 'بازنشسته') ? 'selected' : ''; ?>>بازنشسته</option>
                            <option value="بیکار" <?php echo (($form_data['father_job'] ?? '') === 'بیکار') ? 'selected' : ''; ?>>بیکار</option>
                            <option value="other" <?php echo (($form_data['father_job'] ?? '') === 'other') ? 'selected' : ''; ?>>سایر</option>
                        </select>
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['father_job'] ?? '') === 'other') ? 'show' : ''; ?>" id="father-job-other-field">
                    <div class="form-group">
                        <label>شغل خود را وارد کنید</label>
                        <input type="text" name="father_job_other" id="father_job_other" value="<?php echo htmlspecialchars($form_data['father_job_other'] ?? ''); ?>" placeholder="شغل خود را وارد کنید">
                    </div>
                </div>
                <div class="form-group">
                    <label>شماره تماس پدر</label>
                    <input type="text" name="father_phone" id="father_phone" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['father_phone'] ?? ''); ?>" placeholder="شماره تماس پدر را وارد کنید">
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #edf2f7;">

                <!-- مادر -->
                <div style="background: #fff5f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 18px; border-right: 4px solid var(--primary-orange);">
                    <h3 style="font-size: 16px; margin: 0; color: var(--primary-dark);">👩 مادر</h3>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>نام <span class="required">*</span></label>
                        <input type="text" name="mother_first_name" id="mother_first_name" value="<?php echo htmlspecialchars($form_data['mother_first_name'] ?? ''); ?>" required placeholder="نام مادر را وارد کنید" class="<?php echo isset($errors['mother_first_name']) ? 'error-field' : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>نام خانوادگی <span class="required">*</span></label>
                        <input type="text" name="mother_last_name" id="mother_last_name" value="<?php echo htmlspecialchars($form_data['mother_last_name'] ?? ''); ?>" required placeholder="نام خانوادگی مادر را وارد کنید" class="<?php echo isset($errors['mother_last_name']) ? 'error-field' : ''; ?>">
                    </div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>کد ملی <span class="required">*</span></label>
                        <input type="text" name="mother_national_code" id="mother_national_code" maxlength="10" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['mother_national_code'] ?? ''); ?>" required placeholder="کد ملی مادر را وارد کنید" class="<?php echo isset($errors['mother_national_code']) ? 'error-field' : ''; ?>">
                        <span class="help-text">۱۰ رقم معتبر</span>
                    </div>
                    <div class="form-group">
                        <label>شماره شناسنامه</label>
                        <input type="text" name="mother_id_card" id="mother_id_card" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['mother_id_card'] ?? ''); ?>" placeholder="شماره شناسنامه را وارد کنید">
                    </div>
                </div>
                <div class="form-group">
                    <label>تاریخ تولد مادر</label>
                    <div class="date-select-group">
                        <select name="mother_birth_year" id="mother_birth_year">
                            <option value="">سال</option>
                            <?php echo generateYearOptionsParent($mother_birth_parts['year']); ?>
                        </select>
                        <span class="date-sep">/</span>
                        <select name="mother_birth_month" id="mother_birth_month">
                            <option value="">ماه</option>
                            <?php echo generateMonthOptions($mother_birth_parts['month']); ?>
                        </select>
                        <span class="date-sep">/</span>
                        <select name="mother_birth_day" id="mother_birth_day">
                            <option value="">روز</option>
                            <?php echo generateDayOptions($mother_birth_parts['day']); ?>
                        </select>
                    </div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>تحصیلات</label>
                        <select name="mother_education" id="mother_education">
                            <option value="">انتخاب کنید</option>
                            <option value="سیکل" <?php echo (($form_data['mother_education'] ?? '') === 'سیکل') ? 'selected' : ''; ?>>سیکل</option>
                            <option value="دیپلم ناقص" <?php echo (($form_data['mother_education'] ?? '') === 'دیپلم ناقص') ? 'selected' : ''; ?>>دیپلم ناقص</option>
                            <option value="دیپلم" <?php echo (($form_data['mother_education'] ?? '') === 'دیپلم') ? 'selected' : ''; ?>>دیپلم</option>
                            <option value="فوق دیپلم" <?php echo (($form_data['mother_education'] ?? '') === 'فوق دیپلم') ? 'selected' : ''; ?>>فوق دیپلم</option>
                            <option value="لیسانس" <?php echo (($form_data['mother_education'] ?? '') === 'لیسانس') ? 'selected' : ''; ?>>لیسانس</option>
                            <option value="فوق لیسانس" <?php echo (($form_data['mother_education'] ?? '') === 'فوق لیسانس') ? 'selected' : ''; ?>>فوق لیسانس</option>
                            <option value="دکتری" <?php echo (($form_data['mother_education'] ?? '') === 'دکتری') ? 'selected' : ''; ?>>دکتری</option>
                            <option value="حوزوی" <?php echo (($form_data['mother_education'] ?? '') === 'حوزوی') ? 'selected' : ''; ?>>حوزوی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>شغل</label>
                        <select name="mother_job" id="mother_job">
                            <option value="">انتخاب کنید</option>
                            <option value="خانه‌دار" <?php echo (($form_data['mother_job'] ?? '') === 'خانه‌دار') ? 'selected' : ''; ?>>خانه‌دار</option>
                            <option value="کارمند" <?php echo (($form_data['mother_job'] ?? '') === 'کارمند') ? 'selected' : ''; ?>>کارمند</option>
                            <option value="آزاد" <?php echo (($form_data['mother_job'] ?? '') === 'آزاد') ? 'selected' : ''; ?>>آزاد</option>
                            <option value="کارگر" <?php echo (($form_data['mother_job'] ?? '') === 'کارگر') ? 'selected' : ''; ?>>کارگر</option>
                            <option value="بازنشسته" <?php echo (($form_data['mother_job'] ?? '') === 'بازنشسته') ? 'selected' : ''; ?>>بازنشسته</option>
                            <option value="بیکار" <?php echo (($form_data['mother_job'] ?? '') === 'بیکار') ? 'selected' : ''; ?>>بیکار</option>
                            <option value="other" <?php echo (($form_data['mother_job'] ?? '') === 'other') ? 'selected' : ''; ?>>سایر</option>
                        </select>
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['mother_job'] ?? '') === 'other') ? 'show' : ''; ?>" id="mother-job-other-field">
                    <div class="form-group">
                        <label>شغل خود را وارد کنید</label>
                        <input type="text" name="mother_job_other" id="mother_job_other" value="<?php echo htmlspecialchars($form_data['mother_job_other'] ?? ''); ?>" placeholder="شغل خود را وارد کنید">
                    </div>
                </div>
                <div class="form-group">
                    <label>شماره تماس مادر</label>
                    <input type="text" name="mother_phone" id="mother_phone" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['mother_phone'] ?? ''); ?>" placeholder="شماره تماس مادر را وارد کنید">
                </div>
            </div>

            <!-- ====== بخش ۵: زندگی و اطلاعات تکمیلی ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">5</span>
                    <h2>زندگی و اطلاعات تکمیلی</h2>
                    <span class="subtitle">خانواده، شغل و ارتباطات</span>
                </div>

                <div class="form-group">
                    <label>دانش‌آموز با چه کسی زندگی می‌کند؟ <span class="required">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="live_with" value="parents" <?php echo (($form_data['live_with'] ?? 'parents') === 'parents') ? 'checked' : ''; ?>> پدر و مادر</label>
                        <label><input type="radio" name="live_with" value="father" <?php echo (($form_data['live_with'] ?? '') === 'father') ? 'checked' : ''; ?>> پدر</label>
                        <label><input type="radio" name="live_with" value="mother" <?php echo (($form_data['live_with'] ?? '') === 'mother') ? 'checked' : ''; ?>> مادر</label>
                        <label><input type="radio" name="live_with" value="other" <?php echo (($form_data['live_with'] ?? '') === 'other') ? 'checked' : ''; ?>> سایر</label>
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['live_with'] ?? '') === 'other') ? 'show' : ''; ?>" id="live-with-other-field">
                    <div class="form-group">
                        <label>توضیحات (سایر) <span class="required">*</span></label>
                        <input type="text" name="live_with_other" id="live_with_other" value="<?php echo htmlspecialchars($form_data['live_with_other'] ?? ''); ?>" placeholder="مثلاً پدربزرگ و مادربزرگ">
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['live_with'] ?? '') === 'father' || ($form_data['live_with'] ?? '') === 'mother') ? 'show' : ''; ?>" id="live-with-desc-field">
                    <div class="form-group">
                        <label>توضیحات (مثلاً فوت، طلاق یا ...) <span class="required">*</span></label>
                        <input type="text" name="live_with_desc" id="live_with_desc" value="<?php echo htmlspecialchars($form_data['live_with_desc'] ?? ''); ?>" placeholder="علت را وارد کنید" class="<?php echo isset($errors['live_with_desc']) ? 'error-field' : ''; ?>">
                    </div>
                </div>

                <hr style="margin: 18px 0; border: none; border-top: 1px solid #edf2f7;">

                <div class="row-2">
                    <div class="form-group">
                        <label>تعداد فرزندان خانواده <span class="required">*</span></label>
                        <input type="number" name="total_children" id="total_children" min="1" max="20" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['total_children'] ?? ''); ?>" required placeholder="تعداد فرزندان را وارد کنید" class="<?php echo isset($errors['total_children']) ? 'error-field' : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>دانش‌آموز فرزند چندم است؟</label>
                        <input type="number" name="child_order" id="child_order" min="1" max="20" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['child_order'] ?? ''); ?>" placeholder="ترتیب فرزند را وارد کنید" class="<?php echo isset($errors['child_order']) ? 'error-field' : ''; ?>">
                        <span class="help-text">در صورت تک‌فرزند بودن، نیازی به پر کردن نیست</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>آیا دانش‌آموز شاغل است؟ <span class="required">*</span></label>
                    <div class="radio-group">
                        <label><input type="radio" name="is_employed" value="no" <?php echo (($form_data['is_employed'] ?? 'no') === 'no') ? 'checked' : ''; ?>> خیر</label>
                        <label><input type="radio" name="is_employed" value="yes" <?php echo (($form_data['is_employed'] ?? '') === 'yes') ? 'checked' : ''; ?>> بله</label>
                    </div>
                </div>
                <div class="conditional-field <?php echo (($form_data['is_employed'] ?? '') === 'yes') ? 'show' : ''; ?>" id="employment-field">
                    <div class="form-group">
                        <label>مشخصات شغل <span class="required">*</span></label>
                        <textarea name="employment_desc" id="employment_desc" rows="2" placeholder="مشخصات شغل را وارد کنید"><?php echo htmlspecialchars($form_data['employment_desc'] ?? ''); ?></textarea>
                    </div>
                </div>

                <hr style="margin: 18px 0; border: none; border-top: 1px solid #edf2f7;">

                <div class="row-2">
                    <div class="form-group">
                        <label>شماره تماس در موارد خاص</label>
                        <input type="text" name="emergency_phone" id="emergency_phone" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['emergency_phone'] ?? ''); ?>" placeholder="شماره تماس را وارد کنید">
                    </div>
                    <div class="form-group">
                        <label>شماره شاد</label>
                        <input type="text" name="shad_id" id="shad_id" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['shad_id'] ?? ''); ?>" placeholder="شماره شاد را وارد کنید">
                    </div>
                </div>
                <div class="row-2">
                    <div class="form-group">
                        <label>شماره بله</label>
                        <input type="text" name="bale_id" id="bale_id" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['bale_id'] ?? ''); ?>" placeholder="شماره بله را وارد کنید">
                    </div>
                    <div class="form-group">
                        <label>شماره ایتا</label>
                        <input type="text" name="eita_id" id="eita_id" inputmode="numeric" pattern="[0-9]*" value="<?php echo htmlspecialchars($form_data['eita_id'] ?? ''); ?>" placeholder="شماره ایتا را وارد کنید">
                    </div>
                </div>
            </div>

            <!-- ====== بخش ۶: امکانات سخت‌افزاری ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">6</span>
                    <h2>امکانات سخت‌افزاری</h2>
                    <span class="subtitle">برای کلاس‌های آنلاین</span>
                </div>

                <div class="form-group">
                    <label>کدام یک از موارد زیر را در اختیار دارید؟ <span class="required">*</span></label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="has_pc" value="1" <?php echo (!empty($form_data['has_pc'])) ? 'checked' : ''; ?>> رایانه شخصی (PC)</label>
                        <label><input type="checkbox" name="has_laptop" value="1" <?php echo (!empty($form_data['has_laptop'])) ? 'checked' : ''; ?>> لپ‌تاپ</label>
                        <label><input type="checkbox" name="has_internet" value="1" <?php echo (!empty($form_data['has_internet'])) ? 'checked' : ''; ?>> اینترنت پرسرعت</label>
                        <label><input type="checkbox" name="has_none" value="1" <?php echo (!empty($form_data['has_none'])) ? 'checked' : ''; ?>> هیچ‌کدام</label>
                    </div>
                    <span class="help-text">در صورت انتخاب «هیچ‌کدام»، گزینه‌های دیگر غیرفعال می‌شوند.</span>
                </div>
            </div>

            <!-- ====== بخش ۷: مدرسه قبلی و معدل ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">7</span>
                    <h2>مدرسه قبلی و معدل</h2>
                    <span class="subtitle">سوابق تحصیلی</span>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>مدرسه راهنمایی محل تحصیل <span class="required">*</span></label>
                        <input type="text" name="previous_school" id="previous_school" value="<?php echo htmlspecialchars($form_data['previous_school'] ?? ''); ?>" required placeholder="نام مدرسه را وارد کنید" class="<?php echo isset($errors['previous_school']) ? 'error-field' : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>معدل نهایی پایه نهم <span class="required">*</span></label>
                        <input type="text" name="final_gpa" id="final_gpa" step="0.01" min="0" max="20" inputmode="numeric" pattern="[0-9.]*" value="<?php echo htmlspecialchars($form_data['final_gpa'] ?? ''); ?>" required placeholder="معدل را وارد کنید" class="<?php echo isset($errors['final_gpa']) ? 'error-field' : ''; ?>">
                        <!-- <input type="number" name="final_gpa" id="final_gpa" step="0.01" min="0" max="20" value="<?php echo htmlspecialchars($form_data['final_gpa'] ?? ''); ?>" required placeholder="معدل را وارد کنید" class="<?php echo isset($errors['final_gpa']) ? 'error-field' : ''; ?>"> -->

                        <span class="help-text">عدد بین ۰ تا ۲۰ با دو رقم اعشار</span>
                    </div>
                </div>
            </div>

            <!-- ====== بخش ۸: سلامت ورزشی ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">8</span>
                    <h2>وضعیت سلامت ورزشی</h2>
                    <span class="subtitle">برای درس تربیت بدنی</span>
                </div>

                <div style="background: #fffbeb; border-right: 4px solid #f6ad55; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
                    <p style="font-size: 14px; color: #744210; margin: 0;">⚠️ در صورت انتخاب «دارد» برای هر مورد، ارائه گواهی پزشک در روز ثبت‌نام حضوری الزامی است.</p>
                </div>

                <?php
                $health_items = [
                    'heart_disease' => 'بیماری قلبی-ریوی',
                    'asthma' => 'آسم و آلرژی',
                    'hypertension' => 'فشارخون',
                    'thalassemia' => 'تالاسمی',
                    'fracture_history' => 'سابقه شکستگی',
                    'joint_disorders' => 'ناراحتی‌های مفصلی',
                    'diabetes' => 'دیابت',
                    'cancer' => 'انواع سرطان',
                    'vision_hearing_disorders' => 'اختلالات بینایی و شنوایی',
                    'epilepsy' => 'صرع',
                    'surgery_history' => 'سابقه عمل جراحی',
                    'coagulation_disorders' => 'بیماری‌های انعقادی',
                    'genetic_disorders' => 'اختلالات ژنتیکی و مادرزادی',
                    'balance_disorders' => 'اختلالات تعادل حرکتی',
                    'other_illness' => 'سایر (با ذکر نوع)'
                ];
                $selected_health = $form_data['health_sports'] ?? [];
                ?>

                <?php foreach ($health_items as $key => $label): ?>
                    <div class="health-item">
                        <span class="health-label"><?php echo $label; ?></span>
                        <div class="radio-group">
                            <label><input type="radio" name="health_sports[<?php echo $key; ?>]" value="<?php echo $key; ?>" <?php echo (in_array($key, $selected_health)) ? 'checked' : ''; ?>> دارد</label>
                            <label><input type="radio" name="health_sports[<?php echo $key; ?>]" value="" <?php echo (!in_array($key, $selected_health)) ? 'checked' : ''; ?>> ندارد</label>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="form-group" style="margin-top: 16px;">
                    <label>توضیحات سایر بیماری‌ها</label>
                    <textarea name="other_illness_desc" id="other_illness_desc" rows="2" placeholder="توضیحات را وارد کنید"><?php echo htmlspecialchars($form_data['other_illness_desc'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>داروهای مصرفی</label>
                    <textarea name="medications" id="medications" rows="2" placeholder="داروهای مصرفی را وارد کنید"><?php echo htmlspecialchars($form_data['medications'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- ====== بخش ۹: انتخاب رشته ====== -->
            <div class="form-section">
                <div class="section-header">
                    <span class="badge">9</span>
                    <h2>انتخاب رشته تحصیلی</h2>
                    <span class="subtitle">اولویت‌های خود را مشخص کنید</span>
                </div>

                <div class="form-group">
                    <label>اولویت اول (اجباری) <span class="required">*</span></label>
                    <select name="first_priority" id="first_priority" required class="<?php echo isset($errors['first_priority']) ? 'error-field' : ''; ?>">
                        <option value="">انتخاب کنید</option>
                        <option value="computer" <?php echo (($form_data['first_priority'] ?? '') === 'computer') ? 'selected' : ''; ?>>کامپیوتر (شبکه و نرم‌افزار)</option>
                        <option value="mechanics" <?php echo (($form_data['first_priority'] ?? '') === 'mechanics') ? 'selected' : ''; ?>>مکانیک (خودرو)</option>
                        <option value="electronics" <?php echo (($form_data['first_priority'] ?? '') === 'electronics') ? 'selected' : ''; ?>>الکترونیک</option>
                    </select>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>اولویت دوم (اختیاری)</label>
                        <select name="second_priority" id="second_priority">
                            <option value="">انتخاب کنید</option>
                            <option value="computer" <?php echo (($form_data['second_priority'] ?? '') === 'computer') ? 'selected' : ''; ?>>کامپیوتر (شبکه و نرم‌افزار)</option>
                            <option value="mechanics" <?php echo (($form_data['second_priority'] ?? '') === 'mechanics') ? 'selected' : ''; ?>>مکانیک (خودرو)</option>
                            <option value="electronics" <?php echo (($form_data['second_priority'] ?? '') === 'electronics') ? 'selected' : ''; ?>>الکترونیک</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>اولویت سوم (اختیاری)</label>
                        <select name="third_priority" id="third_priority">
                            <option value="">انتخاب کنید</option>
                            <option value="computer" <?php echo (($form_data['third_priority'] ?? '') === 'computer') ? 'selected' : ''; ?>>کامپیوتر (شبکه و نرم‌افزار)</option>
                            <option value="mechanics" <?php echo (($form_data['third_priority'] ?? '') === 'mechanics') ? 'selected' : ''; ?>>مکانیک (خودرو)</option>
                            <option value="electronics" <?php echo (($form_data['third_priority'] ?? '') === 'electronics') ? 'selected' : ''; ?>>الکترونیک</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <button type="submit" name="submit_registration" class="btn-submit" id="submit-btn">🚀 ثبت‌نام نهایی</button>
                <p style="font-size: 13px; color: var(--text-gray); margin-top: 12px;">پس از ثبت، اطلاعات در صفحه پیش‌نمایش نمایش داده می‌شود.</p>
            </div>

        </form>

        <div class="footer-art" style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e2e8f0; text-align: center;">
            <p style="font-size: 16px; font-weight: 700; color: var(--primary-dark);">🏫 هنرستان <span style="color: var(--primary-orange);">فرصت شیرازی</span></p>
            <p style="font-size: 13px; color: var(--text-gray); margin-top: 5px;">ثبت‌نام پایه دهم - سال تحصیلی ۱۴۰۶-۱۴۰۵</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // -------- ملیت (سایر) --------
            const nationalitySelect = document.getElementById('nationality');
            const nationalityOtherField = document.getElementById('nationality-other-field');
            if (nationalitySelect) {
                nationalitySelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        nationalityOtherField.classList.add('show');
                    } else {
                        nationalityOtherField.classList.remove('show');
                    }
                });
            }

            // -------- سلامت --------
            const healthRadios = document.querySelectorAll('input[name="health_status"]');
            const disabilityField = document.getElementById('disability-field');
            healthRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'disabled') {
                        disabilityField.classList.add('show');
                    } else {
                        disabilityField.classList.remove('show');
                    }
                });
            });

            // -------- ایثارگری --------
            const martyrRadios = document.querySelectorAll('input[name="martyr_status"]');
            const martyrField = document.getElementById('martyr-field');
            martyrRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'yes') {
                        martyrField.classList.add('show');
                    } else {
                        martyrField.classList.remove('show');
                    }
                });
            });

            // -------- زندگی با چه کسی --------
            const liveRadios = document.querySelectorAll('input[name="live_with"]');
            const liveOtherField = document.getElementById('live-with-other-field');
            const liveDescField = document.getElementById('live-with-desc-field');
            liveRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'other') {
                        liveOtherField.classList.add('show');
                    } else {
                        liveOtherField.classList.remove('show');
                    }
                    if (this.value === 'father' || this.value === 'mother') {
                        liveDescField.classList.add('show');
                    } else {
                        liveDescField.classList.remove('show');
                    }
                });
            });

            // -------- شاغل بودن --------
            const employedRadios = document.querySelectorAll('input[name="is_employed"]');
            const employmentField = document.getElementById('employment-field');
            employedRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'yes') {
                        employmentField.classList.add('show');
                    } else {
                        employmentField.classList.remove('show');
                    }
                });
            });

            // -------- شغل پدر (سایر) --------
            const fatherJobSelect = document.getElementById('father_job');
            const fatherJobOtherField = document.getElementById('father-job-other-field');
            if (fatherJobSelect) {
                fatherJobSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        fatherJobOtherField.classList.add('show');
                    } else {
                        fatherJobOtherField.classList.remove('show');
                    }
                });
            }

            // -------- شغل مادر (سایر) --------
            const motherJobSelect = document.getElementById('mother_job');
            const motherJobOtherField = document.getElementById('mother-job-other-field');
            if (motherJobSelect) {
                motherJobSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        motherJobOtherField.classList.add('show');
                    } else {
                        motherJobOtherField.classList.remove('show');
                    }
                });
            }

            // -------- تعداد فرزندان --------
            const totalChildrenInput = document.getElementById('total_children');
            const childOrderInput = document.getElementById('child_order');
            function toggleChildOrder() {
                const val = parseInt(totalChildrenInput.value) || 0;
                if (val > 1) {
                    childOrderInput.disabled = false;
                    childOrderInput.style.opacity = '1';
                    childOrderInput.placeholder = 'ترتیب فرزند را وارد کنید';
                } else {
                    childOrderInput.disabled = true;
                    childOrderInput.style.opacity = '0.5';
                    childOrderInput.value = '';
                    childOrderInput.placeholder = 'تک‌فرزند - نیازی به پر کردن نیست';
                }
            }
            if (totalChildrenInput) {
                totalChildrenInput.addEventListener('input', toggleChildOrder);
                toggleChildOrder();
            }

            // -------- امکانات سخت‌افزاری --------
            const noneCheckbox = document.querySelector('input[name="has_none"]');
            const otherDeviceCheckboxes = document.querySelectorAll('input[name="has_pc"], input[name="has_laptop"], input[name="has_internet"]');
            if (noneCheckbox) {
                noneCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        otherDeviceCheckboxes.forEach(function(cb) {
                            cb.checked = false;
                            cb.disabled = true;
                        });
                    } else {
                        otherDeviceCheckboxes.forEach(function(cb) {
                            cb.disabled = false;
                        });
                    }
                });
                if (noneCheckbox.checked) {
                    otherDeviceCheckboxes.forEach(function(cb) { cb.disabled = true; });
                }
            }

            // -------- اعتبارسنجی سلامت ورزشی --------
            const form = document.getElementById('registration-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const healthGroups = document.querySelectorAll('.health-item');
                    let allValid = true;
                    healthGroups.forEach(function(group) {
                        const radios = group.querySelectorAll('input[type="radio"]');
                        let checked = false;
                        radios.forEach(function(r) {
                            if (r.checked) checked = true;
                        });
                        if (!checked) {
                            allValid = false;
                            group.style.background = '#fff5f5';
                            group.style.borderRadius = '8px';
                            group.style.padding = '4px';
                            setTimeout(function() {
                                group.style.background = '';
                                group.style.padding = '';
                            }, 2000);
                        }
                    });
                    if (!allValid) {
                        e.preventDefault();
                        alert('لطفاً برای هر مورد در بخش سلامت ورزشی، یکی از گزینه‌های "دارد" یا "ندارد" را انتخاب کنید.');
                    }
                });
            }

            // -------- نقشه --------
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const coordsDisplay = document.getElementById('map-coords');

            let defaultLat = 29.5918;
            let defaultLng = 52.5837;

            if (latInput && latInput.value && lngInput && lngInput.value) {
                defaultLat = parseFloat(latInput.value);
                defaultLng = parseFloat(lngInput.value);
            }

            const map = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

            function updateCoords(lat, lng) {
                if (latInput && lngInput && coordsDisplay) {
                    latInput.value = lat.toFixed(7);
                    lngInput.value = lng.toFixed(7);
                    coordsDisplay.textContent = '📍 مختصات انتخاب شده: ' + lat.toFixed(6) + ' , ' + lng.toFixed(6);
                }
            }

            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                updateCoords(lat, lng);
            });

            marker.on('dragend', function(e) {
                const pos = marker.getLatLng();
                updateCoords(pos.lat, pos.lng);
            });

            if (latInput && latInput.value && lngInput && lngInput.value) {
                updateCoords(parseFloat(latInput.value), parseFloat(lngInput.value));
            } else if (coordsDisplay) {
                updateCoords(defaultLat, defaultLng);
            }

            <?php if (!empty($errors)): ?>
                const errorFields = document.querySelectorAll('.error-field');
                if (errorFields.length > 0) {
                    const firstField = errorFields[0];
                    firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(function() {
                        firstField.focus();
                        if (firstField.select) {
                            firstField.select();
                        }
                    }, 600);
                } else {
                    const errorBox = document.getElementById('error-box');
                    if (errorBox) {
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            <?php endif; ?>

        });


            // ====== تبدیل اعداد فارسی به انگلیسی در همه فیلدهای عددی ======
document.querySelectorAll('input[inputmode="numeric"], input[type="tel"], input[type="number"]').forEach(function(input) {
    input.addEventListener('input', function(e) {
        // تبدیل اعداد فارسی (۱۲۳۴۵۶۷۸۹۰) به انگلیسی (1234567890)
        this.value = this.value.replace(/[۰-۹]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1728);
        });
        // همچنین اعداد عربی (١٢٣٤٥٦٧٨٩٠) را هم تبدیل کند
        this.value = this.value.replace(/[٠-٩]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1632);
        });
    });
});
    </script>


</body>
</html>