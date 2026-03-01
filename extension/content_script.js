// Smart YT - PURE EXTRACTION ENGINE (Gemini V 5.0)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Pure Extraction (V 5.0)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        const editable = document.querySelector('div[contenteditable="true"], rich-textarea div, textarea');
        const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button');

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(interval);

            editable.focus();
            editable.innerText = "";
            document.execCommand('insertText', false, data.svs_prompt);
            ['input', 'change', 'keyup', 'keydown'].forEach(name => {
                editable.dispatchEvent(new Event(name, { bubbles: true }));
            });

            setTimeout(() => {
                if (sendBtn && !sendBtn.disabled) { sendBtn.click(); }
                else { editable.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true })); }
                waitForGeminiToFinish(data.svs_source_tab);
            }, 1000);
        }
    }, 1500);

    function waitForGeminiToFinish(sourceTabId) {
        let lastResult = "";
        let stableCount = 0;
        const checkInt = setInterval(() => {
            const responses = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            if (responses.length > 0) {
                let latest = responses[responses.length - 1].innerText;
                if (latest && latest.trim() === lastResult.trim() && latest.length > 200) {
                    stableCount++;
                    if (stableCount >= 5) { // زيادة التأكد لضمان اكتمال الكتابة
                        clearInterval(checkInt);
                        extractPureText(sourceTabId);
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    // --- محرك "الاقتناص النقي" بدون حافظة (V 5.0) ---
    function extractPureText(sourceTabId) {
        const responses = document.querySelectorAll('.model-response-text, .message-content-wrapper');
        let text = responses[responses.length - 1].innerText;

        if (text) {
            console.log("💎 Cleaning final result...");

            // 🛑 1. سحق "تعليمات الهيكل" إذا سربها Gemini بالخطأ
            const instructionsToKill = [
                /الهيكل المطلوبة.*?صحفي فاخر/gs,
                /1\. ابدأ بمقدمة.*?مقال صحفي فاخر/gs,
                /١\. ابدأ بمقدمة.*?مقال صحفي فاخر/gs
            ];
            instructionsToKill.forEach(p => { text = text.replace(p, ''); });

            // 🛑 2. تنظيف التذييلات التقليدية
            text = text.replace(/رابط الفيديو:.*$/gis, '');
            text = text.replace(/alkarbabadi\.net.*$/gis, '');
            text = text.replace(/\d+\s*(views|مشاهدة).*$/gis, '');

            chrome.runtime.sendMessage({ action: "done", text: text.trim(), target: sourceTabId });
        }
    }
})();
