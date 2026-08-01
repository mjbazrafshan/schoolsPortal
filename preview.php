<?php
session_start();

// ====== بررسی وجود اطلاعات در جلسه ======
if (!isset($_SESSION['preview_data']) || empty($_SESSION['preview_data'])) {
    header('Location: form.php');
    exit;
}

// ====== دریافت اطلاعات از جلسه ======
$data = $_SESSION['preview_data'];

// ====== پردازش تأیید نهایی ======
if (isset($_POST['confirm_registration'])) {
    // اتصال به دیتابیس
    require_once 'includes/database.php';
    
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // ====== درج اطلاعات دانش‌آموز (اصلاح شده: ۳۳ placeholder) ======
        $stmt = $pdo->prepare("INSERT INTO students (
            mobile, first_name, last_name, national_code,
            birth_date, birth_place, nationality, religion, denomination,
            health_status, disability_description,
            martyr_status, martyr_relation,
            live_with, live_with_other_desc, live_with_desc,
            previous_school, final_gpa,
            is_employed, employment_description,
            total_children, child_order,
            emergency_phone, bale_id, eita_id, shad_id,
            address_text, postal_code, phone_number,
            transportation, latitude, longitude,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            NOW()
        )");

        $stmt->execute([
            $data['mobile'],
            $data['first_name'],
            $data['last_name'],
            $data['national_code'],
            $data['birth_date'],
            $data['birth_place'],
            $data['nationality'],
            $data['religion'] ?: null,
            $data['denomination'] ?: null,
            $data['health_status'],
            $data['disability_desc'] ?: null,
            $data['martyr_status'],
            $data['martyr_relation'] ?: null,
            $data['live_with'],
            $data['live_with_other'] ?: null,
            $data['live_with_desc'] ?: null,
            $data['previous_school'],
            $data['final_gpa'],
            $data['is_employed'],
            $data['employment_desc'] ?: null,
            $data['total_children'],
            $data['total_children'] > 1 ? $data['child_order'] : 1,
            $data['emergency_phone'] ?: null,
            $data['bale_id'] ?: null,
            $data['eita_id'] ?: null,
            $data['shad_id'] ?: null,
            $data['address_text'],
            $data['postal_code'] ?: null,
            $data['phone_number'] ?: null,
            $data['transportation'],
            $data['latitude'] ?: null,
            $data['longitude'] ?: null
        ]);

        $student_id = $pdo->lastInsertId();

        // ====== والدین (پدر) ======
        $father_full_name = trim(($data['father_first_name'] ?? '') . ' ' . ($data['father_last_name'] ?? ''));
        $stmt = $pdo->prepare("INSERT INTO parents (
            student_id, parent_type, full_name, national_code,
            id_card_number, birth_date, education, job, phone
        ) VALUES (?, 'father', ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $student_id,
            $father_full_name,
            $data['father_national_code'],
            $data['father_id_card'] ?: null,
            $data['father_birth_date'] ?: null,
            $data['father_education'] ?: null,
            ($data['father_job'] === 'other') ? $data['father_job_other'] : $data['father_job'],
            $data['father_phone'] ?: null
        ]);

        // ====== والدین (مادر) ======
        $mother_full_name = trim(($data['mother_first_name'] ?? '') . ' ' . ($data['mother_last_name'] ?? ''));
        $stmt = $pdo->prepare("INSERT INTO parents (
            student_id, parent_type, full_name, national_code,
            birth_date, education, job, phone
        ) VALUES (?, 'mother', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $student_id,
            $mother_full_name,
            $data['mother_national_code'],
            $data['mother_birth_date'] ?: null,
            $data['mother_education'] ?: null,
            ($data['mother_job'] === 'other') ? $data['mother_job_other'] : $data['mother_job'],
            $data['mother_phone'] ?: null
        ]);

        // ====== امکانات سخت‌افزاری ======
        $stmt = $pdo->prepare("INSERT INTO student_devices (
            student_id, has_pc, has_laptop, has_internet, has_none
        ) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $student_id,
            $data['has_pc'],
            $data['has_laptop'],
            $data['has_internet'],
            $data['has_none']
        ]);

        // ====== انتخاب رشته ======
        $stmt = $pdo->prepare("INSERT INTO field_choices (
            student_id, first_priority, second_priority, third_priority
        ) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $student_id,
            $data['first_priority'],
            $data['second_priority'] ?: null,
            $data['third_priority'] ?: null
        ]);

        // ====== سلامت ورزشی ======
        $health_fields = [
            'heart_disease', 'asthma', 'hypertension', 'thalassemia',
            'fracture_history', 'joint_disorders', 'diabetes', 'cancer',
            'vision_hearing_disorders', 'epilepsy', 'surgery_history',
            'coagulation_disorders', 'genetic_disorders', 'balance_disorders',
            'other_illness'
        ];
        
        $health_values = [];
        foreach ($health_fields as $field) {
            $health_values[$field] = in_array($field, $data['health_sports'] ?? []) ? 1 : 0;
        }

        $stmt = $pdo->prepare("INSERT INTO health_declarations (
            student_id, heart_disease, asthma, hypertension, thalassemia,
            fracture_history, joint_disorders, diabetes, cancer,
            vision_hearing_disorders, epilepsy, surgery_history,
            coagulation_disorders, genetic_disorders, balance_disorders,
            other_illness, other_illness_desc, medications, doctor_approval
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )");
        $stmt->execute([
            $student_id,
            $health_values['heart_disease'],
            $health_values['asthma'],
            $health_values['hypertension'],
            $health_values['thalassemia'],
            $health_values['fracture_history'],
            $health_values['joint_disorders'],
            $health_values['diabetes'],
            $health_values['cancer'],
            $health_values['vision_hearing_disorders'],
            $health_values['epilepsy'],
            $health_values['surgery_history'],
            $health_values['coagulation_disorders'],
            $health_values['genetic_disorders'],
            $health_values['balance_disorders'],
            $health_values['other_illness'],
            $data['other_illness_desc'] ?: null,
            $data['medications'] ?: null,
            'pending'
        ]);

        $pdo->commit();

        // ====== پاک کردن جلسه و هدایت به صفحه موفقیت ======
        unset($_SESSION['preview_data']);
        unset($_SESSION['verified_mobile']);
        unset($_SESSION['verified_at']);
        
        $_SESSION['registration_success'] = true;
        header('Location: success.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = '❌ خطا در ثبت اطلاعات: ' . $e->getMessage();
    }
}

// ====== بازگشت به فرم برای ویرایش ======
if (isset($_POST['back_to_form'])) {
    header('Location: form.php');
    exit;
}

// ====== تابع کمکی برای نمایش مقدار ======
function displayValue($value, $empty_text = '—') {
    return !empty($value) ? htmlspecialchars($value) : $empty_text;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأیید اطلاعات ثبت‌نام</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .preview-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .preview-section {
            background: var(--box-white);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            border-right: 5px solid var(--primary-blue);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .preview-section .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f4f8;
        }
        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 25px;
        }
        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f7fafc;
        }
        .preview-item .label {
            font-weight: 600;
            color: var(--text-gray);
            font-size: 14px;
        }
        .preview-item .value {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 14px;
            text-align: left;
            direction: ltr;
        }
        .preview-item .value.rtl {
            direction: rtl;
            text-align: right;
        }
        .preview-full {
            grid-column: 1 / -1;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-edit {
            padding: 14px 40px;
            background: #e2e8f0;
            color: var(--text-dark);
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-edit:hover {
            background: #cbd5e0;
        }
        .btn-confirm {
            padding: 14px 50px;
            background: var(--primary-orange);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-confirm:hover {
            background: #d9772b;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(237,137,54,0.35);
        }
        .error-box {
            background: #fff5f5;
            border: 2px solid #E53E3E;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            color: #E53E3E;
        }
        @media (max-width: 768px) {
            .preview-grid {
                grid-template-columns: 1fr;
            }
            .preview-section {
                padding: 18px 16px;
            }
            .btn-group {
                flex-direction: column;
                align-items: center;
            }
            .btn-edit, .btn-confirm {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container preview-container">
        <h1>📋 <span>تأیید</span> اطلاعات</h1>
        <p class="subtitle">لطفاً اطلاعات وارد شده را با دقت بررسی کنید و در صورت صحت، ثبت نهایی را تأیید کنید.</p>

        <?php if (isset($error)): ?>
            <div class="error-box">
                <strong>❌ خطا:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="preview.php">

            <!-- ====== اطلاعات فردی ====== -->
            <div class="preview-section">
                <div class="section-title">👤 اطلاعات فردی دانش‌آموز</div>
                <div class="preview-grid">
                    <div class="preview-item"><span class="label">نام</span><span class="value rtl"><?php echo displayValue($data['first_name'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">نام خانوادگی</span><span class="value rtl"><?php echo displayValue($data['last_name'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">کد ملی</span><span class="value"><?php echo displayValue($data['national_code'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">تاریخ تولد</span><span class="value rtl"><?php echo displayValue($data['birth_date'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">محل تولد</span><span class="value rtl"><?php echo displayValue($data['birth_place'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">ملیت</span><span class="value rtl"><?php echo displayValue($data['nationality'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">دین</span><span class="value rtl"><?php echo displayValue($data['religion'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">مذهب</span><span class="value rtl"><?php echo displayValue($data['denomination'] ?? ''); ?></span></div>
                </div>
            </div>

            <!-- ====== سلامت و ایثارگری ====== -->
            <div class="preview-section">
                <div class="section-title">🩺 سلامت و ایثارگری</div>
                <div class="preview-grid">
                    <div class="preview-item"><span class="label">وضعیت سالمتی</span><span class="value rtl"><?php echo ($data['health_status'] === 'sane') ? 'سالم' : 'معلول'; ?></span></div>
                    <?php if (!empty($data['disability_desc'])): ?>
                        <div class="preview-item preview-full"><span class="label">نوع معلولیت</span><span class="value rtl"><?php echo displayValue($data['disability_desc'] ?? ''); ?></span></div>
                    <?php endif; ?>
                    <div class="preview-item"><span class="label">وضعیت ایثارگری</span><span class="value rtl"><?php echo ($data['martyr_status'] === 'yes') ? 'بلی' : 'خیر'; ?></span></div>
                    <?php if (!empty($data['martyr_relation'])): ?>
                        <div class="preview-item preview-full"><span class="label">نسبت ایثارگر</span><span class="value rtl"><?php echo displayValue($data['martyr_relation'] ?? ''); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== آدرس و سکونت ====== -->
            <div class="preview-section">
                <div class="section-title">📍 آدرس و مکان سکونت</div>
                <div class="preview-grid">
                    <div class="preview-item preview-full"><span class="label">آدرس</span><span class="value rtl"><?php echo displayValue($data['address_text'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">کد پستی</span><span class="value"><?php echo displayValue($data['postal_code'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">تلفن ثابت</span><span class="value"><?php echo displayValue($data['phone_number'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">وسیله رفت‌وآمد</span><span class="value rtl"><?php 
                        $transport_map = [
                            'school_service' => 'سرویس مدرسه',
                            'personal_car' => 'خودروی شخصی',
                            'taxi' => 'تاکسی',
                            'bus' => 'اتوبوس',
                            'walk' => 'پیاده'
                        ];
                        echo displayValue($transport_map[$data['transportation']] ?? $data['transportation']);
                    ?></span></div>
                    <?php if (!empty($data['latitude']) && !empty($data['longitude'])): ?>
                        <div class="preview-item preview-full"><span class="label">مختصات</span><span class="value"><?php echo $data['latitude'] . ' , ' . $data['longitude']; ?></span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== والدین ====== -->
            <div class="preview-section">
                <div class="section-title">👨‍👩‍👦 والدین</div>
                
                <!-- پدر -->
                <div style="background: #f0f7ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 12px;">
                    <strong style="color: var(--primary-dark);">👨 پدر</strong>
                </div>
                <div class="preview-grid">
                    <div class="preview-item"><span class="label">نام</span><span class="value rtl"><?php echo displayValue($data['father_first_name'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">نام خانوادگی</span><span class="value rtl"><?php echo displayValue($data['father_last_name'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">کد ملی</span><span class="value"><?php echo displayValue($data['father_national_code'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">شماره شناسنامه</span><span class="value"><?php echo displayValue($data['father_id_card'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">تاریخ تولد</span><span class="value rtl"><?php echo displayValue($data['father_birth_date'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">تحصیلات</span><span class="value rtl"><?php echo displayValue($data['father_education'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">شغل</span><span class="value rtl"><?php 
                        $job_val = ($data['father_job'] === 'other') ? ($data['father_job_other'] ?? '') : ($data['father_job'] ?? '');
                        echo displayValue($job_val);
                    ?></span></div>
                    <div class="preview-item"><span class="label">شماره تماس</span><span class="value"><?php echo displayValue($data['father_phone'] ?? ''); ?></span></div>
                </div>

                <!-- مادر -->
                <div style="background: #fff5f0; border-radius: 8px; padding: 12px 16px; margin: 16px 0 12px;">
                    <strong style="color: var(--primary-dark);">👩 مادر</strong>
                </div>
                <div class="preview-grid">
                    <div class="preview-item"><span class="label">نام</span><span class="value rtl"><?php echo displayValue($data['mother_first_name'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">نام خانوادگی</span><span class="value rtl"><?php echo displayValue($data['mother_last_name'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">کد ملی</span><span class="value"><?php echo displayValue($data['mother_national_code'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">تاریخ تولد</span><span class="value rtl"><?php echo displayValue($data['mother_birth_date'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">تحصیلات</span><span class="value rtl"><?php echo displayValue($data['mother_education'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">شغل</span><span class="value rtl"><?php 
                        $job_val = ($data['mother_job'] === 'other') ? ($data['mother_job_other'] ?? '') : ($data['mother_job'] ?? '');
                        echo displayValue($job_val);
                    ?></span></div>
                    <div class="preview-item"><span class="label">شماره تماس</span><span class="value"><?php echo displayValue($data['mother_phone'] ?? ''); ?></span></div>
                </div>

                <!-- زندگی با چه کسی -->
                <div class="preview-grid" style="margin-top: 12px;">
                    <div class="preview-item preview-full"><span class="label">زندگی با</span><span class="value rtl"><?php 
                        $live_map = [
                            'parents' => 'پدر و مادر',
                            'father' => 'پدر',
                            'mother' => 'مادر',
                            'other' => 'سایر'
                        ];
                        $live_value = $live_map[$data['live_with']] ?? $data['live_with'];
                        if ($data['live_with'] === 'other' && !empty($data['live_with_other'])) {
                            $live_value .= ' (' . $data['live_with_other'] . ')';
                        }
                        if (($data['live_with'] === 'father' || $data['live_with'] === 'mother') && !empty($data['live_with_desc'])) {
                            $live_value .= ' - ' . $data['live_with_desc'];
                        }
                        echo displayValue($live_value);
                    ?></span></div>
                </div>
            </div>

            <!-- ====== اطلاعات تکمیلی ====== -->
            <div class="preview-section">
                <div class="section-title">📊 اطلاعات تکمیلی</div>
                <div class="preview-grid">
                    <div class="preview-item"><span class="label">تعداد فرزندان</span><span class="value"><?php echo displayValue($data['total_children'] ?? ''); ?></span></div>
                    <?php if (($data['total_children'] ?? 0) > 1): ?>
                        <div class="preview-item"><span class="label">فرزند چندم</span><span class="value"><?php echo displayValue($data['child_order'] ?? ''); ?></span></div>
                    <?php endif; ?>
                    <div class="preview-item"><span class="label">شاغل</span><span class="value rtl"><?php echo ($data['is_employed'] === 'yes') ? 'بله' : 'خیر'; ?></span></div>
                    <?php if (!empty($data['employment_desc'])): ?>
                        <div class="preview-item preview-full"><span class="label">مشخصات شغل</span><span class="value rtl"><?php echo displayValue($data['employment_desc'] ?? ''); ?></span></div>
                    <?php endif; ?>
                    <div class="preview-item"><span class="label">تماس اضطراری</span><span class="value"><?php echo displayValue($data['emergency_phone'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">شماره شاد</span><span class="value"><?php echo displayValue($data['shad_id'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">شماره بله</span><span class="value"><?php echo displayValue($data['bale_id'] ?? ''); ?></span></div>
                    <div class="preview-item"><span class="label">شماره ایتا</span><span class="value"><?php echo displayValue($data['eita_id'] ?? ''); ?></span></div>
                </div>
            </div>

            <!-- ====== امکانات ====== -->
            <div class="preview-section">
                <div class="section-title">💻 امکانات سخت‌افزاری</div>
                <div class="preview-grid">
                    <div class="preview-item"><span class="label">رایانه شخصی</span><span class="value"><?php echo ($data['has_pc']) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                    <div class="preview-item"><span class="label">لپ‌تاپ</span><span class="value"><?php echo ($data['has_laptop']) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                    <div class="preview-item"><span class="label">اینترنت پرسرعت</span><span class="value"><?php echo ($data['has_internet']) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                    <div class="preview-item"><span class="label">هیچ‌کدام</span><span class="value"><?php echo ($data['has_none']) ? '✅ دارد' : '❌ ندارد'; ?></span></div>
                </div>
            </div>

            <!-- ====== مدرسه و معدل ====== -->
            <div class="preview-section">
                <div class="section-title">📚 مدرسه قبلی و معدل</div>
                <div class="preview-grid">
                    <div class="preview-item preview-full"><span class="label">مدرسه راهنمایی</span><span class="value rtl"><?php echo displayValue($data['previous_school'] ?? ''); ?></span></div>
                    <div class="preview-item preview-full"><span class="label">معدل نهایی</span><span class="value"><?php echo displayValue($data['final_gpa'] ?? ''); ?></span></div>
                </div>
            </div>

            <!-- ====== سلامت ورزشی ====== -->
            <div class="preview-section">
                <div class="section-title">🏃 وضعیت سلامت ورزشی</div>
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
                    'other_illness' => 'سایر'
                ];
                ?>
                <div class="preview-grid">
                    <?php foreach ($health_items as $key => $label): ?>
                        <div class="preview-item">
                            <span class="label"><?php echo $label; ?></span>
                            <span class="value"><?php echo (in_array($key, $data['health_sports'] ?? [])) ? '⚠️ دارد' : '✔️ ندارد'; ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($data['other_illness_desc'])): ?>
                        <div class="preview-item preview-full"><span class="label">توضیحات سایر</span><span class="value rtl"><?php echo displayValue($data['other_illness_desc'] ?? ''); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($data['medications'])): ?>
                        <div class="preview-item preview-full"><span class="label">داروهای مصرفی</span><span class="value rtl"><?php echo displayValue($data['medications'] ?? ''); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== انتخاب رشته ====== -->
            <div class="preview-section">
                <div class="section-title">🎯 انتخاب رشته</div>
                <div class="preview-grid">
                    <div class="preview-item preview-full"><span class="label">اولویت اول</span><span class="value rtl"><?php 
                        $field_map = [
                            'computer' => 'کامپیوتر (شبکه و نرم‌افزار)',
                            'mechanics' => 'مکانیک (خودرو)',
                            'electronics' => 'الکترونیک'
                        ];
                        echo displayValue($field_map[$data['first_priority']] ?? $data['first_priority']);
                    ?></span></div>
                    <?php if (!empty($data['second_priority'])): ?>
                        <div class="preview-item preview-full"><span class="label">اولویت دوم</span><span class="value rtl"><?php echo displayValue($field_map[$data['second_priority']] ?? $data['second_priority']); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($data['third_priority'])): ?>
                        <div class="preview-item preview-full"><span class="label">اولویت سوم</span><span class="value rtl"><?php echo displayValue($field_map[$data['third_priority']] ?? $data['third_priority']); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== دکمه‌ها ====== -->
            <div class="btn-group">
                <button type="submit" name="back_to_form" class="btn-edit">✏️ بازگشت و ویرایش</button>
                <button type="submit" name="confirm_registration" class="btn-confirm">✅ تأیید و ثبت نهایی</button>
            </div>
            <p style="text-align: center; font-size: 13px; color: var(--text-gray); margin-top: 15px;">
                پس از تأیید، اطلاعات شما در سامانه ثبت خواهد شد و قابل ویرایش نخواهد بود.
            </p>

        </form>

        <!-- ====== فوتر ====== -->
        <div class="footer-art" style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e2e8f0; text-align: center;">
            <p style="font-size: 16px; font-weight: 700; color: var(--primary-dark);">
                🏫 هنرستان <span style="color: var(--primary-orange);">فرصت شیرازی</span>
            </p>
        </div>
    </div>
</body>
</html>