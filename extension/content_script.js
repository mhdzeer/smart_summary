// Smart YT - MASTER CLIPBOARD ICON ENGINE (Gemini V 5.5)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Targeting Copy Icon (V 5.5)...");
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
            document.execCommand('insertText', false, data.svs_prompt);
            ['compositionstart', 'compositionend', 'beforeinput', 'input', 'change'].forEach(n => ed.dispatchEvent(new Event(n, { bubbles: true })));

            setTimeout(() => {
                const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button');
                if (sendBtn && !sendBtn.disabled) sendBtn.click();
                else ed.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));
                waitForGeminiToFinishAndCopy(data.svs_source_tab);
            }, 1200);
        }
    }, 1500);

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
                        clickCopyIconAndGrab(sourceTabId);
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    async function clickCopyIconAndGrab(sourceTabId) {
        console.log("💎 Final Step: Locating and Clicking the Copy button...");

        // البحث عن أيقونة النسخ في آخر رد
        const allCopyBtns = document.querySelectorAll('button[aria-label*="Copy"], button[aria-label*="نسخ"], .copy-button');
        let btn = allCopyBtns[allCopyBtns.length - 1];

        if (btn) {
            btn.click(); // نقرة على أيقونة النسخ!
            console.log("🚀 Clicked Copy Icon!");

            // انتظار الحافظة (1 ثانية) ثم القراءة
            setTimeout(() => {
                const ta = document.createElement("textarea");
                document.body.appendChild(ta);
                ta.focus();
                document.execCommand('paste');
                let markdown = ta.value.trim();
                document.body.removeChild(ta);

                if (markdown && markdown.length > 100) {
                    console.log("✅ Received RAW Markdown from Keyboard!");

                    // تنظيف الهيكل المطلوب (الفلتر المطوّر)
                    markdown = markdown.split(/الهيكل المطلوبة|ابدأ بمقدمة/i)[0].trim();

                    chrome.runtime.sendMessage({ action: "done", text: markdown, target: sourceTabId });
                } else {
                    // Fallback لو فشلت الحافظة
                    let domText = document.querySelectorAll('.model-response-text, .message-content-wrapper');
                    chrome.runtime.sendMessage({ action: "done", text: domText[domText.length - 1].innerText, target: sourceTabId });
                }
            }, 1000);
        } else {
            let domText = document.querySelectorAll('.model-response-text, .message-content-wrapper');
            chrome.runtime.sendMessage({ action: "done", text: domText[domText.length - 1].innerText, target: sourceTabId });
        }
    }
})();
