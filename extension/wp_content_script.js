// Smart YT Bridge - V 4.9 Premium Formatting Prompt
console.log("⚡ Smart YT Bridge: ACTIVE (V 4.9 Premium)...");

window.addEventListener('SVS_CHECK_CONNECTION', () => { window.dispatchEvent(new CustomEvent('SVS_CONNECTION_OK')); });

window.addEventListener('SVS_START_AUTO', (e) => {
    const videoUrl = e.detail.url;

    // --- البرومبت الفاخر والحصري للطباعة والنشر ---
    const prompt = "أنت كاتب مقالات ديني واجتماعي محترف. قم بكتابة مقال تلخيصي عميق وبليغ باللغة العربية الفصحى لهذا الفيديو:\n" + videoUrl + "\n\n" +
        "الهيكل المطلوبة (مهم جداً):\n" +
        "1. ابدأ بمقدمة روحانية وبليغة جداً.\n" +
        "2. استخدم عناوين h3 جذابة للنقاط الرئيسية.\n" +
        "3. يجب أن تكون التفاصيل على شكل قائمة مرقمة (1. 2. 3. 4.) لتسهيل القراءة والطباعة.\n" +
        "4. ممنوع ذكر اسم الشيخ أو القناة أو كلمة 'إليك' أو 'رابط'.\n" +
        "5. اجعل النص غنياً بالمعاني والمصطلحات البليغة ليكون مناسباً للنشر كـ مقال صحفي فاخر.";

    window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم توجيه الروبوت لكتابة مقال فاخر... Gemini يعمل صامتاً الآن.', done: false } }));
    chrome.runtime.sendMessage({ action: "open_gemini", prompt: prompt });
});

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "paste_result") {
        const textarea = document.getElementById('svs_raw_result');
        if (textarea) {
            textarea.value = request.text;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم استلام المقال الفاخر! جاري التنسيق النهائي...', done: true } }));

            // الضغط التلقائي على الزر الأخضر الجديد
            setTimeout(() => { document.getElementById('svs_btn_format_premium').click(); }, 1000);
        }
    }
});
