(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Processing prompt...");
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

            ['input', 'change', 'keyup'].forEach(name => {
                editable.dispatchEvent(new Event(name, { bubbles: true }));
            });

            // محاولة الضغط التلقائي أو Enter كحل احتياطي
            setTimeout(() => {
                if (sendBtn && !sendBtn.disabled) {
                    sendBtn.click();
                    waitForGeminiResponse(data.svs_source_tab);
                } else {
                    const enter = new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true });
                    editable.dispatchEvent(enter);
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

                        // --- تنظيف المحتوى بذكاء (إزالة الروابط، الأسماء، المشاهدات) ---
                        let cleaned = latest;
                        // حذف أي شيء يبدأ بـ رابط الفيديو أو alkarbabadi وحتى نهاية التلخيص
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
