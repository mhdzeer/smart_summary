// Smart YT - ULTIMATE SUBMIT ENGINE (Gemini V 5.1)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Forcing Submit (V 5.1)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        const editable = document.querySelector('div[contenteditable="true"], rich-textarea div, textarea');

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(interval);

            editable.focus();
            editable.innerText = "";
            document.execCommand('insertText', false, data.svs_prompt);

            ['input', 'change', 'keyup', 'keydown'].forEach(name => {
                editable.dispatchEvent(new Event(name, { bubbles: true }));
            });

            // --- حلقة الإرسال "الفيزيائية" القوية (V 5.1) ---
            let submitAttempts = 0;
            const submitLoop = setInterval(() => {
                submitAttempts++;

                // البحث عن أزرار الإرسال بكافة أشكالها
                const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"], .j-input-footer-send-button');

                if (sendBtn && !sendBtn.disabled) {
                    // محاكاة نقرة فيزيائية كاملة (Pointer Events)
                    const pointerDown = new PointerEvent('pointerdown', { bubbles: true, cancelable: true, pointerType: 'mouse' });
                    const mousedown = new MouseEvent('mousedown', { bubbles: true, cancelable: true });
                    const mouseup = new MouseEvent('mouseup', { bubbles: true, cancelable: true });
                    const click = new MouseEvent('click', { bubbles: true, cancelable: true });

                    sendBtn.dispatchEvent(pointerDown);
                    sendBtn.dispatchEvent(mousedown);
                    sendBtn.dispatchEvent(mouseup);
                    sendBtn.dispatchEvent(click);

                    console.log("🚀 Forced Physical Click (Attempt " + submitAttempts + ")");
                }

                // محاكاة ضغطة Enter "عنيفة" (V 5.1)
                const enterOpts = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true };
                editable.dispatchEvent(new KeyboardEvent('keydown', enterOpts));
                editable.dispatchEvent(new KeyboardEvent('keypress', enterOpts));

                // التحقق: هل بدأ Gemini بالرد فعلياً؟
                // نبحث عن مؤشر "جاري الكتابة" أو اختفاء زر الإرسال أو ظهور رد جديد
                const isWriting = document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"], .streaming-text');
                if (isWriting || submitAttempts > 15) {
                    console.log("✅ Successfully triggered Gemini!");
                    clearInterval(submitLoop);
                    waitForGeminiToFinish(data.svs_source_tab);
                }
            }, 1000);
        }
        if (attempts > 50) clearInterval(interval);
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
                    if (stableCount >= 5) {
                        clearInterval(checkInt);
                        extractPureText(sourceTabId);
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    function extractPureText(sourceTabId) {
        const responses = document.querySelectorAll('.model-response-text, .message-content-wrapper');
        let text = responses[responses.length - 1].innerText;

        if (text) {
            const instructionsToKill = [
                /الهيكل المطلوبة.*?صحفي فاخر/gs,
                /1\. ابدأ بمقدمة.*?مقال صحفي فاخر/gs,
                /١\. ابدأ بمقدمة.*?مقال صحفي فاخر/gs
            ];
            instructionsToKill.forEach(p => { text = text.replace(p, ''); });
            text = text.replace(/رابط الفيديو:.*$/gis, '');
            text = text.replace(/alkarbabadi\.net.*$/gis, '');
            text = text.replace(/\d+\s*(views|مشاهدة).*$/gis, '');

            chrome.runtime.sendMessage({ action: "done", text: text.trim(), target: sourceTabId });
        }
    }
})();
