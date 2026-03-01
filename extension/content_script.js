(async () => {
    const data = await chrome.storage.local.get(["svs_prompt", "svs_source_tab"]);
    if (data.svs_prompt && window.location.href.includes("gemini.google.com")) {
        console.log("⚡ STEALTH START: Attempting auto-summarization...");
        await chrome.storage.local.remove("svs_prompt");

        let attempts = 0;
        const interval = setInterval(() => {
            attempts++;
            // قائمة بكافة الكاشفات المحتملة لخانات Gemini المتغيرة
            const editable = document.querySelector('div[contenteditable="true"], rich-textarea div, textarea.p-input-textarea, div[placeholder*="Ask"], div[placeholder*="اسأل"]');
            const sendBtn = document.querySelector('button[aria-label*="Send"], button[aria-label*="ارسال"], .send-button, .j-input-footer-send-button, [data-test-id="send-button"]');

            if (editable) {
                editable.focus();
                document.execCommand('insertText', false, data.svs_prompt);
                editable.dispatchEvent(new Event('input', { bubbles: true }));

                setTimeout(() => {
                    if (sendBtn && !sendBtn.disabled) {
                        sendBtn.click();
                        console.log("🚀 STEALTH SUCCESS: Prompt sent!");
                        clearInterval(interval);
                        waitForGeminiResponse(data.svs_source_tab);
                    }
                }, 1500);
            }

            if (attempts > 30) {
                clearInterval(interval);
                console.log("❌ STEALTH FAIL: Interface not found.");
            }
        }, 1500);
    }

    function waitForGeminiResponse(sourceTabId) {
        let lastText = "";
        let stableCount = 0;
        const checkInt = setInterval(() => {
            const responses = document.querySelectorAll('.model-response-text, .message-content-wrapper, [data-test-id="model-response-text"]');
            if (responses.length > 0) {
                let latest = responses[responses.length - 1].innerText;
                if (latest && latest.trim() === lastText.trim() && latest.length > 100) {
                    stableCount++;
                    if (stableCount >= 2) {
                        clearInterval(checkInt);
                        // تنظيف النص
                        let cleaned = latest.split(/رابط الفيديو:|alkarbabadi/i)[0].trim();
                        chrome.runtime.sendMessage({ action: "done", text: cleaned, target: sourceTabId });
                    }
                } else { stableCount = 0; }
                lastText = latest;
            }
        }, 3000);
    }
})();
