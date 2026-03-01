// Smart YT - MASTER SUBMIT ENGINE (Gemini V 4.7)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Forcing auto-submit...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        const editable = document.querySelector('div[contenteditable="true"], rich-textarea div, textarea');

        // قائمة محدثة بكافة أزرار الإرسال الممكنة
        const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"], .j-input-footer-send-button');

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(interval);

            editable.focus();
            // مسح وتعبئة النص بعناية
            editable.innerText = "";
            document.execCommand('insertText', false, data.svs_prompt);

            // إرسال تنبيهات مكثفة لكل حرف لضمان تفعيل الأزرار
            ['input', 'change', 'keyup', 'keydown'].forEach(name => {
                editable.dispatchEvent(new Event(name, { bubbles: true }));
            });

            // --- حلقة الإرسال القوية (تكرر المحاولة كل ثانية حتى يبدأ الإرسال) ---
            let submitAttempts = 0;
            const submitLoop = setInterval(() => {
                submitAttempts++;
                const currentBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"]');

                // 1. محاولة الضغط على الزر إذا كان مفعلاً
                if (currentBtn && !currentBtn.disabled) {
                    currentBtn.click();
                    console.log("🚀 Clicked Send Button.");
                }

                // 2. محاولة ضغط مفتاح Enter برمجياً (أكثر قوة)
                const enterEvent = new KeyboardEvent('keydown', {
                    key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true
                });
                editable.dispatchEvent(enterEvent);

                // 3. التحقق هل بدأ Gemini بالرد فعلياً؟ (البحث عن مؤشر الكتابة)
                const isStreaming = document.querySelector('.model-response-text, .message-content-wrapper');
                if (isStreaming || submitAttempts > 15) {
                    console.log("✅ Successfully submitted request!");
                    clearInterval(submitLoop);
                    waitForGeminiResponse(data.svs_source_tab);
                }
            }, 1000);
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
                    if (stableCount >= 4) {
                        clearInterval(checkInt);
                        // تنظيف النص النهائي من التذييلات (الرابط، المشاهدات، القناة)
                        let cleaned = latest;
                        cleaned = cleaned.replace(/رابط الفيديو:.*$/gis, '');
                        cleaned = cleaned.replace(/alkarbabadi\.net.*$/gis, '');
                        cleaned = cleaned.replace(/\d+\s*(views|مشاهدات).*$/gis, '');
                        cleaned = cleaned.replace(/الوحدة\s*الإسلامية.*$/gis, '');

                        chrome.runtime.sendMessage({ action: "done", text: cleaned.trim(), target: sourceTabId });
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }
})();
