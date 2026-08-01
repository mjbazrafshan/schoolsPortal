document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('test-btn');
    const nextBtn = document.getElementById('next-btn');
    const resultDiv = document.getElementById('test-result');

    if (testBtn) {
        testBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const host = document.getElementById('db_host').value;
            const db = document.getElementById('db_name').value;
            const user = document.getElementById('db_user').value;
            const pass = document.getElementById('db_pass').value;

            if (!host || !db || !user) {
                resultDiv.className = 'error';
                resultDiv.style.display = 'block';
                resultDiv.textContent = 'لطفاً تمام فیلدهای هاست، نام دیتابیس و نام کاربری را پر کنید.';
                return;
            }

            // غیرفعال کردن دکمه تست
            testBtn.disabled = true;
            testBtn.textContent = 'در حال بررسی...';
            resultDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('host', host);
            formData.append('db', db);
            formData.append('user', user);
            formData.append('pass', pass);

            fetch('setup.php?ajax=test_db', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.style.display = 'block';
                if (data.status === 'success') {
                    resultDiv.className = 'success';
                    resultDiv.textContent = '✅ ' + data.message;
                    if (nextBtn) nextBtn.disabled = false;
                } else {
                    resultDiv.className = 'error';
                    resultDiv.textContent = '❌ ' + data.message;
                    if (nextBtn) nextBtn.disabled = true;
                }
            })
            .catch(error => {
                resultDiv.style.display = 'block';
                resultDiv.className = 'error';
                resultDiv.textContent = '❌ خطا در ارتباط با سرور: ' + error;
                if (nextBtn) nextBtn.disabled = true;
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.textContent = 'بررسی اتصال';
            });
        });
    }
});