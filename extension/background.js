// Smart YT Bridge - Version 6.1.1 Maintenance
let isOpening = false;
let openTimeout = null;

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "open_gemini") {
        if (isOpening) {
            console.log("⚠️ Already opening Gemini, request ignored.");
            return;
        }
        isOpening = true;

        if (openTimeout) clearTimeout(openTimeout);
        openTimeout = setTimeout(() => { isOpening = false; }, 15000); // 15s safety timeout

        const sourceTabId = sender.tab.id;

        chrome.storage.local.set({
            "svs_prompt": request.prompt,
            "svs_source_tab": sourceTabId
        }, () => {
            chrome.tabs.create({ url: "https://gemini.google.com/app", active: true }, (tab) => {
                setTimeout(() => {
                    try { chrome.tabs.update(sourceTabId, { active: true }); } catch (e) { }
                }, 2000);
            });
            console.log("🚀 Background: Opening Gemini tab.");
        });
    }

    if (request.action === "done") {
        isOpening = false;
        if (openTimeout) clearTimeout(openTimeout);

        const resultText = request.text;
        const targetTabId = request.target;

        console.log("🚀 Background: Sending result back to WP tab.");
        chrome.tabs.sendMessage(targetTabId, { action: "paste_result", text: resultText });

        // إغلاق تبويب Gemini
        setTimeout(() => {
            if (sender.tab && sender.tab.id) {
                try { chrome.tabs.remove(sender.tab.id); } catch (e) { }
            }
        }, 5000);
    }
});
