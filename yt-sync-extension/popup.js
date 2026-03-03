// popup.js - منطق إضافة كروم المزامنة

const STATUS = document.getElementById('status');
const VIDEO_INFO = document.getElementById('video_info');
const VIDEO_COUNT = document.getElementById('video_count');

// تحميل الإعدادات المحفوظة عند فتح الـ Popup
document.addEventListener('DOMContentLoaded', function () {
    chrome.storage.local.get(['wp_url', 'wp_token', 'cat_id'], function (data) {
        if (data.wp_url) document.getElementById('wp_url').value = data.wp_url;
        if (data.wp_token) document.getElementById('wp_token').value = data.wp_token;
        if (data.cat_id) document.getElementById('cat_id').value = data.cat_id;
    });

    // فحص الصفحة الحالية لمعرفة عدد الفيديوهات
    chrome.tabs.query({ active: true, currentWindow: true }, function (tabs) {
        if (tabs[0] && tabs[0].url && tabs[0].url.includes('youtube.com')) {
            chrome.tabs.sendMessage(tabs[0].id, { action: 'getVideos' }, function (response) {
                if (response && response.count > 0) {
                    VIDEO_INFO.style.display = 'block';
                    VIDEO_COUNT.textContent = response.count;
                }
            });
        }
    });
});

// حفظ الإعدادات
document.getElementById('btn_save').addEventListener('click', function () {
    const wpUrl = document.getElementById('wp_url').value.trim().replace(/\/$/, '');
    const token = document.getElementById('wp_token').value.trim();
    const catId = document.getElementById('cat_id').value.trim();

    if (!wpUrl) return showStatus('يرجى إدخال رابط الموقع أولاً', 'err');

    chrome.storage.local.set({ wp_url: wpUrl, wp_token: token, cat_id: catId }, function () {
        showStatus('✅ تم حفظ الإعدادات بنجاح!', 'ok');
    });
});

// الاستيراد من يوتيوب
document.getElementById('btn_scan').addEventListener('click', function () {
    const wpUrl = document.getElementById('wp_url').value.trim().replace(/\/$/, '');
    const token = document.getElementById('wp_token').value.trim();
    const catId = document.getElementById('cat_id').value.trim();

    if (!wpUrl) return showStatus('❌ يرجى إدخال رابط الموقع أولاً', 'err');
    if (!catId) return showStatus('❌ يرجى إدخال رقم ID التصنيف', 'err');
    if (!token) return showStatus('❌ يرجى إدخال رمز الأمان', 'err');

    showStatus('⏳ جاري جمع الفيديوهات من الصفحة...', 'info');

    chrome.tabs.query({ active: true, currentWindow: true }, function (tabs) {
        if (!tabs[0] || !tabs[0].url || !tabs[0].url.includes('youtube.com')) {
            return showStatus('❌ يرجى فتح صفحة فيديوهات القناة في يوتيوب أولاً', 'err');
        }

        chrome.tabs.sendMessage(tabs[0].id, { action: 'getVideos' }, function (response) {
            if (chrome.runtime.lastError || !response) {
                return showStatus('❌ تعذر قراءة الفيديوهات. تأكد من وجودها في الصفحة', 'err');
            }

            if (response.count === 0) {
                return showStatus('⚠️ لم يتم العثور على فيديوهات في هذه الصفحة', 'err');
            }

            showStatus(`⏳ تم العثور على ${response.count} فيديو، جاري الإرسال...`, 'info');

            // الإرسال إلى وردبريس عبر AJAX (بدون قيود CORS من الإضافة)
            const ajaxUrl = wpUrl + '/wp-admin/admin-ajax.php';
            const formData = new FormData();
            formData.append('action', 'sam_process_browser_data');
            formData.append('cat_id', catId);
            formData.append('sam_token', token);
            formData.append('videos', JSON.stringify(response.videos));

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showStatus(`✅ تم الاستيراد بنجاح! اذهب لوردبريس واضغط "فحص المقالات"`, 'ok');
                    } else {
                        showStatus('❌ فشل الاستيراد: ' + (data.data || 'خطأ غير معروف'), 'err');
                    }
                })
                .catch(err => {
                    showStatus('❌ خطأ في الاتصال بالموقع: ' + err.message, 'err');
                });
        });
    });
});

function showStatus(msg, type) {
    STATUS.style.display = 'block';
    STATUS.className = 'status-' + (type === 'ok' ? 'ok' : type === 'err' ? 'err' : 'info');
    STATUS.textContent = msg;
}
