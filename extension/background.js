// Smart YT Bridge - Version 4.5 One-Shot Engine
let isOpening = false;

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "open_gemini") {
        if (isOpening) return; // منع فتح أكثر من تبويب
        isOpening = true;

        const sourceTabId = sender.tab.id;

        // حفظ البرومبت والتبويب الأصلي في الذاكرة
        chrome.storage.local.set({
            "svs_prompt": request.prompt,
            "svs_source_tab": sourceTabId
        }, () => {
            // فتح Gemini في تبويب نشط للحظات ثم إخفاءه
            chrome.tabs.create({ url: "https://gemini.google.com/app", active: true }, (tab) => {
                setTimeout(() => {
                    chrome.tabs.update(sourceTabId, { active: true });
                }, 1500);
            });
            console.log("🚀 Background: Opening Gemini tab only once.");
        });
    }

    if (request.action === "done") {
        isOpening = false; // فك قفل الفتح

        const resultText = request.text;
        const targetTabId = request.target;

        console.log("🚀 Background: Sending result back to WP tab.");

        // إرسال النتيجة لتبويب ووردبريس
        chrome.tabs.sendMessage(targetTabId, { action: "paste_result", text: resultText });

        // إغلاق تبويب Gemini بعد ضمان الإرسال
        setTimeout(() => {
            if (sender.tab && sender.tab.id) chrome.tabs.remove(sender.tab.id);
        }, 5000);
    }
});
