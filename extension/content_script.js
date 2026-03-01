// Smart YT - MASTER ONE-SHOT ENGINE (Gemini)
(async () => {
    // منع التشغيل المتكرر في نفس الصفحة
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);

    // إذا لم يوجد برومبت أو تم استخدامه مسبقاً، نخرج فوراً
    if (!data.svs_prompt) {
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

        // كاشفات زر الإرسال المتعددة (بما فيها الأيقونة بداخل الزر)
        const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, .send-button-container button, [data-test-id="send-button"]');

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(interval);

            editable.focus();

            // تنظيف الخانة أولاً
            editable.innerText = "";

            // إدراج النص عبر الحافظة الوهمية لضمان تفعيل الحساسات
            document.execCommand('insertText', false, data.svs_prompt);

            // إطلاق كافة الأحداث الممكنة لتنشيط واجهة جوجل
            const events = ['input', 'change', 'keyup', 'keydown', 'keypress'];
            events.forEach(name => {
                editable.dispatchEvent(new Event(name, { bubbles: true }));
            });

            // محاولة الضغط عدة مرات بفاصل زمني بسيط
            let clickAttempts = 0;
            const clicker = setInterval(() => {
                const activeBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"]');
                clickAttempts++;

                if (activeBtn && !activeBtn.disabled) {
                    activeBtn.click();
                    console.log("🚀 STEALTH: Sent successfully on attempt " + clickAttempts);
                    clearInterval(clicker);
                    waitForGeminiResponse(data.svs_source_tab);
                } else if (clickAttempts > 10) {
                    // إذا فشل الزر تماماً، جرب ضغط Enter برمجياً كحل أخير
                    const enterEvent = new KeyboardEvent('keydown', {
                        key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true
                    });
                    editable.dispatchEvent(enterEvent);
                    console.log("🚀 STEALTH: Fallback to Enter key.");
                    clearInterval(clicker);
                    waitForGeminiResponse(data.svs_source_tab);
                }
            }, 800);
        }

        if (attempts > 50) clearInterval(interval);
    }, 1500);

    function waitForGeminiResponse(sourceTabId) {
        let lastResult = "";
        let stableCount = 0;

        const checkInt = setInterval(() => {
            const responses = document.querySelectorAll('.model-response-text, .message-content-wrapper, [data-test-id="model-response-text"]');
            if (responses.length > 0) {
                let latest = responses[responses.length - 1].innerText;

                if (latest && latest.trim() === lastResult.trim() && latest.length > 200) {
                    stableCount++;
                    if (stableCount >= 4) { // استقرار كامل (12 ثانية) لضمان انتهاء التلخيص الطويل
                        clearInterval(checkInt);

                        // تنظيف النص النهائي
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
