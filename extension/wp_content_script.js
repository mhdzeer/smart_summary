// Smart YT Bridge - V 5.0 Pure Content Engine
console.log("⚡ Smart YT Bridge: ACTIVE (V 5.0)...");

window.addEventListener('SVS_CHECK_CONNECTION', () => { window.dispatchEvent(new CustomEvent('SVS_CONNECTION_OK')); });

window.addEventListener('SVS_START_AUTO', (e) => {
    const videoUrl = e.detail.url;

    // --- البرومبت الجديد المحكم لـ Gemini ---
    const prompt = "أنت كاتب مقالات ديني واجتماعي محترف. قم بكتابة مقال تلخيصي عميق وبليغ باللغة العربية الفصحى لهذا الفيديو:\n" + videoUrl + "\n\n" +
        "تعليمات صارمة (ممنوع كتابة هذه التعليمات في ردك):\n" +
        "1. ابدأ مباشرة بالمقال بمقدمة روحانية وبليغة جداً.\n" +
        "2. استخدم عناوين h3 جذابة للنقاط الرئيسية.\n" +
        "3. اجعل التفاصيل على شكل قائمة مرقمة (1. 2. 3. 4.) لتسهيل القراءة والطباعة.\n" +
        "4. ممنوع ذكر اسم الشيخ أو القناة أو كلمة 'إليك' أو 'رابط'.\n" +
        "5. اجعل النص غنياً بالمعاني ليكون مناسباً للنشر كـ مقال صحفي فاخر.\n" +
        "تنبيه: لا تكرر سؤالي ولا تكتب الهيكل المطلوب، ابدأ بالمقال فوراً بـ 'بسم الله' أو بمقدمة بليغة.";

    window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم توجيه الروبوت... انتظر حتى ينهي Gemini المقال بالكامل.', done: false } }));
    chrome.runtime.sendMessage({ action: "open_gemini", prompt: prompt });
});

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "paste_result") {
        const textarea = document.getElementById('svs_raw_result');
        if (textarea) {
            let text = request.text;

            // 🛑 فلتر تنظيف جذري إضافي لأي تعليمات سربها Gemini
            const badBlocks = [
                'الهيكل المطلوبة (مهم جداً):',
                'ابدأ بمقدمة روحانية وبليغة جداً',
                'استخدم عناوين h3 جذابة',
                'يجب أن تكون التفاصيل على شكل قائمة مرقمة',
                'ممنوع ذكر اسم الشيخ',
                'اجعل النص غنياً بالمعاني'
            ];

            badBlocks.forEach(block => {
                if (text.includes(block)) {
                    // إذا وجدنا التعليمات، نقوم بقص كل ما قبلها وما يليها حتى نصل لصلب المقال
                    text = text.split(block).pop().trim();
                }
            });

            textarea.value = text;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم استلام المقال وتنظيفه تماماً من أي تعليمات!', done: true } }));

            setTimeout(() => { document.getElementById('svs_btn_format_premium').click(); }, 1500);
        }
    }
});
