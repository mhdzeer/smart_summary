// Smart YT - MASTER SUBMIT & COPY ENGINE (Gemini V 4.9.8)
(async () => {
    if (window.SVS_LOCK) return;
    window.SVS_LOCK = true;

    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (!data.svs_prompt) { window.SVS_LOCK = false; return; }

    console.log("⚡ STEALTH START: Forcing Submit (V 4.9.8)...");
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
            
            // تنبيهات الحساسات
            ['input', 'change', 'keyup', 'keydown'].forEach(name => { 
                editable.dispatchEvent(new Event(name, { bubbles: true })); 
            });

            // --- حلقة الإرسال "الانتحارية" (V 4.9.8) ---
            let submitAttempts = 0;
            const submitLoop = setInterval(() => {
                submitAttempts++;
                // كاشفات أزرار Gemini المتغيرة
                const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, mat-icon[aria-label*="Send"]')?.closest('button');

                if (sendBtn && !sendBtn.disabled) {
                    sendBtn.focus();
                    sendBtn.click();
                    console.log("🚀 Clicked Send Button (Attempt " + submitAttempts + ")");
                }

                // محاكاة ضغطة Enter كاملة بكل الطرق
                const opts = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true };
                editable.dispatchEvent(new KeyboardEvent('keydown', opts));
                editable.dispatchEvent(new KeyboardEvent('keypress', opts));
                editable.dispatchEvent(new KeyboardEvent('keyup', opts));

                // التحقق: هل بدأ Gemini بالرد؟
                const isWriting = document.querySelector('.model-response-text, .message-content-wrapper, .progress-bar');
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
                    if (stableCount >= 4) { 
                        clearInterval(checkInt);
                        extractFromCopyButton(sourceTabId);
                    }
                } else { stableCount = 0; }
                lastResult = latest;
            }
        }, 3000);
    }

    async function extractFromCopyButton(sourceTabId) {
        console.log("💎 Final Step: Locating the Copy button...");
        const copyBtns = document.querySelectorAll('button[aria-label*="Copy"], button[aria-label*="نسخ"], .copy-button');
        let lastCopyBtn = copyBtns[copyBtns.length - 1];

        if (lastCopyBtn) {
            lastCopyBtn.click();
            setTimeout(async () => {
                const tempArea = document.createElement("textarea");
                document.body.appendChild(tempArea);
                tempArea.focus();
                document.execCommand('paste');
                const copiedText = tempArea.value.trim();
                document.body.removeChild(tempArea);

                if (copiedText && copiedText.length > 100) {
                    chrome.runtime.sendMessage({ action: "done", text: copiedText, target: sourceTabId });
                } else {
                    const fallbackText = document.querySelectorAll('.model-response-text, .message-content-wrapper');
                    chrome.runtime.sendMessage({ action: "done", text: fallbackText[fallbackText.length - 1].innerText, target: sourceTabId });
                }
            }, 1000);
        } else {
             const fallbackText = document.querySelectorAll('.model-response-text, .message-content-wrapper');
             chrome.runtime.sendMessage({ action: "done", text: fallbackText[fallbackText.length - 1].innerText, target: sourceTabId });
        }
    }
})();
