// Smart YT - ULTIMATE BOX DETECTION & WRITING (Gemini V 5.2)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Targeting Prompt Box (V 5.2)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const findBox = setInterval(() => {
        attempts++;

        // البحث عن صندوق الكتابة بكافة وسائله (التقليدية والحديثة)
        // 1. div contenteditable
        // 2. rich-textarea div
        // 3. textarea الـ fallback
        const selectors = [
            'div[contenteditable="true"]',
            'rich-textarea div',
            'textarea',
            '.prompt-text-area',
            '[aria-label*="Prompt"]',
            '[aria-label*="اكتب"]'
        ];

        let editable = null;
        for (let s of selectors) {
            editable = document.querySelector(s);
            if (editable) break;
        }

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(findBox);

            console.log("✅ Box Found! Starting writing...");

            // التركيز على الصندوق
            editable.focus();

            // --- وسيلة الكتابة المزدوجة لضمان الظهور 100% ---
            // 1. إدراج النص المباشر
            document.execCommand('insertText', false, data.svs_prompt);

            // 2. فحص: هل ظهر النص فعلاً؟ إذا لم يظهر نستخدم وسيلة بديلة
            setTimeout(() => {
                if (!editable.innerText && !editable.value) {
                    console.log("⚠️ Text not showing! Using alternative writing method...");
                    editable.innerHTML = "<p>" + data.svs_prompt + "</p>"; // وسيلة HTML البديلة
                    if (editable.value !== undefined) editable.value = data.svs_prompt; // textarea البديلة
                }

                // إطلاق كافة التنبيهات اللازمة لتنشيط زر الإرسال
                ['input', 'change', 'keyup', 'keydown', 'keypress'].forEach(name => {
                    editable.dispatchEvent(new Event(name, { bubbles: true }));
                });

                // تأخير بسيط لضمان فتح الأزرار
                setTimeout(() => { startForcedSubmit(editable, data.svs_source_tab); }, 1000);
            }, 500);
        }

        if (attempts > 60) {
            clearInterval(findBox);
            console.error("❌ Box not found after 60 attempts! Page might be different.");
        }
    }, 1500);

    function startForcedSubmit(editable, sourceTabId) {
        let submitAttempts = 0;
        const submitLoop = setInterval(() => {
            submitAttempts++;

            const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"]');

            if (sendBtn && !sendBtn.disabled) {
                // محاكاة النقرة الفيزيائية الكاملة (Pointer Events)
                const pointerDown = new PointerEvent('pointerdown', { bubbles: true, cancelable: true, pointerType: 'mouse' });
                const mousedown = new MouseEvent('mousedown', { bubbles: true, cancelable: true });
                const mouseup = new MouseEvent('mouseup', { bubbles: true, cancelable: true });
                const click = new MouseEvent('click', { bubbles: true, cancelable: true });

                sendBtn.dispatchEvent(pointerDown);
                sendBtn.dispatchEvent(mousedown);
                sendBtn.dispatchEvent(mouseup);
                sendBtn.dispatchEvent(click);
                console.log("🚀 Forced Submit (Attempt " + submitAttempts + ")");
            }

            // محاكاة ضغطة Enter "عنيفة" كحل رديف
            const enterOpts = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true };
            editable.dispatchEvent(new KeyboardEvent('keydown', enterOpts));

            // التحقق من بدء التوليد
            const isWriting = document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"]');
            if (isWriting || submitAttempts > 15) {
                console.log("✅ Successfully triggered Gemini!");
                clearInterval(submitLoop);
                waitForGeminiToFinish(sourceTabId);
            }
        }, 1000);
    }

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
            // فلاتر النقاء 5.0
            const instructionsToKill = [/الهيكل المطلوبة.*?صحفي فاخر/gs, /١?\. ابدأ بمقدمة.*?مقال صحفي فاخر/gs];
            instructionsToKill.forEach(p => { text = text.replace(p, ''); });
            text = text.replace(/رابط الفيديو:.*$/gis, '').replace(/alkarbabadi\.net.*$/gis, '').replace(/\d+\s*(views|مشاهدة).*$/gis, '');
            chrome.runtime.sendMessage({ action: "done", text: text.trim(), target: sourceTabId });
        }
    }
})();
