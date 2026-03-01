// WordPress Automatic Result Handler
console.log("⚡ Smart YT WP: Monitoring for results...");

// مراقبة الرسائل القادمة من Gemini
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "paste_result") {
        console.log("⚡ WP: Processing result from Gemini...");

        let raw = request.text;

        // --- تنظيف المحتوى بذكاء (إزالة الروابط، الأسماء، المشاهدات) ---
        // حذف رابط الفيديو وكل ما يتبعه إذا كان تكراراً
        raw = raw.replace(/رابط الفيديو:.*?\n/gi, "");
        raw = raw.replace(/alkarbabadi\.net.*?\n/gi, "");
        raw = raw.replace(/\d+\s*views/gi, "");
        raw = raw.replace(/الوحدة\s*الإسلامية/gi, ""); // حذف العنوان المتكرر

        // ضبط التنسيق HTML
        let html = raw.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/### (.*?)\n/g, '<h3>$1</h3>');
        html = html.replace(/\n\n/g, '</p><p>');
        html = '<hr><p>' + html + '</p>';

        // 1. محاولة الإدراج في Gutenberg
        let success = false;
        try {
            const editor = window.wp?.data?.dispatch('core/block-editor') || window.wp?.data?.dispatch('core/editor');
            const select = window.wp?.data?.select('core/block-editor') || window.wp?.data?.select('core/editor');
            if (editor && editor.insertBlocks) {
                const block = window.wp.blocks.createBlock('core/freeform', { content: html });
                editor.insertBlocks(block, select.getBlockCount());
                success = true;
            }
        } catch (e) { }

        // 2. محاولة TinyMCE
        if (!success && window.tinyMCE?.activeEditor) {
            try {
                window.tinyMCE.activeEditor.setContent(window.tinyMCE.activeEditor.getContent() + html);
                success = true;
            } catch (e) { }
        }

        // 3. خانة التحديث اليدوي
        const resArea = document.getElementById('svs_raw_result');
        if (resArea) {
            resArea.value = raw.trim();
        }

        if (success) {
            alert("✅ تم التلخيص والنشر آلياً بنظافة تامة!");
        } else {
            alert("✅ تم جلب التلخيص! يرجى لصقه يدوياً.");
        }
    }
});
