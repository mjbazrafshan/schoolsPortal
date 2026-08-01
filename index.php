<?php
session_start();

// ====== پردازش درخواست‌های AJAX ======
if (isset($_POST['action']) && ($_POST['action'] == 'send_otp' || $_POST['action'] == 'verify_otp')) {
    header('Content-Type: application/json; charset=utf-8');
    error_reporting(0);
    ini_set('display_errors', 0);
    
    function jsonResponse($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        if (!file_exists('includes/functions.php')) {
            throw new Exception('❌ فایل includes/functions.php یافت نشد.');
        }
        require_once 'includes/functions.php';
        
        if (!function_exists('sendOtpCode')) {
            throw new Exception('❌ تابع sendOtpCode تعریف نشده است.');
        }
        if (!function_exists('verifyOtpCode')) {
            throw new Exception('❌ تابع verifyOtpCode تعریف نشده است.');
        }
        
        if ($_POST['action'] == 'send_otp') {
            $mobile = $_POST['mobile'] ?? '';
            $result = sendOtpCode($mobile);
            jsonResponse($result);
        } elseif ($_POST['action'] == 'verify_otp') {
            $mobile = $_POST['mobile'] ?? '';
            $code = $_POST['code'] ?? '';
            $result = verifyOtpCode($mobile, $code);
            jsonResponse($result);
        }
    } catch (Throwable $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// ====== بررسی احراز هویت ======
$auth = false;
try {
    if (file_exists('includes/functions.php')) {
        require_once 'includes/functions.php';
        if (function_exists('isAuthenticated')) {
            $auth = isAuthenticated();
        }
    }
} catch (Throwable $e) {
    // خطا را نادیده بگیر
}
if ($auth) {
    header('Location: form.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه ثبت‌نام هنرستان</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .otp-box {
            max-width: 450px;
            margin: 0 auto;
            text-align: center;
        }
        .otp-box .form-group {
            text-align: right;
        }
        #step-2 {
            display: none;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px dashed #e2e8f0;
        }
        #step-2.show {
            display: block;
        }
        #timer {
            color: var(--primary-orange);
            font-weight: 700;
            font-size: 18px;
        }
        .resend-link {
            color: var(--primary-blue);
            cursor: pointer;
            font-weight: 600;
            text-decoration: underline;
        }
        .resend-link.disabled {
            color: #a0aec0;
            cursor: not-allowed;
            text-decoration: none;
        }
        #status-message, #status-message-2 {
            margin-top: 15px;
            padding: 12px;
            border-radius: 10px;
            display: none;
        }
        #status-message.success, #status-message-2.success {
            display: block;
            background: #f0fff4;
            border: 1px solid #38A169;
            color: #38A169;
        }
        #status-message.error, #status-message-2.error {
            display: block;
            background: #fff5f5;
            border: 1px solid #E53E3E;
            color: #E53E3E;
        }
        .otp-input {
            text-align: center;
            font-size: 28px;
            letter-spacing: 12px;
            font-weight: 700;
            direction: ltr;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
            padding: 15px 10px;
        }
        .footer-art {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        .otp-input-wrapper {
            display: flex;
            justify-content: center;
        }
        .debug-info {
            background: #f7fafc;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            color: #718096;
            margin-top: 10px;
            text-align: left;
            direction: ltr;
            display: none;
        }
        #send-btn-wrapper {
            transition: all 0.3s ease;
        }
        #send-btn-wrapper.hidden {
            display: none;
        }
        .btn-verified {
            opacity: 0.6;
            cursor: not-allowed;
            background: #38A169 !important;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 500px;">
        <h1>📱 <span>ورود</span> به سامانه</h1>
        <p class="subtitle">برای ثبت‌نام، ابتدا شماره موبایل خود را تأیید کنید.</p>

        <div class="otp-box">
            <!-- ====== مرحله ۱: دریافت شماره موبایل ====== -->
            <div id="step-1">
                <div class="form-group">
                    <label>شماره موبایل</label>
                    <input type="tel" id="mobile" placeholder="مثلاً 09123456789" maxlength="11" required>
                    <small style="color: #718096; font-size: 13px;">شماره را با ۰۹ شروع کنید.</small>
                </div>

                <div id="send-btn-wrapper">
                    <button id="send-btn" class="btn btn-success btn-block">📤 ارسال کد تأیید</button>
                </div>

                <div id="status-message"></div>
            </div>

            <!-- ====== مرحله ۲: وارد کردن کد ====== -->
            <div id="step-2">
                <p style="font-weight: 600; margin-bottom: 10px;">کد ۴ رقمی ارسال شده را وارد کنید:</p>
                
                <div class="form-group otp-input-wrapper">
                    <input type="text" id="otp-code" class="otp-input" placeholder="1234" maxlength="4" required>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 14px; color: #718096;">
                        ⏱️ زمان باقی‌مانده: <span id="timer">02:00</span>
                    </span>
                    <span class="resend-link disabled" id="resend-link">ارسال مجدد کد (غیرفعال)</span>
                </div>

                <button id="verify-btn" class="btn btn-primary btn-block">✅ تأیید کد</button>

                <div id="status-message-2"></div>
                
                <!-- ====== باکس دیباگ برای مشاهده پاسخ سرور ====== -->
                <div class="debug-info" id="debug-info"></div>
            </div>
        </div>

        <!-- ====== فوتر ====== -->
        <div class="footer-art">
            <p style="font-size: 14px; color: var(--text-gray);">
                🔒 اطلاعات شما محفوظ است و صرفاً برای ثبت‌نام استفاده می‌شود.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ صفحه بارگذاری شد');

            const mobileInput = document.getElementById('mobile');
            const sendBtnWrapper = document.getElementById('send-btn-wrapper');
            const sendBtn = document.getElementById('send-btn');
            const statusMsg = document.getElementById('status-message');
            const step2 = document.getElementById('step-2');
            const otpInput = document.getElementById('otp-code');
            const verifyBtn = document.getElementById('verify-btn');
            const statusMsg2 = document.getElementById('status-message-2');
            const timerSpan = document.getElementById('timer');
            const resendLink = document.getElementById('resend-link');
            const debugInfo = document.getElementById('debug-info');
            
            let timerInterval = null;
            let remainingSeconds = 0;
            let currentMobile = '';
            let isResendEnabled = false;

            // ====== تابع نمایش بخش کد ======
            function showCodeSection() {
                step2.style.display = 'block';
                step2.classList.add('show');
                console.log('✅ بخش ورود کد نمایش داده شد');
            }

            function showMessage(element, type, text) {
                element.className = type;
                element.textContent = text;
                element.style.display = 'block';
                if (type !== 'error') {
                    setTimeout(() => {
                        element.style.display = 'none';
                    }, 5000);
                }
            }

            function startTimer(seconds) {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                
                remainingSeconds = seconds;
                updateTimerDisplay();
                console.log('⏱️ تایمر شروع شد با ' + seconds + ' ثانیه');
                
                // غیرفعال کردن لینک ارسال مجدد
                resendLink.classList.add('disabled');
                resendLink.textContent = 'ارسال مجدد کد (غیرفعال)';
                isResendEnabled = false;
                
                timerInterval = setInterval(function() {
                    remainingSeconds--;
                    if (remainingSeconds <= 0) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                        timerSpan.textContent = '00:00';
                        verifyBtn.disabled = true;
                        verifyBtn.style.opacity = '0.5';
                        showMessage(statusMsg2, 'error', '⏰ زمان کد منقضی شد. لطفاً دوباره درخواست کنید.');
                        console.log('⏱️ تایمر به پایان رسید');
                        
                        // فعال کردن لینک ارسال مجدد
                        resendLink.classList.remove('disabled');
                        resendLink.textContent = 'ارسال مجدد کد';
                        isResendEnabled = true;
                    } else {
                        updateTimerDisplay();
                    }
                }, 1000);
            }

            function updateTimerDisplay() {
                const mins = Math.floor(remainingSeconds / 60);
                const secs = remainingSeconds % 60;
                timerSpan.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }

            // ====== تابع بررسی موفقیت بر اساس پاسخ ======
            function isResponseSuccess(data) {
                if (data.status === 'success' || data.Status === 'success' || data.success === true || data.result === 'success') {
                    return true;
                }
                const msg = data.message || data.Message || data.error || '';
                const keywords = ['موفقیت', 'ارسال شد', 'تأیید', 'تایید', 'درست', 'صحیح'];
                for (let keyword of keywords) {
                    if (msg.includes(keyword)) {
                        return true;
                    }
                }
                return false;
            }

            // ====== ارسال کد ======
            sendBtn.addEventListener('click', function() {
                const mobile = mobileInput.value.trim();
                if (!mobile || mobile.length < 11) {
                    showMessage(statusMsg, 'error', '❌ لطفاً شماره موبایل معتبر ۱۱ رقمی وارد کنید.');
                    return;
                }

                // ====== 1. ناپدید کردن دکمه ارسال کد ======
                sendBtnWrapper.classList.add('hidden');
                
                // ====== 2. غیرفعال کردن فیلد شماره موبایل ======
                mobileInput.disabled = true;
                mobileInput.style.opacity = '0.6';

                // ====== 3. نمایش بخش ورود کد ======
                showCodeSection();
                
                sendBtn.disabled = true;
                sendBtn.textContent = '⏳ در حال ارسال...';
                statusMsg.style.display = 'none';
                debugInfo.style.display = 'none';

                const formData = new FormData();
                formData.append('action', 'send_otp');
                formData.append('mobile', mobile);

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('📩 پاسخ ارسال کد (خام):', data);
                    
                    debugInfo.textContent = 'پاسخ سرور: ' + JSON.stringify(data, null, 2);
                    debugInfo.style.display = 'block';
                    
                    const isSuccess = isResponseSuccess(data);
                    console.log('📊 نتیجه بررسی موفقیت:', isSuccess);
                    
                    if (isSuccess) {
                        currentMobile = mobile;
                        showMessage(statusMsg, 'success', '✅ ' + (data.message || 'کد با موفقیت ارسال شد'));
                        otpInput.value = '';
                        otpInput.focus();
                        startTimer(120);
                        verifyBtn.disabled = false;
                        verifyBtn.style.opacity = '1';
                        statusMsg2.style.display = 'none';
                    } else {
                        const errorMsg = data.message || data.Message || data.error || 'خطای ناشناخته';
                        showMessage(statusMsg, 'error', '❌ ' + errorMsg);
                        // در صورت خطا، دکمه را دوباره نمایش بده
                        sendBtnWrapper.classList.remove('hidden');
                        mobileInput.disabled = false;
                        mobileInput.style.opacity = '1';
                        step2.style.display = 'none';
                        step2.classList.remove('show');
                    }
                })
                .catch(error => {
                    console.error('❌ خطا:', error);
                    showMessage(statusMsg, 'error', '❌ خطا در ارتباط با سرور: ' + error.message);
                    // در صورت خطا، دکمه را دوباره نمایش بده
                    sendBtnWrapper.classList.remove('hidden');
                    mobileInput.disabled = false;
                    mobileInput.style.opacity = '1';
                    step2.style.display = 'none';
                    step2.classList.remove('show');
                })
                .finally(() => {
                    sendBtn.disabled = false;
                    sendBtn.textContent = '📤 ارسال کد تأیید';
                });
            });

            // ====== تأیید کد ======
            verifyBtn.addEventListener('click', function() {
                const code = otpInput.value.trim();
                if (!code || code.length < 4) {
                    showMessage(statusMsg2, 'error', '❌ کد ۴ رقمی را به‌درستی وارد کنید.');
                    return;
                }

                verifyBtn.disabled = true;
                verifyBtn.textContent = '⏳ در حال بررسی...';
                statusMsg2.style.display = 'none';

                const formData = new FormData();
                formData.append('action', 'verify_otp');
                formData.append('mobile', currentMobile);
                formData.append('code', code);

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('✅ پاسخ تأیید کد (خام):', data);
                    
                    debugInfo.textContent = 'پاسخ تأیید: ' + JSON.stringify(data, null, 2);
                    debugInfo.style.display = 'block';
                    
                    const isSuccess = isResponseSuccess(data);
                    
                    if (isSuccess) {
                        showMessage(statusMsg2, 'success', '✅ ' + (data.message || 'کد تأیید شد') + ' در حال انتقال...');
                        if (timerInterval) clearInterval(timerInterval);
                        
                        // ====== غیرفعال کردن دکمه تأیید ======
                        verifyBtn.disabled = true;
                        verifyBtn.textContent = '✅ تأیید شده';
                        verifyBtn.style.background = '#38A169';
                        
                        // ====== غیرفعال کردن لینک ارسال مجدد ======
                        resendLink.classList.add('disabled');
                        resendLink.textContent = 'تأیید شده';
                        
                        setTimeout(() => {
                            window.location.href = 'form.php';
                        }, 1000);
                    } else {
                        const errorMsg = data.message || data.Message || data.error || 'کد نامعتبر است';
                        showMessage(statusMsg2, 'error', '❌ ' + errorMsg);
                        verifyBtn.disabled = false;
                        verifyBtn.textContent = '✅ تأیید کد';
                    }
                })
                .catch(error => {
                    console.error('❌ خطا:', error);
                    showMessage(statusMsg2, 'error', '❌ خطا در ارتباط با سرور: ' + error.message);
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = '✅ تأیید کد';
                });
            });

            // ====== ارسال مجدد کد (با کلیک روی لینک) ======
            resendLink.addEventListener('click', function() {
                if (!isResendEnabled) {
                    showMessage(statusMsg2, 'error', '⏳ لطفاً صبر کنید تا زمان انقضای کد تمام شود.');
                    return;
                }
                
                const mobile = currentMobile;
                if (!mobile) {
                    showMessage(statusMsg2, 'error', '❌ شماره موبایل نامعتبر است.');
                    return;
                }

                resendLink.classList.add('disabled');
                resendLink.textContent = '⏳ در حال ارسال...';
                
                const formData = new FormData();
                formData.append('action', 'send_otp');
                formData.append('mobile', mobile);

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('📩 پاسخ ارسال مجدد کد:', data);
                    
                    const isSuccess = isResponseSuccess(data);
                    
                    if (isSuccess) {
                        showMessage(statusMsg2, 'success', '✅ ' + (data.message || 'کد جدید با موفقیت ارسال شد'));
                        otpInput.value = '';
                        otpInput.focus();
                        startTimer(120);
                        verifyBtn.disabled = false;
                        verifyBtn.style.opacity = '1';
                    } else {
                        const errorMsg = data.message || data.Message || data.error || 'خطا در ارسال مجدد';
                        showMessage(statusMsg2, 'error', '❌ ' + errorMsg);
                        resendLink.classList.remove('disabled');
                        resendLink.textContent = 'ارسال مجدد کد';
                        isResendEnabled = true;
                    }
                })
                .catch(error => {
                    console.error('❌ خطا:', error);
                    showMessage(statusMsg2, 'error', '❌ خطا در ارتباط با سرور: ' + error.message);
                    resendLink.classList.remove('disabled');
                    resendLink.textContent = 'ارسال مجدد کد';
                    isResendEnabled = true;
                });
            });

            // ====== کلید Enter ======
            mobileInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') sendBtn.click();
            });
            otpInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') verifyBtn.click();
            });

            // ====== اعتبارسنجی لحظه‌ای ======
            mobileInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 11) this.value = this.value.slice(0, 11);
            });
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 4) this.value = this.value.slice(0, 4);
            });
        });
    </script>
</body>
</html>