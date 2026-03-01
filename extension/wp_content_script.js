// WordPress-to-Extension Bridge Engine (V 4.4)
console.log("⚡ Smart YT Bridge: ACTIVE and listening for signals...");

// 1. كاشف الاتصال للتأكيد لووردبريس
window.addEventListener('SVS_CHECK_CONNECTION', () => {
    window.dispatchEvent(new CustomEvent('SVS_CONNECTION_OK'));
});

// 2. استقبال إشارة البدء
window.addEventListener('SVS_START_AUTO', (e) => {
    const videoUrl = e.detail.url;
    console.log("🚀 Bridge: Received start signal for: ", videoUrl);

    // إرسال تحديث لووردبريس
    window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم استلام الرابط من الإضافة! جاري فتح Gemini...', done: false } }));

    const prompt = "قم بكتابة مقال تلخيصي احترافي وعميق باللغة العربية الفصحى لـ فيديو يوتيوب من الرابط هذا: " + videoUrl + "\n\nالقواعد:\n1. ابدأ مباشرة بمقدمة بليغة.\n2. ممنوع ذكر اسم الشيخ أو القناة.\n3. استخدم h3 و ul للنقاط.\n4. ممنوع مخاطبتي بـ 'إليك'.";

    // طلب فتح التبويب الجديد من الباك-غراوند وحفظ الرابط بذاكرة الإضافة
    chrome.runtime.sendMessage({ action: "open_gemini", prompt: prompt });
});

// 3. استقبال النتائج النهائية من Gemini
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "paste_result") {
        console.log("⚡ Bridge: Final result arrived from background!");

        const resultText = request.text;
        const textarea = document.getElementById('svs_raw_result');

        if (textarea) {
            textarea.value = resultText;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));

            // إبلاغ ووردربريس بالنجاح
            window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم جلب التلخيص ونقله لووردبريس بنجاح!', done: true } }));

            // الضغط على زر التنسيق والنشر تلقائياً
            const formatBtn = document.getElementById('svs_btn_format_final');
            if (formatBtn) formatBtn.click();
        } else {
            console.error("❌ Could not find the result textarea.");
            alert("✅ تم جلب التلخيص! فضلاً الصقه يدوياً في الخانة.");
        }
    }
});
