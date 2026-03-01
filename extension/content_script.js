// Smart YT - MASTER ONE-SHOT ENGINE (Gemini)
(async () => {
    // منع التشغيل المتكرر في نفس الصفحة
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);

    // إذا لم يوجد برومبت أو تم استخدامه مسبقاً، نخرج فوراً
    if (!data.svs_prompt || window.location.href.includes("?done=true")) {
        window.SVS_LOCK = false;
        return;
    }

    console.log("⚡ STEALTH START: Processing prompt...");
    // مسح البرومبت فوراً من الذاكرة لمنع أي تكرار مستقبلي
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        const editable = document.querySelector('div[contenteditable="true"], rich-textarea div, textarea');
        const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button');

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true; // وضع قفل الإرسال فوراً
            clearInterval(interval); // إيقاف البحث بمجرد إيجاد الخانة

            editable.focus();
            document.execCommand('insertText', false, data.svs_prompt);
            editable.dispatchEvent(new Event('input', { bubbles: true }));

            // انتظار بسيط لضمان تفعيل الأزرار ثم الضغط (مرة واحدة فقط)
            setTimeout(() => {
                if (sendBtn && !sendBtn.disabled) {
                    sendBtn.click();
                    console.log("🚀 STEALTH: Sent successfully.");
                    waitForGeminiResponse(data.svs_source_tab);
                } else {
                    // إذا فشل الزر، نعيد القفل للمحاولة يدوياً أو عبر إعادة المحاولة
                    window.SVS_SENT = false;
                }
            }, 1000);
        }

        if (attempts > 40) clearInterval(interval);
    }, 1500);

    function waitForGeminiResponse(sourceTabId) {
        let lastResult = "";
        let stableCount = 0;

        const checkInt = setInterval(() => {
            const responses = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            if (responses.length > 0) {
                let latest = responses[responses.length - 1].innerText;

                if (latest && latest.trim() === lastResult.trim() && latest.length > 100) {
                    stableCount++;
                    if (stableCount >= 3) { // استقرار كامل
                        clearInterval(checkInt);

                        // تنظيف النص النهائي من التكرارات
                        let cleaned = latest;
                        cleaned = cleaned.replace(/رابط الفيديو:.*?\n/gi, '');
                        cleaned = cleaned.replace(/(alkarbabadi.*?|views|مشاهدة|قناة).*?\n/gi, '');
                        cleaned = cleaned.replace(/الوحدة\s*الإسلامية/gi, '');

                        chrome.runtime.sendMessage({ action: "done", text: cleaned.trim(), target: sourceTabId });
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }
})();
