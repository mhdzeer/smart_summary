// Smart YT Bridge - Version 4.4
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "open_gemini") {
        const sourceTabId = sender.tab.id;

        // حفظ البرومبت والتبويب الأصلي في الذاكرة
        chrome.storage.local.set({
            "svs_prompt": request.prompt,
            "svs_source_tab": sourceTabId
        }, () => {
            // فتح Gemini في تبويب نشط للحظات (لضمان عمل السكريبت) ثم إخفاءه
            chrome.tabs.create({ url: "https://gemini.google.com/app", active: true }, (tab) => {
                setTimeout(() => {
                    chrome.tabs.update(sourceTabId, { active: true });
                }, 1000);
            });
            console.log("🚀 Background: Gemini tab opened and prompt saved.");
        });
    }

    if (request.action === "done") {
        const resultText = request.text;
        const targetTabId = request.target;

        console.log("🚀 Background: Sending result back to WP tab: ", targetTabId);

        // إرسال النتيجة لتبويب ووردبريس
        chrome.tabs.sendMessage(targetTabId, { action: "paste_result", text: resultText });

        // إغلاق تبويب Gemini بعد ضمان الإرسال (3 ثوانٍ)
        setTimeout(() => {
            if (sender.tab && sender.tab.id) chrome.tabs.remove(sender.tab.id);
        }, 5000);
    }
});
