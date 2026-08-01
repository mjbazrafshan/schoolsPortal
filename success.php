<?php
session_start();

// ====== بررسی وجود نشست موفقیت ======
if (!isset($_SESSION['registration_success'])) {
    header('Location: index.php');
    exit;
}

// ====== حذف نشست برای جلوگیری از نمایش مجدد ======
unset($_SESSION['registration_success']);

// ====== تولید شماره پیگیری ساده ======
$tracking_code = date('Ymd') . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام موفق</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 30px 20px;
        }
        .success-icon {
            font-size: 80px;
            color: #38A169;
            display: block;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .success-box {
            background: var(--box-white);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border-right: 6px solid #38A169;
        }
        .success-box h1 {
            font-size: 28px;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        .success-box h1 span {
            color: #38A169;
        }
        .success-box .message {
            font-size: 18px;
            color: var(--text-dark);
            margin: 15px 0;
            line-height: 1.8;
        }
        .tracking-code {
            background: #f7fafc;
            padding: 15px 20px;
            border-radius: 12px;
            display: inline-block;
            margin: 15px 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-dark);
            border: 2px dashed var(--primary-blue);
        }
        .tracking-code span {
            color: var(--primary-orange);
        }
        .btn-home {
            display: inline-block;
            padding: 14px 45px;
            background: var(--primary-blue);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-home:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(43,108,176,0.35);
        }
        .btn-print {
            display: inline-block;
            padding: 14px 35px;
            background: #e2e8f0;
            color: var(--text-dark);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-print:hover {
            background: #cbd5e0;
        }
        .footer-art {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        @media (max-width: 480px) {
            .success-box {
                padding: 25px 15px;
            }
            .tracking-code {
                font-size: 16px;
                padding: 12px 16px;
            }
            .btn-home, .btn-print {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container success-container">
        <div class="success-box">
            <span class="success-icon">✅</span>
            <h1>🎉 ثبت‌نام با <span>موفقیت</span> انجام شد!</h1>
            <p class="message">
                اطلاعات شما با موفقیت در سامانه ثبت گردید.<br>
                به زودی با شما تماس گرفته خواهد شد.
            </p>

            <div class="tracking-code">
                📋 شماره پیگیری: <span><?php echo $tracking_code; ?></span>
            </div>

            <p style="font-size: 14px; color: var(--text-gray); margin: 10px 0;">
                لطفاً این شماره را برای پیگیری ثبت‌نام خود نگهداری کنید.
            </p>

            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="index.php" class="btn-home">🏠 بازگشت به صفحه اصلی</a>
                <a href="javascript:window.print()" class="btn-print">🖨️ چاپ این صفحه</a>
            </div>
        </div>

        <!-- ====== فوتر ====== -->
        <div class="footer-art">
            <p style="font-size: 16px; font-weight: 700; color: var(--primary-dark);">
                🏫 هنرستان <span style="color: var(--primary-orange);">فرصت شیرازی</span>
            </p>
            <p style="font-size: 13px; color: var(--text-gray); margin-top: 5px;">
                ثبت‌نام پایه دهم - سال تحصیلی ۱۴۰۶-۱۴۰۵
            </p>
        </div>
    </div>
</body>
</html>