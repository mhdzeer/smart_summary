// Smart YT Bridge - V 6.0 Dynamic Template Engine
console.log("⚡ Smart YT Bridge: ACTIVE (V 6.0)...");

window.addEventListener('SVS_CHECK_CONNECTION', () => { window.dispatchEvent(new CustomEvent('SVS_CONNECTION_OK')); });

window.addEventListener('SVS_START_AUTO', (e) => {
    const finalPrompt = e.detail.prompt; // استلام البرومبت المجهز بالكامل مع الرابط

    window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم تجهيز القالب... الروبوت في طريقه لـ Gemini.', done: false } }));
    chrome.runtime.sendMessage({ action: "open_gemini", prompt: finalPrompt });
});

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "paste_result") {
        const textarea = document.getElementById('svs_raw_result');
        if (textarea) {
            let text = request.text;

            // فلتر تنقية محتوى فائق
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
                    text = text.split(block).pop().trim();
                }
            });

            textarea.value = text;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            window.dispatchEvent(new CustomEvent('SVS_LOG', { detail: { msg: '✅ تم استلام المقال وتنظيفه وتجهيزه للتنسيق!', done: true } }));

            // تفعيل زر التوافق الفاخر تلقائياً بعد ثانية
            setTimeout(() => { document.getElementById('svs_btn_format_premium').click(); }, 1500);
        }
    }
});
