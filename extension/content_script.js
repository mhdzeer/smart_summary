// Smart YT - ULTRA SUBMIT & COPY ENGINE (Gemini V 5.6)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Ultra Force Submit (V 5.6)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const findBox = setInterval(() => {
        attempts++;
        const sel = ['div[contenteditable="true"]', 'rich-textarea div', 'textarea', '.prompt-text-area'];
        let ed = null;
        for (let s of sel) { ed = document.querySelector(s); if (ed) break; }

        if (ed && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(findBox);

            ed.focus();
            // مسح أي نص قديم
            ed.innerText = "";
            document.execCommand('insertText', false, data.svs_prompt);

            // تفعيل كاشفات الحركة
            ['compositionstart', 'compositionend', 'beforeinput', 'input', 'change', 'keyup', 'keydown'].forEach(n => {
                ed.dispatchEvent(new Event(n, { bubbles: true }));
            });

            // --- حلقة الإرسال الفائقة (V 5.6) ---
            let subAttempts = 0;
            const subLoop = setInterval(() => {
                subAttempts++;
                const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, [data-test-id="send-button"]');

                if (sendBtn && !sendBtn.disabled) {
                    // إطلاق نقرات فيزيائية متتابعة
                    ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click'].forEach(evt => {
                        const e = (evt.startsWith('pointer')) ? new PointerEvent(evt, { bubbles: true, pointerType: 'mouse' }) : new MouseEvent(evt, { bubbles: true });
                        sendBtn.dispatchEvent(e);
                    });
                    console.log("🚀 Forced Ultra Click (Attempt " + subAttempts + ")");
                }

                // محاكاة Enter
                ed.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));

                // فحص البدء
                if (document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"]') || subAttempts > 20) {
                    console.log("✅ Gemini Started!");
                    clearInterval(subLoop);
                    waitForGeminiToFinishAndCopy(data.svs_source_tab);
                }
            }, 800);
        }
    }, 1200);

    function waitForGeminiToFinishAndCopy(sourceTabId) {
        let lastResult = "";
        let stableCount = 0;
        const checkInt = setInterval(() => {
            const res = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            if (res.length > 0) {
                let latest = res[res.length - 1].innerText;
                if (latest && latest.trim() === lastResult.trim() && latest.length > 200) {
                    stableCount++;
                    if (stableCount >= 5) {
                        clearInterval(checkInt);
                        extractFromCopyBtn(sourceTabId);
                    }
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
                const ta = document.createElement("textarea");
                document.body.appendChild(ta);
                ta.focus();
                document.execCommand('paste');
                let md = ta.value.trim();
                document.body.removeChild(ta);
                if (md && md.length > 100) chrome.runtime.sendMessage({ action: "done", text: md, target: sourceTabId });
                else {
                    const fallback = document.querySelectorAll('.model-response-text');
                    chrome.runtime.sendMessage({ action: "done", text: fallback[fallback.length - 1].innerText, target: sourceTabId });
                }
            }, 1000);
        } else {
            const fallback = document.querySelectorAll('.model-response-text');
            chrome.runtime.sendMessage({ action: "done", text: fallback[fallback.length - 1].innerText, target: sourceTabId });
        }
    }
})();
