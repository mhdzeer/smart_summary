// Smart YT - MASTER CLEANING ENGINE (Gemini V 4.8)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Processing prompt for V 4.8...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        const editable = document.querySelector('div[contenteditable="true"], rich-textarea div, textarea');
        const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"]');

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(interval);

            editable.focus();
            editable.innerText = "";
            document.execCommand('insertText', false, data.svs_prompt);

            ['input', 'change', 'keyup', 'keydown'].forEach(name => {
                editable.dispatchEvent(new Event(name, { bubbles: true }));
            });

            // حلقة الإرسال التلقائية
            let submitAttempts = 0;
            const submitLoop = setInterval(() => {
                submitAttempts++;
                const currentBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"]');
                if (currentBtn && !currentBtn.disabled) {
                    currentBtn.click();
                } else {
                    const enterEvent = new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true });
                    editable.dispatchEvent(enterEvent);
                }

                // التحقق من بدء التوليد
                if (document.querySelector('.model-response-text, .message-content-wrapper') || submitAttempts > 15) {
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
                    if (stableCount >= 4) { // استقرار كامل (12 ثانية للتأكد من الانتهاء)
                        clearInterval(checkInt);

                        // --- محرك تنظيف جراحي وذكي (V 4.8) ---
                        let finalContent = latest.trim();

                        // حذف التذييلات فقط إذا ظهرت في آخر 500 حرف
                        // هذا يحمي متن المقال من الحذف بالخطأ
                        const tailStart = Math.max(0, finalContent.length - 500);
                        const body = finalContent.substring(0, tailStart);
                        let tail = finalContent.substring(tailStart);

                        // ريغيكس لحذف الكلمات المزعجة من الذيل (فقط)
                        const badPatterns = [
                            /رابط الفيديو:.*$/gis,
                            /alkarbabadi\.net.*$/gis,
                            /\d+\s*(views|مشاهدة).*$/gis
                        ];

                        badPatterns.forEach(p => { tail = tail.replace(p, ''); });

                        // إعادة دمج المقال النظيف
                        const cleaned = (body + tail).trim();

                        chrome.runtime.sendMessage({ action: "done", text: cleaned, target: sourceTabId });
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }
})();
