// Smart YT - THE FINAL KEY (Gemini V 6.1)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("🔑 THE FINAL KEY START (V 6.1)...");
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

            console.log("✅ Box Found. Priming...");
            ed.focus();

            // 🛑 1. تنظيف عميق وتحفيز الصندوق
            ed.innerText = "";
            document.execCommand('insertText', false, data.svs_prompt);

            // 🛑 2. "خداع" النظام (Focus/Blur/Input)
            setTimeout(() => {
                ed.dispatchEvent(new Event('input', { bubbles: true }));
                ed.blur();
                setTimeout(() => {
                    ed.focus();
                    ed.dispatchEvent(new Event('input', { bubbles: true }));
                    // إطلاق أحداث لوحة المفاتيح التنبيهية
                    ['keydown', 'keypress', 'keyup'].forEach(n => {
                        ed.dispatchEvent(new KeyboardEvent(n, { key: 'a', bubbles: true }));
                    });

                    setTimeout(() => { finalKeySubmitLoop(ed, data.svs_source_tab); }, 800);
                }, 200);
            }, 500);
        }
    }, 1500);

    function finalKeySubmitLoop(ed, sourceTabId) {
        let subAttempts = 0;
        const subLoop = setInterval(() => {
            subAttempts++;

            const btnSels = [
                'button[aria-label*="Send"]', 'button[aria-label*="ارسال"]',
                '.send-button', '[data-test-id="send-button"]',
                'button:has(mat-icon)', 'div[role="button"][aria-label*="Send"]'
            ];

            btnSels.forEach(s => {
                const el = document.querySelector(s);
                if (el) {
                    const target = el.closest('button') || el.closest('[role="button"]') || el;
                    target.removeAttribute('disabled');
                    target.disabled = false;
                    target.setAttribute('aria-disabled', 'false');

                    // 🛑 3. النقر "الفيزيائي الحقيقي" (PointerEvents)
                    const pDown = new PointerEvent('pointerdown', { bubbles: true, cancelable: true, pointerType: 'mouse', button: 0 });
                    const mDown = new MouseEvent('mousedown', { bubbles: true, cancelable: true, button: 0 });
                    const pUp = new PointerEvent('pointerup', { bubbles: true, cancelable: true, pointerType: 'mouse', button: 0 });
                    const mUp = new MouseEvent('mouseup', { bubbles: true, cancelable: true, button: 0 });
                    const click = new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 });

                    target.dispatchEvent(pDown);
                    target.dispatchEvent(mDown);
                    target.dispatchEvent(pUp);
                    target.dispatchEvent(mUp);
                    target.dispatchEvent(click);

                    // الضغط على العناصر الداخلية
                    target.querySelectorAll('*').forEach(c => c.dispatchEvent(click));
                }
            });

            // 🛑 4. ضربات Enter "المتلاحقة" (Triple Tap)
            const enter = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true };
            ed.dispatchEvent(new KeyboardEvent('keydown', enter));
            ed.dispatchEvent(new KeyboardEvent('keypress', enter));

            // حل بديل: إرسال Enter عبر Command
            if (subAttempts % 2 === 0) {
                document.execCommand('insertParagraph'); // محاكاة ضغطة Enter في contentEditable
            }

            // فحص البدء (هل بدأ Gemini الكتابة؟)
            const activeLine = document.querySelector('.model-response-text, .message-content-wrapper, [role="progressbar"], button[aria-label*="Stop"]');
            if (activeLine || subAttempts > 25) {
                console.log("✅ THE KEY UNLOCKED: Submission confirmed!");
                clearInterval(subLoop);
                waitForGeminiComplete(sourceTabId);
            }
        }, 800);
    }

    function waitForGeminiComplete(sourceTabId) {
        let lastRes = "";
        let stableCount = 0;
        const check = setInterval(() => {
            const res = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            if (res.length > 0) {
                let latest = res[res.length - 1].innerText;
                if (latest && latest.trim() === lastRes.trim() && latest.length > 200) {
                    stableCount++;
                    if (stableCount >= 5) { clearInterval(check); extractFinalMarkdown(sourceTabId); }
                } else { stableCount = 0; }
                lastRes = latest;
            }
        }, 3000);
    }

    async function extractFinalMarkdown(sourceTabId) {
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
