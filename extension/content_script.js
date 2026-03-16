// Smart YT - Clipboard Paste Engine (Gemini V 6.2)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("🔑 CLIPBOARD ENGINE START (V 6.2)...");

    let attempts = 0;
    const findBox = setInterval(() => {
        attempts++;
        const sel = [
            'div[aria-label*="prompt"]',
            '.ql-editor.textarea',
            'div[contenteditable="true"][role="textbox"]',
            'rich-textarea div',
            '.prompt-text-area',
            'textarea',
            '[role="textbox"]'
        ];
        let ed = null;
        for (let s of sel) { ed = document.querySelector(s); if (ed) break; }

        if (ed && !window.SVS_SENT) {
            window.SVS_SENT = true;
            clearInterval(findBox);

            // مسح البرومبت من الذاكرة بعد العثور على الصندوق
            chrome.storage.local.remove("svs_prompt");

            console.log("✅ Box Found. Using Clipboard Paste strategy...");

            // تنظيف الهروب الزائد
            let cleanPrompt = data.svs_prompt.replace(/\\+'/g, "'");

            // 🎯 الاستراتيجية: نسخ النص للحافظة بالطريقة الكلاسيكية (تعمل بدون focus)
            // إنشاء textarea مخفي لنسخ النص
            const tempArea = document.createElement('textarea');
            tempArea.value = cleanPrompt;
            tempArea.style.position = 'fixed';
            tempArea.style.top = '-9999px';
            tempArea.style.left = '-9999px';
            tempArea.style.opacity = '0';
            document.body.appendChild(tempArea);
            tempArea.focus();
            tempArea.select();
            const copyOk = document.execCommand('copy');
            document.body.removeChild(tempArea);
            console.log("📋 execCommand copy result:", copyOk);

            // الآن نفوكس على صندوق Gemini ونلصق
            ed.focus();
            ed.click();

            setTimeout(() => {
                // تنظيف الصندوق
                document.execCommand('selectAll', false, null);
                document.execCommand('delete', false, null);

                // لصق النص (يُعامله Gemini كإدخال حقيقي من المستخدم)
                document.execCommand('paste');
                console.log("📝 Paste executed.");

                setTimeout(() => {
                    const hasText = ed.innerText && ed.innerText.trim().length > 10;
                    console.log("✅ Text present:", hasText, "| Length:", ed.innerText?.length);

                    if (!hasText) {
                        // طريقة الطوارئ إذا فشل اللصق
                        console.warn("⚠️ Paste failed, using innerHTML fallback...");
                        ed.innerHTML = cleanPrompt.split('\n').map(l => `<p>${l || '&nbsp;'}</p>`).join('');
                        ed.dispatchEvent(new InputEvent('input', { bubbles: true, cancelable: true, inputType: 'insertText', data: cleanPrompt }));
                    }

                    // انتظار ليتعرف Gemini على رابط يوتيوب ويُفعّل الأداة
                    setTimeout(() => {
                        console.log("🚀 Starting submission loop...");
                        finalKeySubmitLoop(ed, data.svs_source_tab);
                    }, 2500);

                }, 800);
            }, 300);
        }

        if (attempts > 20) { clearInterval(findBox); window.SVS_LOCK = false; console.warn("⏰ Timeout: Input box not found."); }
    }, 1000);

    function finalKeySubmitLoop(ed, sourceTabId) {
        let subAttempts = 0;
        const subLoop = setInterval(() => {
            subAttempts++;

            const btnSels = [
                'button[aria-label*="Send message"]',
                'button[aria-label*="إرسال رسالة"]',
                'button[aria-label*="Send"]',
                'button[aria-label*="ارسال"]',
                '.send-button.submit',
                '.send-button',
                '[data-test-id="send-button"]',
                'div[role="button"][aria-label*="Send"]'
            ];

            let submitted = false;
            for (let s of btnSels) {
                const el = document.querySelector(s);
                if (el) {
                    const target = el.closest('button') || el.closest('[role="button"]') || el;
                    // إزالة حالة التعطيل
                    target.removeAttribute('disabled');
                    target.disabled = false;
                    target.setAttribute('aria-disabled', 'false');

                    // نقر فيزيائي حقيقي
                    target.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true, cancelable: true, pointerType: 'mouse', button: 0 }));
                    target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true, button: 0 }));
                    target.dispatchEvent(new PointerEvent('pointerup', { bubbles: true, cancelable: true, pointerType: 'mouse', button: 0 }));
                    target.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true, button: 0 }));
                    target.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 }));
                    submitted = true;
                    console.log("🖱️ Clicked send button:", s);
                    break;
                }
            }

            if (!submitted) {
                // محاولة Enter كبديل
                const enter = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true };
                ed.dispatchEvent(new KeyboardEvent('keydown', enter));
                ed.dispatchEvent(new KeyboardEvent('keypress', enter));
            }

            // هل بدأ Gemini بالرد؟
            const isResponding = document.querySelector(
                '.model-response-text, .message-content-wrapper, [role="progressbar"], button[aria-label*="Stop"], button[aria-label*="إيقاف"]'
            );
            if (isResponding || subAttempts > 20) {
                console.log("✅ Submission confirmed! Waiting for response...");
                clearInterval(subLoop);
                waitForGeminiComplete(sourceTabId);
            }
        }, 1000);
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
            chrome.runtime.sendMessage({ action: "done", text: fallback[fallback.length - 1]?.innerText || "", target: sourceTabId });
        }
    }
})();
