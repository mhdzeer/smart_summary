// Smart YT - MASTER AUTO-PASTE (Final Hybrid)
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "open_gemini") {
        const sourceTabId = sender.tab.id;
        chrome.storage.local.set({
            "svs_prompt": request.prompt,
            "svs_source_tab": sourceTabId
        }, () => {
            // فتح التبويب ونقله للخلفية فوراً بعد تفعيل سكريبتاته
            chrome.tabs.create({ url: "https://gemini.google.com/app", active: true }, (tab) => {
                // العودة لتبويب ووردبريس فوراً لإخفاء تبويب Gemini
                setTimeout(() => { chrome.tabs.update(sourceTabId, { active: true }); }, 1000);
            });
            console.log("🚀 Stealth: Data set, backgrounding Gemini...");
        });
    }

    if (request.action === "done") {
        const resultText = request.text;
        const targetTabId = request.target;

        console.log("🚀 Stealth: Summarization complete! Back to WP...");

        chrome.tabs.sendMessage(targetTabId, { action: "paste_result", text: resultText }, (response) => {
            // إغلاق تبويب Gemini فوراً
            if (sender.tab && sender.tab.id) chrome.tabs.remove(sender.tab.id);
        });
    }
});
