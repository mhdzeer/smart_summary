// Smart YT - NUCLEAR SUBMIT & EXTRACTION ENGINE (Gemini V 5.8)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("🚀 NUCLEAR OPTION START (V 5.8)...");
    await chrome.storage.local.remove("svs_prompt");

    let attempts = 0;
    const findBox = setInterval(() => {
        attempts++;
        const sel = ['div[contenteditable="true"]', 'rich-textarea div', '.prompt-text-area', 'textarea', '[role="textbox"]'];
        let ed = null;
        for (let s of sel) { ed = document.querySelector(s); if (ed) break; }

        if (ed && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(findBox);

            ed.focus();

            // 🛑 1. تنظيف عميق وتهييج الصندوق
            ed.innerText = "";
            ed.dispatchEvent(new Event('focus', { bubbles: true }));

            // 🛑 2. إدراج النص عبر InputEvent المطور
            document.execCommand('insertText', false, data.svs_prompt);

            // تنبيهات الحواس المتعددة
            const evts = ['input', 'change', 'beforeinput', 'compositionend', 'textInput'];
            evts.forEach(n => {
                const e = (n === 'textInput') ? new CustomEvent(n, { detail: { data: data.svs_prompt } }) : new Event(n, { bubbles: true });
                ed.dispatchEvent(e);
            });

            setTimeout(() => { nuclearSubmitLoop(ed, data.svs_source_tab); }, 1000);
        }
        if (attempts > 50) clearInterval(findBox);
    }, 1500);

    function nuclearSubmitLoop(ed, sourceTabId) {
        let subAttempts = 0;
        const subLoop = setInterval(() => {
            subAttempts++;

            // 🛑 3. البحث "النووي" عن الزر (أي زر أو ديف يحمل سمة إرسال)
            const btnSels = [
                'button[aria-label*="Send"]', 'button[aria-label*="ارسال"]',
                '.send-button', '[data-test-id="send-button"]',
                'div[role="button"][aria-label*="Send"]',
                'mat-icon[aria-label*="Send"]', 'svg[aria-label*="Send"]'
            ];

            btnSels.forEach(s => {
                const el = document.querySelector(s);
                if (el) {
                    const target = el.closest('button') || el.closest('[role="button"]') || el;
                    target.disabled = false;
                    target.removeAttribute('disabled');

                    // نقرات فيزيائية لكل الجزيئات
                    ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click'].forEach(evt => {
                        const e = new MouseEvent(evt, { bubbles: true, cancelable: true, view: window });
                        target.dispatchEvent(e);
                        // ضرب العمق (الأيقونات بداخله)
                        target.querySelectorAll('*').forEach(child => child.dispatchEvent(e));
                    });
                }
            });

            // 🛑 4. خدعة مفتاح Enter "المزدوج"
            const options = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true };
            ed.dispatchEvent(new KeyboardEvent('keydown', options));
            ed.dispatchEvent(new KeyboardEvent('keypress', options));

            // محاولة إدراج سطر جديد كحل أخير (أحياناً هذا يطلق الإرسال)
            if (subAttempts % 3 === 0) {
                document.execCommand('insertText', false, '\n');
                ed.dispatchEvent(new KeyboardEvent('keydown', options));
            }

            // فحص البدء (ظهور علامة التحميل أو اختفاء مربع النص أو ظهور رد)
            const isWorking = document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"], button[aria-label*="Stop"]');
            if (isWorking || subAttempts > 25) {
                console.log("✅ NUCLEAR HIT: Process started!");
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
                    if (stableCount >= 5) {
                        clearInterval(checkInt);
                        extractFromCopyIcon(sourceTabId);
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    async function extractFromCopyIcon(sourceTabId) {
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
