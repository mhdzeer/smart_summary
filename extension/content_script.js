// Smart YT - THE HAMMER SUBMIT ENGINE (Gemini V 5.7)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("🔨 THE HAMMER START: Final Force Attempt (V 5.7)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const findBox = setInterval(() => {
        attempts++;
        const selectors = ['div[contenteditable="true"]', 'rich-textarea div', '.prompt-text-area', 'textarea'];
        let ed = null;
        for (let s of selectors) { ed = document.querySelector(s); if (ed) break; }

        if (ed && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(findBox);

            ed.focus();

            // 🛑 1. إيقاظ المحرك (كتابة وهمية تمهيدية)
            document.execCommand('selectAll', false, null);
            document.execCommand('delete', false, null);
            document.execCommand('insertText', false, " ");
            ed.dispatchEvent(new Event('input', { bubbles: true }));

            setTimeout(() => {
                // 🛑 2. وضع النص الحقيقي
                document.execCommand('selectAll', false, null);
                document.execCommand('insertText', false, data.svs_prompt);

                // إرسال كافة التنبيهات اللازمة للأطر البرمجية (Angular/React)
                ['input', 'change', 'compositionend', 'beforeinput'].forEach(n => {
                    ed.dispatchEvent(new Event(n, { bubbles: true }));
                });

                setTimeout(() => { hammerSubmitLoop(ed, data.svs_source_tab); }, 1000);
            }, 500);
        }
    }, 1200);

    function hammerSubmitLoop(ed, sourceTabId) {
        let subAttempts = 0;
        const subLoop = setInterval(() => {
            subAttempts++;

            // محاولة إيجاد الزر بكل الوسائل الممكنة (بما فيها الأيقونة)
            const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"], button:has(mat-icon)');

            if (sendBtn) {
                // 🛑 3. "قهر" حالة التعطيل
                sendBtn.disabled = false;
                sendBtn.removeAttribute('disabled');
                sendBtn.setAttribute('aria-disabled', 'false');

                // إرسال نقرات فيزيائية لكل طبقات الزر
                ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click'].forEach(evt => {
                    const e = (evt.startsWith('pointer')) ? new PointerEvent(evt, { bubbles: true, pointerType: 'mouse' }) : new MouseEvent(evt, { bubbles: true });
                    sendBtn.dispatchEvent(e);
                    // الضغط على ما بداخل الزر أيضاً (الأيقونات/السبان)
                    sendBtn.querySelectorAll('*').forEach(child => child.dispatchEvent(e));
                });
                console.log("🔨 Hammer Click Attempt " + subAttempts);
            }

            // محاكاة مفتاح Enter بكل القوة
            const enter = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true };
            ed.dispatchEvent(new KeyboardEvent('keydown', enter));

            // هل نجحنا؟ (ابحث عن مؤشر الرد)
            if (document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"]') || subAttempts > 20) {
                console.log("✅ THE HAMMER SUCCESS!");
                clearInterval(subLoop);
                waitForGeminiToFinishAndCopy(sourceTabId);
            }
        }, 800);
    }

    function waitForGeminiToFinishAndCopy(sourceTabId) {
        let lastResult = "";
        let stableCount = 0;
        const checkInt = setInterval(() => {
            const res = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            if (res.length > 0) {
                let latest = res[res.length - 1].innerText;
                if (latest && latest.trim() === lastResult.trim() && latest.length > 200) {
                    stableCount++;
                    if (stableCount >= 5) { clearInterval(checkInt); extractFromCopyBtn(sourceTabId); }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    async function extractFromCopyBtn(sourceTabId) {
        const btns = document.querySelectorAll('button[aria-label*="Copy"], button[aria-label*="نسخ"], .copy-button');
        let b = btns[btns.length - 1];
        if (b) {
            b.click();
            setTimeout(() => {
                const ta = document.createElement("textarea"); document.body.appendChild(ta); ta.focus();
                document.execCommand('paste');
                let md = ta.value.trim();
                document.body.removeChild(ta);
                chrome.runtime.sendMessage({ action: "done", text: (md || ""), target: sourceTabId });
            }, 1000);
        } else {
            const fallback = document.querySelectorAll('.model-response-text');
            chrome.runtime.sendMessage({ action: "done", text: fallback[fallback.length - 1].innerText, target: sourceTabId });
        }
    }
})();
