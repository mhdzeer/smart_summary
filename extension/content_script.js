// Smart YT - JUGGERNAUT SUBMIT ENGINE (Gemini V 5.3)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Forcing Juggernaut Submit (V 5.3)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const findBox = setInterval(() => {
        attempts++;
        const selectors = ['div[contenteditable="true"]', 'rich-textarea div', 'textarea', '.prompt-text-area', '[aria-label*="Prompt"]', '[aria-label*="اكتب"]'];
        let editable = null;
        for (let s of selectors) { editable = document.querySelector(s); if (editable) break; }

        if (editable && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(findBox);

            console.log("✅ Box Found! Activating and Writing...");
            editable.focus();

            // 🛑 1. بروتوكول "تفعيل الحواس" المطور لفتح زر الإرسال
            ['compositionstart', 'compositionend', 'beforeinput', 'input', 'change', 'keyup', 'keydown', 'keypress'].forEach(name => {
                const event = (name.startsWith('composition')) ? new CompositionEvent(name, { bubbles: true }) : new Event(name, { bubbles: true });
                editable.dispatchEvent(event);
            });

            // 🛑 2. الكتابة العميقة
            document.execCommand('insertText', false, data.svs_prompt);
            if (!editable.innerText && !editable.value) {
                editable.innerHTML = "<p>" + data.svs_prompt + "</p>";
                if (editable.value !== undefined) editable.value = data.svs_prompt;
            }

            // تنبيه نهائي بعد الكتابة
            editable.dispatchEvent(new Event('input', { bubbles: true }));

            setTimeout(() => { startJuggernautSubmit(editable, data.svs_source_tab); }, 1200);
        }
        if (attempts > 60) clearInterval(findBox);
    }, 1500);

    function startJuggernautSubmit(editable, sourceTabId) {
        let submitAttempts = 0;
        const submitLoop = setInterval(() => {
            submitAttempts++;

            // 🛑 3. البحث الشامل عن زر الإرسال (بكل الطرق الممكنة)
            const sendBtnSelectors = [
                'button[aria-label*="Send"]', 'button[aria-label*="ارسال"]',
                '.send-button', '[data-test-id="send-button"]',
                'button:has(mat-icon[aria-label*="Send"])',
                'div[role="button"][aria-label*="Send"]',
                '.j-input-footer-send-button'
            ];

            let sendBtn = null;
            for (let s of sendBtnSelectors) {
                try { sendBtn = document.querySelector(s); if (sendBtn) break; } catch (e) { }
            }

            // إذا لم يجده بالمؤشرات، يبحث عنه بالنص
            if (!sendBtn) {
                const allBtns = document.querySelectorAll('button');
                for (let b of allBtns) {
                    if (b.innerText.includes('Send') || b.innerText.includes('إرسال')) { sendBtn = b; break; }
                }
            }

            if (sendBtn) {
                // محاكاة النقرة العميقة والفيزيائية
                const evts = ['pointerdown', 'mousedown', 'mouseup', 'click'];
                evts.forEach(name => {
                    const e = (name.startsWith('pointer')) ? new PointerEvent(name, { bubbles: true, pointerType: 'mouse' }) : new MouseEvent(name, { bubbles: true });
                    sendBtn.dispatchEvent(e);
                    // الضغط على الأيقونات الداخلية أيضاً
                    const icon = sendBtn.querySelector('mat-icon, svg, i');
                    if (icon) icon.dispatchEvent(e);
                });
                console.log("🚀 Forced Juggernaut Click (Attempt " + submitAttempts + ")");
            }

            // محاكاة ضغطة Enter "المدمرة" بكل التنبيهات
            const enterOpts = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true };
            editable.dispatchEvent(new KeyboardEvent('keydown', enterOpts));
            editable.dispatchEvent(new KeyboardEvent('keypress', enterOpts));

            // التحقق من بدء التوليد
            if (document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"], .streaming-text') || submitAttempts > 15) {
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
            const res = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            if (res.length > 0) {
                let latest = res[res.length - 1].innerText;
                if (latest && latest.trim() === lastResult.trim() && latest.length > 200) {
                    stableCount++;
                    if (stableCount >= 5) { clearInterval(checkInt); extractPureText(sourceTabId); }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    function extractPureText(sourceTabId) {
        const res = document.querySelectorAll('.model-response-text, .message-content-wrapper');
        let text = res[res.length - 1].innerText;
        if (text) {
            const kills = [/الهيكل المطلوبة.*?صحفي فاخر/gs, /١?\. ابدأ بمقدمة.*?مقال صحفي فاخر/gs];
            kills.forEach(p => { text = text.replace(p, ''); });
            text = text.replace(/رابط الفيديو:.*$/gis, '').replace(/alkarbabadi\.net.*$/gis, '').replace(/\d+\s*(views|مشاهدة).*$/gis, '');
            chrome.runtime.sendMessage({ action: "done", text: text.trim(), target: sourceTabId });
        }
    }
})();
